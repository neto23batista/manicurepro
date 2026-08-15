<?php

use App\Models\Agendamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\User;
use App\Services\FinanceiroService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->dono = User::factory()->create([
        'role'     => 'dono',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
    $this->financeiro = app(FinanceiroService::class);
});

function agendamentoComServicos(
    int $salaoId,
    int $manicureId,
    array $servicosPivot,
    float $desconto = 0,
    ?Carbon $quando = null,
): Agendamento {
    $quando ??= now();
    $total = collect($servicosPivot)->sum(fn ($p) => (float) $p['preco']);

    $ag = Agendamento::factory()->create([
        'salao_id'         => $salaoId,
        'manicure_id'      => $manicureId,
        'status'           => 'concluido',
        'valor_total'      => $total,
        'valor_desconto'   => $desconto,
        'data_hora_inicio' => $quando,
        'data_hora_fim'    => $quando->copy()->addHour(),
    ]);

    $sync = [];
    foreach ($servicosPivot as $row) {
        $sync[$row['servico_id']] = [
            'preco'   => $row['preco'],
            'duracao' => $row['duracao'] ?? 30,
        ];
    }
    $ag->servicos()->sync($sync);

    return $ag->fresh(['servicos', 'manicure']);
}

test('serviço com comissao_percentual sobrepõe a % da manicure', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);
    $servico = Servico::factory()->create([
        'salao_id'            => $this->salao->id,
        'comissao_percentual' => 30,
        'comissao_fixo'       => null,
        'preco'               => 100,
    ]);

    agendamentoComServicos($this->salao->id, $manicure->id, [
        ['servico_id' => $servico->id, 'preco' => 100],
    ]);

    $linha = $this->financeiro->comissoes($this->salao->id, now()->startOfDay(), now()->endOfDay())->first();

    expect($linha['base'])->toBe(100.0);
    expect($linha['comissao'])->toBe(30.0); // 30% do serviço, não 50%
    expect($linha['usa_regra_servico'])->toBeTrue();
});

test('serviço com comissao_fixo prevalece sobre percentual', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);
    $servico = Servico::factory()->create([
        'salao_id'            => $this->salao->id,
        'comissao_percentual' => 40,
        'comissao_fixo'       => 15,
        'preco'               => 100,
    ]);

    agendamentoComServicos($this->salao->id, $manicure->id, [
        ['servico_id' => $servico->id, 'preco' => 100],
    ]);

    $linha = $this->financeiro->comissoes($this->salao->id, now()->startOfDay(), now()->endOfDay())->first();

    expect($linha['comissao'])->toBe(15.0);
    expect($linha['usa_regra_servico'])->toBeTrue();
});

test('sem regra no serviço usa % da manicure (linha a linha)', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);
    $s1 = Servico::factory()->create([
        'salao_id' => $this->salao->id, 'comissao_percentual' => null, 'comissao_fixo' => null, 'preco' => 80,
    ]);
    $s2 = Servico::factory()->create([
        'salao_id' => $this->salao->id, 'comissao_percentual' => 20, 'comissao_fixo' => null, 'preco' => 20,
    ]);

    agendamentoComServicos($this->salao->id, $manicure->id, [
        ['servico_id' => $s1->id, 'preco' => 80],
        ['servico_id' => $s2->id, 'preco' => 20],
    ]);

    // 50% de 80 = 40; 20% de 20 = 4 → 44
    $linha = $this->financeiro->comissoes($this->salao->id, now()->startOfDay(), now()->endOfDay())->first();

    expect($linha['base'])->toBe(100.0);
    expect($linha['comissao'])->toBe(44.0);
    expect($linha['usa_regra_servico'])->toBeTrue();
});

