<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Comanda;
use App\Models\Manicure;
use App\Models\Pagamento;
use App\Models\Salao;
use App\Models\User;
use App\Services\ComandaService;
use App\Services\FinanceiroService;
use App\Services\ValePresenteService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->dono = User::factory()->create(['role' => 'dono', 'salao_id' => $this->salao->id, 'ativo' => true]);
    $this->financeiro = app(FinanceiroService::class);
});

function agendamentoConcluido(int $salaoId, int $manicureId, float $valor, float $desconto = 0, ?Carbon $quando = null): Agendamento
{
    $quando ??= now();

    return Agendamento::factory()->create([
        'salao_id'         => $salaoId,
        'manicure_id'      => $manicureId,
        'status'           => 'concluido',
        'valor_total'      => $valor,
        'valor_desconto'   => $desconto,
        'data_hora_inicio' => $quando,
        'data_hora_fim'    => $quando->copy()->addHour(),
    ]);
}

test('caixa soma pagamentos confirmados por forma no período', function () {
    $cliente = Cliente::factory()->create(['salao_id' => $this->salao->id]);
    $ag = agendamentoConcluido($this->salao->id, Manicure::factory()->create(['salao_id' => $this->salao->id])->id, 100);
    $comanda = Comanda::create([
        'agendamento_id' => $ag->id, 'salao_id' => $this->salao->id, 'cliente_id' => $cliente->id,
        'valor_servicos' => 100, 'valor_produtos' => 0, 'desconto' => 0, 'total' => 100, 'status' => 'fechada',
    ]);

    Pagamento::create(['comanda_id' => $comanda->id, 'agendamento_id' => $ag->id, 'salao_id' => $this->salao->id, 'forma' => 'pix', 'valor' => 60, 'status' => 'confirmado']);
    Pagamento::create(['comanda_id' => $comanda->id, 'agendamento_id' => $ag->id, 'salao_id' => $this->salao->id, 'forma' => 'dinheiro', 'valor' => 40, 'status' => 'confirmado']);
    // pendente não conta
    Pagamento::create(['comanda_id' => $comanda->id, 'agendamento_id' => $ag->id, 'salao_id' => $this->salao->id, 'forma' => 'pix', 'valor' => 999, 'status' => 'pendente']);

    $caixa = $this->financeiro->caixa($this->salao->id, now()->startOfDay(), now()->endOfDay());

    expect($caixa['total'])->toBe(100.0);
    expect($caixa['count'])->toBe(2);
    expect($caixa['porForma']->firstWhere('forma', 'pix')['total'])->toBe(60.0);
});

test('comissão usa a taxa do profissional sobre os serviços líquidos', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);

    agendamentoConcluido($this->salao->id, $manicure->id, 100, 0);
    agendamentoConcluido($this->salao->id, $manicure->id, 80, 10); // base líquida 70

    $comissoes = $this->financeiro->comissoes($this->salao->id, now()->startOfDay(), now()->endOfDay());

    expect($comissoes)->toHaveCount(1);
    $linha = $comissoes->first();
    expect($linha['base'])->toBe(170.0);       // 100 + (80-10)
    expect($linha['atendimentos'])->toBe(2);
    expect($linha['comissao'])->toBe(85.0);     // 50% de 170
});

test('apenas atendimentos concluídos do salão entram na comissão', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 100]);

    agendamentoConcluido($this->salao->id, $manicure->id, 100);
    // cancelado não conta
    Agendamento::factory()->create([
        'salao_id'    => $this->salao->id, 'manicure_id' => $manicure->id, 'status' => 'cancelado',
        'valor_total' => 500, 'data_hora_inicio' => now(), 'data_hora_fim' => now()->addHour(),
    ]);
    // outro salão não conta
    $outro = Salao::factory()->create();
    agendamentoConcluido($outro->id, Manicure::factory()->create(['salao_id' => $outro->id])->id, 300);

    $comissoes = $this->financeiro->comissoes($this->salao->id, now()->startOfDay(), now()->endOfDay());

    expect($comissoes->sum('base'))->toBe(100.0);
});

test('período fora da janela não é contabilizado', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);
    agendamentoConcluido($this->salao->id, $manicure->id, 100, 0, now()->subMonth());

    $comissoes = $this->financeiro->comissoes($this->salao->id, now()->startOfDay(), now()->endOfDay());

    expect($comissoes)->toHaveCount(0);
});

