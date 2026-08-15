<?php

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ConfiguracaoSalao;
use App\Models\Cupom;
use App\Models\FidelidadePonto;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use App\Services\FidelidadeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'manicure.indicacao.enabled'     => true,
        'manicure.indicacao.recompensa'  => 'pontos',
        'manicure.indicacao.pontos'      => 50,
        'manicure.indicacao.cupom_valor' => 20,
    ]);

    $this->service = app(FidelidadeService::class);

    $this->salao = Salao::factory()->create();
    ConfiguracaoSalao::create([
        'salao_id'         => $this->salao->id,
        'fidelidade_ativo' => true,
        'pontos_por_real'  => 1,
    ]);

    $userManicure = User::factory()->create(['role' => 'manicure', 'salao_id' => $this->salao->id]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $userManicure->id,
    ]);

    $this->indicador = Cliente::factory()->create([
        'salao_id'          => $this->salao->id,
        'nome'              => 'Indicadora',
        'pontos_fidelidade' => 0,
    ]);

    $this->indicado = Cliente::factory()->create([
        'salao_id'                => $this->salao->id,
        'nome'                    => 'Indicada',
        'indicado_por_cliente_id' => $this->indicador->id,
        'pontos_fidelidade'       => 0,
    ]);
});

function agendamentoIndicacaoConcluido(object $ctx, Cliente $cliente): Agendamento
{
    return Agendamento::factory()->create([
        'salao_id'    => $ctx->salao->id,
        'manicure_id' => $ctx->manicure->id,
        'cliente_id'  => $cliente->id,
        'status'      => AgendamentoStatus::Concluido->value,
    ]);
}

test('cliente recebe codigo_indicacao único ao ser criado', function () {
    expect($this->indicador->codigo_indicacao)->not->toBeEmpty();
    expect($this->indicado->codigo_indicacao)->not->toBeEmpty();
    expect($this->indicador->codigo_indicacao)->not->toBe($this->indicado->codigo_indicacao);
});

test('primeira visita concluída credita pontos ao indicador', function () {
    $ag = agendamentoIndicacaoConcluido($this, $this->indicado);

    $this->service->creditarPorAtendimento($ag, 80.0);

    $this->indicador->refresh();
    expect($this->indicador->pontos_fidelidade)->toBe(50);

    $this->assertDatabaseHas('fidelidade_pontos', [
        'cliente_id' => $this->indicador->id,
        'tipo'       => 'ganho',
        'pontos'     => 50,
    ]);

    expect(
        FidelidadePonto::where('cliente_id', $this->indicador->id)
            ->where('descricao', 'like', "Indicação #{$this->indicado->id}%")
            ->exists(),
    )->toBeTrue();
});

test('segunda visita do indicado não recompensa de novo', function () {
    $ag1 = agendamentoIndicacaoConcluido($this, $this->indicado);
    $this->service->creditarPorAtendimento($ag1, 50.0);

    $ag2 = agendamentoIndicacaoConcluido($this, $this->indicado);
    $this->service->creditarPorAtendimento($ag2, 50.0);

    expect($this->indicador->fresh()->pontos_fidelidade)->toBe(50);
    expect(
        FidelidadePonto::where('cliente_id', $this->indicador->id)
            ->where('descricao', 'like', "Indicação #{$this->indicado->id}%")
            ->count(),
    )->toBe(1);
});

test('não recompensa quando indicação está desativada', function () {
    config(['manicure.indicacao.enabled' => false]);

    $ag = agendamentoIndicacaoConcluido($this, $this->indicado);
    $this->service->creditarPorAtendimento($ag, 100.0);

    expect($this->indicador->fresh()->pontos_fidelidade)->toBe(0);
});

test('modo cupom gera cupom para o indicador na primeira visita', function () {
    config(['manicure.indicacao.recompensa' => 'cupom']);

    $ag = agendamentoIndicacaoConcluido($this, $this->indicado);
    $this->service->creditarPorAtendimento($ag, 40.0);

    expect($this->indicador->fresh()->pontos_fidelidade)->toBe(0);
    expect(Cupom::where('salao_id', $this->salao->id)->where('codigo', 'like', 'IND-%')->count())->toBe(1);

    $this->assertDatabaseHas('fidelidade_pontos', [
        'cliente_id' => $this->indicador->id,
        'tipo'       => 'ajuste',
        'pontos'     => 0,
    ]);
});

test('sem indicado_por não há recompensa de indicação', function () {
    $semIndicacao = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'nome'     => 'Sem indicação',
    ]);

    $ag = agendamentoIndicacaoConcluido($this, $semIndicacao);
    $this->service->creditarPorAtendimento($ag, 100.0);

    expect(
        FidelidadePonto::where('descricao', 'like', 'Indicação #%')->count(),
    )->toBe(0);
});