test('desconto é rateado proporcionalmente entre serviços', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);
    $s1 = Servico::factory()->create([
        'salao_id' => $this->salao->id, 'comissao_percentual' => 50, 'preco' => 80,
    ]);
    $s2 = Servico::factory()->create([
        'salao_id' => $this->salao->id, 'comissao_percentual' => 50, 'preco' => 20,
    ]);

    agendamentoComServicos($this->salao->id, $manicure->id, [
        ['servico_id' => $s1->id, 'preco' => 80],
        ['servico_id' => $s2->id, 'preco' => 20],
    ], desconto: 10);

    // bases: 72 e 18; 50% → 36 + 9 = 45
    $linha = $this->financeiro->comissoes($this->salao->id, now()->startOfDay(), now()->endOfDay())->first();

    expect($linha['base'])->toBe(90.0);
    expect($linha['comissao'])->toBe(45.0);
});

test('ajuste manual soma à comissão e grava audit_logs', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);

    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $manicure->id,
        'status'           => 'concluido',
        'valor_total'      => 100,
        'valor_desconto'   => 0,
        'data_hora_inicio' => now(),
        'data_hora_fim'    => now()->addHour(),
    ]);

    $inicio = now()->startOfDay();
    $fim = now()->endOfDay();

    $this->actingAs($this->dono);
    $this->financeiro->registrarAjuste(
        $this->salao->id,
        $manicure->id,
        $inicio,
        $fim,
        10.0,
        'Bônus',
        $this->dono->id,
    );

    $linha = $this->financeiro->comissoes($this->salao->id, $inicio, $fim)->first();

    expect($linha['comissao_calc'])->toBe(50.0);
    expect($linha['ajuste'])->toBe(10.0);
    expect($linha['comissao'])->toBe(60.0);

    $this->assertDatabaseHas('comissao_ajustes', [
        'salao_id'    => $this->salao->id,
        'manicure_id' => $manicure->id,
        'valor'       => 10.00,
        'motivo'      => 'Bônus',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action'  => 'comissao.ajuste',
        'user_id' => $this->dono->id,
    ]);
});

test('ajuste negativo reduz comissão a pagar', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);

    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $manicure->id,
        'status'           => 'concluido',
        'valor_total'      => 100,
        'valor_desconto'   => 0,
        'data_hora_inicio' => now(),
        'data_hora_fim'    => now()->addHour(),
    ]);

    $inicio = now()->startOfDay();
    $fim = now()->endOfDay();

    $this->financeiro->registrarAjuste(
        $this->salao->id, $manicure->id, $inicio, $fim, -15.0, 'Desconto', $this->dono->id,
    );

    $linha = $this->financeiro->comissoes($this->salao->id, $inicio, $fim)->first();
    expect($linha['comissao'])->toBe(35.0);
    expect($linha['a_pagar'])->toBe(35.0);
});

test('dono registra ajuste pela UI', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50, 'ativo' => true]);

    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $manicure->id,
        'status'           => 'concluido',
        'valor_total'      => 100,
        'valor_desconto'   => 0,
        'data_hora_inicio' => now(),
        'data_hora_fim'    => now()->addHour(),
    ]);

    $this->actingAs($this->dono)
        ->post(route('dono.financeiro.comissoes.ajustes.store'), [
            'manicure_id' => $manicure->id,
            'data_inicio' => now()->toDateString(),
            'data_fim'    => now()->toDateString(),
            'periodo'     => 'hoje',
            'valor'       => 5,
            'motivo'      => 'Extra',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('comissao_ajustes', [
        'manicure_id' => $manicure->id,
        'valor'       => 5.00,
        'motivo'      => 'Extra',
    ]);

    $this->actingAs($this->dono)->get('/dono/financeiro?periodo=hoje')
        ->assertOk()
        ->assertSee('Ajustes de comissão');
});

test('atendente não pode registrar ajuste de comissão', function () {
    $manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'comissao' => 50]);
    $atendente = User::factory()->create([
        'role'     => 'atendente',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);

    $this->actingAs($atendente)
        ->post(route('dono.financeiro.comissoes.ajustes.store'), [
            'manicure_id' => $manicure->id,
            'data_inicio' => now()->toDateString(),
            'data_fim'    => now()->toDateString(),
            'valor'       => 10,
        ])
        ->assertForbidden();
});
