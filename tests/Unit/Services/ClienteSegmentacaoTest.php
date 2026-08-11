<?php

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Cupom;
use App\Models\Manicure;
use App\Models\Salao;
use App\Services\ClienteSegmentacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'manicure.crm.novo_dias' => 30,
        'manicure.crm.recorrente_min_visitas' => 3,
        'manicure.crm.inativo_dias' => 60,
        'manicure.crm.risco_churn_dias' => 40,
        'manicure.crm.vip_gasto_minimo' => 500,
        'manicure.crm.vip_visitas_minimas' => 8,
        'manicure.crm.reativacao.cupom_tipo' => 'percentual',
        'manicure.crm.reativacao.cupom_valor' => 15,
        'manicure.crm.reativacao.cupom_validade_dias' => 30,
    ]);

    $this->crm = app(ClienteSegmentacao::class);
    $this->salao = Salao::factory()->create();
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id]);
});

function criarVisita(Cliente $cliente, Manicure $manicure, $dataHora, float $valor = 100): Agendamento
{
    return Agendamento::factory()->create([
        'salao_id'         => $cliente->salao_id,
        'manicure_id'      => $manicure->id,
        'cliente_id'       => $cliente->id,
        'status'           => AgendamentoStatus::Concluido->value,
        'data_hora_inicio' => $dataHora,
        'data_hora_fim'    => $dataHora->copy()->addMinutes(60),
        'valor_total'      => $valor,
        'valor_desconto'   => 0,
    ]);
}

test('segmenta cliente novo por created_at recente', function () {
    $novo = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'created_at'    => now()->subDays(5),
        'total_visitas' => 0,
    ]);
    $antigo = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'created_at'    => now()->subDays(90),
        'total_visitas' => 0,
    ]);

    expect($this->crm->ehNovo($novo))->toBeTrue()
        ->and($this->crm->ehNovo($antigo))->toBeFalse()
        ->and($this->crm->ehInativo($antigo))->toBeTrue();
});

test('segmenta VIP por gasto ou visitas', function () {
    $porGasto = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'total_gasto'   => 600,
        'total_visitas' => 2,
    ]);
    $porVisitas = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'total_gasto'   => 50,
        'total_visitas' => 10,
    ]);
    $comum = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'total_gasto'   => 50,
        'total_visitas' => 2,
    ]);

    expect($this->crm->ehVip($porGasto))->toBeTrue()
        ->and($this->crm->ehVip($porVisitas))->toBeTrue()
        ->and($this->crm->ehVip($comum))->toBeFalse();
});

test('segmenta recorrente, risco churn e inativo pela última visita', function () {
    $recorrente = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'total_visitas' => 5,
        'created_at'    => now()->subYear(),
    ]);
    criarVisita($recorrente, $this->manicure, now()->subDays(10), 80);

    $risco = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'total_visitas' => 4,
        'created_at'    => now()->subYear(),
    ]);
    criarVisita($risco, $this->manicure, now()->subDays(45), 80);

    $inativo = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'total_visitas' => 3,
        'created_at'    => now()->subYear(),
    ]);
    criarVisita($inativo, $this->manicure, now()->subDays(90), 80);

    expect($this->crm->ehRecorrente($recorrente))->toBeTrue()
        ->and($this->crm->ehRiscoChurn($recorrente))->toBeFalse()
        ->and($this->crm->ehInativo($recorrente))->toBeFalse();

    expect($this->crm->ehRiscoChurn($risco))->toBeTrue()
        ->and($this->crm->ehInativo($risco))->toBeFalse()
        ->and($this->crm->ehRecorrente($risco))->toBeFalse();

    expect($this->crm->ehInativo($inativo))->toBeTrue()
        ->and($this->crm->ehRiscoChurn($inativo))->toBeFalse()
        ->and($this->crm->ehRecorrente($inativo))->toBeFalse();
});

test('filtro de segmento na query sem N+1 de visitas', function () {
    $vip = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'total_gasto'   => 900,
        'total_visitas' => 2,
        'nome'          => 'VIP Cliente',
    ]);
    Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'total_gasto'   => 10,
        'total_visitas' => 1,
        'nome'          => 'Comum',
    ]);

    $ids = $this->crm
        ->aplicarFiltro(Cliente::query()->where('salao_id', $this->salao->id), 'vip')
        ->pluck('id');

    expect($ids)->toContain($vip->id)->and($ids)->toHaveCount(1);
});

test('métricas calculam ticket médio LTV e visitas sem carregar coleção', function () {
    $cliente = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'total_visitas' => 0,
        'total_gasto'   => 0,
    ]);

    criarVisita($cliente, $this->manicure, now()->subDays(20), 100);
    criarVisita($cliente, $this->manicure, now()->subDays(5), 50);
    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $cliente->id,
        'status'           => AgendamentoStatus::Confirmado->value,
        'data_hora_inicio' => now()->addDays(3),
        'data_hora_fim'    => now()->addDays(3)->addHour(),
        'valor_total'      => 70,
    ]);

    $m = $this->crm->metricas($cliente);

    expect($m['visitas_concluidas'])->toBe(2)
        ->and($m['ltv'])->toBe(150.0)
        ->and($m['ticket_medio'])->toBe(75.0)
        ->and($m['ultima_visita']->toDateString())->toBe(now()->subDays(5)->toDateString())
        ->and($m['proxima_visita'])->not->toBeNull();
});

test('cupom de reativação só para inativo e reusa Cupom', function () {
    $inativo = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'total_visitas' => 2,
        'created_at'    => now()->subYear(),
    ]);
    criarVisita($inativo, $this->manicure, now()->subDays(100), 40);

    $ativo = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'total_visitas' => 2,
        'created_at'    => now()->subYear(),
    ]);
    criarVisita($ativo, $this->manicure, now()->subDays(5), 40);

    $cupom = $this->crm->gerarCupomReativacao($inativo);
    $mesmo = $this->crm->gerarCupomReativacao($inativo);

    expect($cupom->codigo)->toStartWith('REATIVA-'.$inativo->id)
        ->and($mesmo->id)->toBe($cupom->id)
        ->and(Cupom::where('salao_id', $this->salao->id)->count())->toBe(1);

    $this->crm->gerarCupomReativacao($ativo);
})->throws(ValidationException::class);
