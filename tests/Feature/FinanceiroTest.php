<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Comanda;
use App\Models\Manicure;
use App\Models\Pagamento;
use App\Models\Salao;
use App\Models\User;
use App\Services\FinanceiroService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->dono = User::factory()->create(['role' => 'dono', 'salao_id' => $this->salao->id, 'ativo' => true]);
    $this->financeiro = app(FinanceiroService::class);
});

function agendamentoConcluido(int $salaoId, int $manicureId, float $valor, float $desconto = 0, ?\Carbon\Carbon $quando = null): Agendamento
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
        'salao_id' => $this->salao->id, 'manicure_id' => $manicure->id, 'status' => 'cancelado',
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
    $vales = app(\App\Services\ValePresenteService::class);
    $vale = $vales->criar($this->salao->id, ['valor' => 150, 'forma' => 'pix']);

    // A venda registrou entrada de R$150 em pix.
    $caixa = $this->financeiro->caixa($this->salao->id, now()->startOfDay(), now()->endOfDay());
    expect($caixa['total'])->toBe(150.0);
    expect($caixa['porForma']->firstWhere('forma', 'pix')['total'])->toBe(150.0);

    // Resgate de R$100 num atendimento: NÃO soma de novo — aparece à parte.
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id]);
    $ag = agendamentoConcluido($this->salao->id, $manicure->id, 100);
    app(\App\Services\ComandaService::class)->aplicarVale($ag, $vale, $vales);

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