test('venda de vale entra no caixa na forma escolhida e o resgate não soma', function () {
    $vales = app(ValePresenteService::class);
    $vale = $vales->criar($this->salao->id, ['valor' => 150, 'forma' => 'pix']);

    // A venda registrou entrada de R$150 em pix.
    $caixa = $this->financeiro->caixa($this->salao->id, now()->startOfDay(), now()->endOfDay());
    expect($caixa['total'])->toBe(150.0);
    expect($caixa['porForma']->firstWhere('forma', 'pix')['total'])->toBe(150.0);

    // Resgate de R$100 num atendimento: NÃO soma de novo — aparece à parte.
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id]);
    $ag = agendamentoConcluido($this->salao->id, $manicure->id, 100);
    app(ComandaService::class)->aplicarVale($ag, $vale, $vales);

    $caixa = $this->financeiro->caixa($this->salao->id, now()->startOfDay(), now()->endOfDay());
    expect($caixa['total'])->toBe(150.0);
    expect($caixa['resgatesVale'])->toBe(100.0);
    expect($caixa['resgatesValeCount'])->toBe(1);
});

test('dono abre a página de financeiro', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 30]);
    agendamentoConcluido($this->salao->id, $manicure->id, 120);

    $this->actingAs($this->dono)->get('/dono/financeiro')
        ->assertOk()
        ->assertSee('Caixa &amp; Comissões', false)
        ->assertSee($manicure->nome);
});

test('cliente não acessa o financeiro', function () {
    $cliente = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->actingAs($cliente)->get('/dono/financeiro')->assertForbidden();
});

test('marcar comissão como paga registra repasse do período', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);
    agendamentoConcluido($this->salao->id, $manicure->id, 200);

    $inicio = now()->startOfDay();
    $fim = now()->endOfDay();

    $pagamento = $this->financeiro->marcarPago(
        $this->salao->id,
        $manicure->id,
        $inicio,
        $fim,
        $this->dono->id,
        'Repasse semanal',
    );

    expect((float) $pagamento->valor)->toBe(100.0);
    expect($pagamento->observacao)->toBe('Repasse semanal');
    expect($pagamento->user_id)->toBe($this->dono->id);
    expect($pagamento->periodo_inicio->toDateString())->toBe($inicio->toDateString());
    expect($pagamento->periodo_fim->toDateString())->toBe($fim->toDateString());

    $comissoes = $this->financeiro->comissoes($this->salao->id, $inicio, $fim);
    $linha = $comissoes->first();
    expect($linha['pago'])->toBeTrue();
    expect($linha['a_pagar'])->toBe(0.0);
    expect($linha['valor_pago'])->toBe(100.0);
});

test('não permite marcar o mesmo período duas vezes', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 40]);
    agendamentoConcluido($this->salao->id, $manicure->id, 100);

    $inicio = now()->startOfDay();
    $fim = now()->endOfDay();

    $this->financeiro->marcarPago($this->salao->id, $manicure->id, $inicio, $fim, $this->dono->id);

    $this->financeiro->marcarPago($this->salao->id, $manicure->id, $inicio, $fim, $this->dono->id);
})->throws(ValidationException::class);

test('desfazer pagamento volta comissão a pagar', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);
    agendamentoConcluido($this->salao->id, $manicure->id, 80);

    $inicio = now()->startOfDay();
    $fim = now()->endOfDay();
    $pagamento = $this->financeiro->marcarPago($this->salao->id, $manicure->id, $inicio, $fim, $this->dono->id);

    $this->financeiro->desfazerPagamento($pagamento, $this->salao->id);

    $comissoes = $this->financeiro->comissoes($this->salao->id, $inicio, $fim);
    expect($comissoes->first()['pago'])->toBeFalse();
    expect($comissoes->first()['a_pagar'])->toBe(40.0);
    expect($this->financeiro->pagamentosDoPeriodo($this->salao->id, $inicio, $fim))->toHaveCount(0);
});

test('dono marca comissão como paga pela UI', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);
    agendamentoConcluido($this->salao->id, $manicure->id, 100);

    $this->actingAs($this->dono)
        ->post('/dono/financeiro/comissoes', [
            'manicure_id' => $manicure->id,
            'data_inicio' => now()->toDateString(),
            'data_fim'    => now()->toDateString(),
            'periodo'     => 'hoje',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('comissao_pagamentos', [
        'salao_id'    => $this->salao->id,
        'manicure_id' => $manicure->id,
        'valor'       => 50.00,
        'user_id'     => $this->dono->id,
    ]);

    $this->actingAs($this->dono)->get('/dono/financeiro?periodo=hoje')
        ->assertOk()
        ->assertSee('Pago')
        ->assertSee('Repasses neste período');
});

test('atendente não pode marcar comissão como paga', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);
    agendamentoConcluido($this->salao->id, $manicure->id, 100);
    $atendente = User::factory()->create([
        'role'     => 'atendente',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);

    $this->actingAs($atendente)
        ->post('/dono/financeiro/comissoes', [
            'manicure_id' => $manicure->id,
            'data_inicio' => now()->toDateString(),
            'data_fim'    => now()->toDateString(),
        ])
        ->assertForbidden();
});
