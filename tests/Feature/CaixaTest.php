<?php

use App\Models\AuditLog;
use App\Models\Caixa;
use App\Models\Cliente;
use App\Models\Comanda;
use App\Models\ComissaoPagamento;
use App\Models\Despesa;
use App\Models\Manicure;
use App\Models\Pagamento;
use App\Models\Salao;
use App\Models\User;
use App\Services\CaixaService;
use App\Services\FinanceiroService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->dono = User::factory()->create([
        'role'     => 'dono',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
    $this->atendente = User::factory()->create([
        'role'     => 'atendente',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
    $this->caixaService = app(CaixaService::class);
    $this->financeiro = app(FinanceiroService::class);
});

test('abrir movimentar e fechar caixa calcula diferença', function () {
    $caixa = $this->caixaService->abrir($this->salao->id, 100, $this->dono->id);

    $this->caixaService->movimentar($caixa, 'entrada', 50, 'Venda avulsa', $this->dono->id);
    $this->caixaService->movimentar($caixa, 'sangria', 30, 'Cofre', $this->dono->id);

    expect($this->caixaService->saldoCalculado($caixa->fresh('movimentacoes')))->toBe(120.0);

    $fechado = $this->caixaService->fechar($caixa, 115, $this->dono->id, 'Contagem');

    expect((float) $fechado->saldo_calculado)->toBe(120.0);
    expect((float) $fechado->saldo_final_informado)->toBe(115.0);
    expect((float) $fechado->diferenca)->toBe(-5.0);
    expect($fechado->estaAberto())->toBeFalse();

    $this->assertDatabaseHas('audit_logs', [
        'action'       => 'caixa.fechado',
        'auditable_id' => $fechado->id,
    ]);
});

test('não permite abrir segundo caixa enquanto houver um aberto', function () {
    $this->caixaService->abrir($this->salao->id, 50, $this->dono->id);

    expect(fn () => $this->caixaService->abrir($this->salao->id, 10, $this->dono->id))
        ->toThrow(ValidationException::class);

    expect(Caixa::where('salao_id', $this->salao->id)->whereNull('fechado_em')->count())->toBe(1);
});

test('não permite movimentar caixa fechado', function () {
    $caixa = $this->caixaService->abrir($this->salao->id, 0, $this->dono->id);
    $this->caixaService->fechar($caixa, 0, $this->dono->id);

    $this->caixaService->movimentar($caixa->fresh(), 'entrada', 10, 'Tarde demais', $this->dono->id);
})->throws(ValidationException::class);

test('não permite movimentar caixa fechado com instância desatualizada', function () {
    $caixa = $this->caixaService->abrir($this->salao->id, 0, $this->dono->id);
    $this->caixaService->fechar($caixa, 0, $this->dono->id);

    $this->caixaService->movimentar($caixa, 'entrada', 10, 'Tarde demais', $this->dono->id);
})->throws(ValidationException::class);

test('dono abre e fecha caixa pela UI', function () {
    $this->actingAs($this->dono)
        ->post('/dono/financeiro/caixa/abrir', [
            'saldo_inicial' => 80,
            'observacao'    => 'Abertura do dia',
        ])
        ->assertRedirect(route('dono.financeiro.caixa.index'));

    $caixa = Caixa::first();
    expect($caixa)->not->toBeNull();
    expect((float) $caixa->saldo_inicial)->toBe(80.0);

    $this->actingAs($this->dono)
        ->post("/dono/financeiro/caixa/{$caixa->id}/movimentar", [
            'tipo'      => 'suprimento',
            'valor'     => 20,
            'descricao' => 'Troco extra',
        ])
        ->assertRedirect();

    $this->actingAs($this->dono)
        ->post("/dono/financeiro/caixa/{$caixa->id}/fechar", [
            'saldo_final_informado' => 100,
        ])
        ->assertRedirect();

    $caixa->refresh();
    expect((float) $caixa->diferenca)->toBe(0.0);
    expect(AuditLog::where('action', 'caixa.fechado')->exists())->toBeTrue();
});

test('atendente recebe 403 no caixa operacional', function () {
    $this->actingAs($this->atendente)
        ->get('/dono/financeiro/caixa')
        ->assertForbidden();

    $this->actingAs($this->atendente)
        ->post('/dono/financeiro/caixa/abrir', ['saldo_inicial' => 10])
        ->assertForbidden();
});

test('despesas crud do dono', function () {
    $this->actingAs($this->dono)
        ->post('/dono/financeiro/despesas', [
            'descricao'  => 'Aluguel do salão',
            'categoria'  => 'aluguel',
            'fornecedor' => 'Imobiliária X',
            'valor'      => 1500,
            'vencimento' => now()->addDays(5)->toDateString(),
            'recorrente' => 1,
            'pago'       => 0,
        ])
        ->assertRedirect(route('dono.financeiro.despesas.index'));

    $despesa = Despesa::first();
    expect($despesa->descricao)->toBe('Aluguel do salão');
    expect($despesa->recorrente)->toBeTrue();
    expect($despesa->estaPaga())->toBeFalse();

    $this->actingAs($this->dono)
        ->put("/dono/financeiro/despesas/{$despesa->id}", [
            'descricao'  => 'Aluguel atualizado',
            'categoria'  => 'aluguel',
            'fornecedor' => 'Imobiliária Y',
            'valor'      => 1600,
            'vencimento' => now()->addDays(10)->toDateString(),
            'recorrente' => 1,
        ])
        ->assertRedirect();

    expect($despesa->fresh()->descricao)->toBe('Aluguel atualizado');
    expect((float) $despesa->fresh()->valor)->toBe(1600.0);

    $this->actingAs($this->dono)
        ->post("/dono/financeiro/despesas/{$despesa->id}/pagar")
        ->assertRedirect();

    expect($despesa->fresh()->estaPaga())->toBeTrue();

    $this->actingAs($this->dono)
        ->delete("/dono/financeiro/despesas/{$despesa->id}")
        ->assertRedirect();

    expect(Despesa::count())->toBe(0);
});

test('atendente recebe 403 em despesas', function () {
    $this->actingAs($this->atendente)
        ->get('/dono/financeiro/despesas')
        ->assertForbidden();

    $this->actingAs($this->atendente)
        ->post('/dono/financeiro/despesas', [
            'descricao'  => 'Hack',
            'categoria'  => 'outros',
            'valor'      => 10,
            'vencimento' => now()->toDateString(),
        ])
        ->assertForbidden();
});

test('dono não altera despesa de outro salão', function () {
    $outro = Salao::factory()->create();
    $despesa = Despesa::create([
        'salao_id'   => $outro->id,
        'descricao'  => 'Aluguel alheio',
        'categoria'  => 'aluguel',
        'valor'      => 900,
        'vencimento' => now()->addDays(3)->toDateString(),
        'user_id'    => $this->dono->id,
    ]);

    $this->actingAs($this->dono)
        ->put("/dono/financeiro/despesas/{$despesa->id}", [
            'descricao'  => 'Tentativa IDOR',
            'categoria'  => 'aluguel',
            'valor'      => 1,
            'vencimento' => now()->toDateString(),
        ])
        ->assertForbidden();

    $this->actingAs($this->dono)
        ->post("/dono/financeiro/despesas/{$despesa->id}/pagar")
        ->assertForbidden();

    $this->actingAs($this->dono)
        ->delete("/dono/financeiro/despesas/{$despesa->id}")
        ->assertForbidden();

    expect($despesa->fresh()->descricao)->toBe('Aluguel alheio');
    expect($despesa->fresh()->estaPaga())->toBeFalse();
    expect(Despesa::whereKey($despesa->id)->exists())->toBeTrue();
});

test('fluxoCaixa soma entradas e saídas do período', function () {
    $cliente = Cliente::factory()->create(['salao_id' => $this->salao->id]);
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);

    $ag = \App\Models\Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $manicure->id,
        'status'           => 'concluido',
        'valor_total'      => 200,
        'valor_desconto'   => 0,
        'data_hora_inicio' => now(),
        'data_hora_fim'    => now()->addHour(),
    ]);

    $comanda = Comanda::create([
        'agendamento_id' => $ag->id,
        'salao_id'       => $this->salao->id,
        'cliente_id'     => $cliente->id,
        'valor_servicos' => 200,
        'valor_produtos' => 0,
        'desconto'       => 0,
        'total'          => 200,
        'status'         => 'fechada',
    ]);

    Pagamento::create([
        'comanda_id'     => $comanda->id,
        'agendamento_id' => $ag->id,
        'salao_id'       => $this->salao->id,
        'forma'          => 'pix',
        'valor'          => 200,
        'status'         => 'confirmado',
    ]);

    Despesa::create([
        'salao_id'   => $this->salao->id,
        'descricao'  => 'Produtos',
        'categoria'  => 'produtos',
        'valor'      => 50,
        'vencimento' => now()->toDateString(),
        'pago_em'    => now(),
        'user_id'    => $this->dono->id,
    ]);

    Despesa::create([
        'salao_id'   => $this->salao->id,
        'descricao'  => 'Internet',
        'categoria'  => 'internet',
        'valor'      => 100,
        'vencimento' => now()->toDateString(),
        'pago_em'    => null,
        'user_id'    => $this->dono->id,
    ]);

    ComissaoPagamento::create([
        'salao_id'       => $this->salao->id,
        'manicure_id'    => $manicure->id,
        'periodo_inicio' => now()->toDateString(),
        'periodo_fim'    => now()->toDateString(),
        'valor'          => 30,
        'pago_em'        => now(),
        'user_id'        => $this->dono->id,
    ]);

    $fluxo = $this->financeiro->fluxoCaixa($this->salao->id, now()->startOfDay(), now()->endOfDay());

    expect($fluxo['entradas'])->toBe(200.0);
    expect($fluxo['despesas_pagas'])->toBe(50.0);
    expect($fluxo['comissoes_pagas'])->toBe(30.0);
    expect($fluxo['saidas'])->toBe(80.0);
    expect($fluxo['saldo'])->toBe(120.0);
    expect($fluxo['despesas_pendentes'])->toBe(100.0);
    expect($fluxo['despesas_pendentes_count'])->toBe(1);
});

test('dono vê fluxo de caixa na página financeiro', function () {
    $this->actingAs($this->dono)
        ->get('/dono/financeiro')
        ->assertOk()
        ->assertSee('Fluxo de caixa');
});
