<?php

/**
 * Smoke do fluxo empresário (Fase 10) — cenários reais de operação diária.
 * Não duplica suítes dedicadas; cobre o caminho ponta a ponta e gaps críticos
 * (cancelamento, no-show, double booking, estoque zerado, IDOR, atendente 403).
 */

use App\Models\Agendamento;
use App\Models\Caixa;
use App\Models\Cliente;
use App\Models\ConfiguracaoSalao;
use App\Models\DisponibilidadeManicure;
use App\Models\HorarioFuncionamento;
use App\Models\Manicure;
use App\Models\Produto;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\CaixaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->salao = Salao::factory()->create(['ativo' => true]);
    ConfiguracaoSalao::create([
        'salao_id'                    => $this->salao->id,
        'intervalo_agendamento'       => 30,
        'antecedencia_minima'         => 0,
        'antecedencia_maxima'         => 30,
        'permitir_agendamento_online' => true,
        'limite_alerta_no_show'       => 2,
    ]);

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

    $userManicure = User::factory()->create([
        'role'     => 'manicure',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $userManicure->id,
        'ativo'    => true,
    ]);

    for ($dia = 1; $dia <= 5; $dia++) {
        HorarioFuncionamento::create([
            'salao_id'        => $this->salao->id,
            'dia_semana'      => $dia,
            'hora_abertura'   => '08:00:00',
            'hora_fechamento' => '18:00:00',
            'ativo'           => true,
        ]);
        DisponibilidadeManicure::create([
            'manicure_id' => $this->manicure->id,
            'dia_semana'  => $dia,
            'hora_inicio' => '08:00:00',
            'hora_fim'    => '18:00:00',
            'ativo'       => true,
        ]);
    }

    $this->servico = Servico::factory()->create([
        'salao_id'          => $this->salao->id,
        'preco'             => 50.00,
        'duracao'           => 30,
        'ativo'             => true,
        'disponivel_online' => true,
    ]);

    $this->cliente = Cliente::factory()->create([
        'salao_id'     => $this->salao->id,
        'total_faltas' => 0,
    ]);

    $this->agenda = app(AgendaService::class);
    $this->caixaService = app(CaixaService::class);
});

test('fluxo diário: abrir caixa → agendar → finalizar → fechar caixa', function () {
    $this->actingAs($this->dono)
        ->post('/dono/financeiro/caixa/abrir', ['saldo_inicial' => 100])
        ->assertRedirect();

    $caixa = Caixa::where('salao_id', $this->salao->id)->whereNull('fechado_em')->first();
    expect($caixa)->not->toBeNull();

    $inicio = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
    $ag = $this->agenda->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'origem'           => 'balcao',
        'status'           => 'confirmado',
    ]);

    expect($ag->status)->toBe('confirmado')
        ->and((float) $ag->valor_total)->toBe(50.0);

    $ag->update([
        'status'           => 'em_andamento',
        'data_hora_inicio' => Carbon::now()->subHour(),
        'data_hora_fim'    => Carbon::now()->subMinutes(30),
    ]);

    $comanda = $this->agenda->finalizarAtendimento($ag->fresh(), [
        'forma' => 'dinheiro',
        'valor' => 50,
    ]);

    expect($ag->fresh()->status)->toBe('concluido')
        ->and((float) $comanda->total)->toBe(50.0);

    $this->actingAs($this->dono)
        ->post("/dono/financeiro/caixa/{$caixa->id}/fechar", [
            'saldo_final_informado' => 150,
        ])
        ->assertRedirect();

    expect($caixa->fresh()->estaAberto())->toBeFalse();
});

test('cancelamento de agendamento aguardando libera o slot', function () {
    $inicio = Carbon::now()->next(Carbon::MONDAY)->setTime(11, 0);

    $ag = $this->agenda->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'origem'           => 'balcao',
        'status'           => 'aguardando',
    ]);

    $this->actingAs($this->dono)
        ->patch(route('dono.agendamentos.status', $ag), ['status' => 'cancelado'])
        ->assertRedirect();

    expect($ag->fresh()->status)->toBe('cancelado');

    // Slot volta a ficar disponível (sem conflito duro).
    $slots = $this->agenda->getSlotsDisponiveis($this->manicure, $inicio->copy()->startOfDay(), 30);
    expect($slots->pluck('hora')->all())->toContain('11:00');
});

test('no-show incrementa faltas e marca risco no limite', function () {
    $inicio = Carbon::now()->subHours(2);

    $ag1 = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => 'confirmado',
    ]);

    $this->actingAs($this->dono)
        ->patch(route('dono.agendamentos.status', $ag1), ['status' => 'nao_compareceu'])
        ->assertRedirect();

    expect($this->cliente->fresh()->total_faltas)->toBe(1)
        ->and($this->cliente->fresh()->eh_risco_no_show)->toBeFalse();

    $ag2 = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'data_hora_inicio' => $inicio->copy()->subDay(),
        'data_hora_fim'    => $inicio->copy()->subDay()->addMinutes(30),
        'status'           => 'confirmado',
    ]);

    $this->actingAs($this->dono)
        ->patch(route('dono.agendamentos.status', $ag2), ['status' => 'nao_compareceu'])
        ->assertRedirect();

    expect($this->cliente->fresh()->total_faltas)->toBe(2)
        ->and($this->cliente->fresh()->eh_risco_no_show)->toBeTrue();
});

test('double booking no mesmo horário é rejeitado', function () {
    $inicio = Carbon::now()->next(Carbon::MONDAY)->setTime(14, 0);

    $this->agenda->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'origem'           => 'balcao',
        'status'           => 'confirmado',
    ]);

    expect(fn () => $this->agenda->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'origem'           => 'balcao',
        'status'           => 'aguardando',
    ]))->toThrow(ValidationException::class);
});

test('estoque zerado bloqueia venda na comanda', function () {
    $inicio = Carbon::now()->addDay()->setTime(10, 0);
    $ag = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => 'confirmado',
        'valor_total'      => 50,
    ]);

    $produto = Produto::create([
        'salao_id'       => $this->salao->id,
        'nome'           => 'Esmalte Zerado',
        'preco_venda'    => 15,
        'estoque_atual'  => 0,
        'estoque_minimo' => 1,
        'unidade'        => 'un',
        'ativo'          => true,
    ]);

    $this->actingAs($this->dono)
        ->post(route('dono.agendamentos.produto', $ag), [
            'produto_id' => $produto->id,
            'quantidade' => 1,
        ])
        ->assertSessionHasErrors('error');

    expect((float) $produto->fresh()->estoque_atual)->toBe(0.0);
});

test('atendente recebe 403 no financeiro e no caixa', function () {
    $this->actingAs($this->atendente);

    $this->get('/dono/financeiro')->assertForbidden();
    $this->get('/dono/financeiro/caixa')->assertForbidden();
    $this->post('/dono/financeiro/caixa/abrir', ['saldo_inicial' => 10])->assertForbidden();
});

test('IDOR: dono de outro salão não acessa agendamento', function () {
    $outro = Salao::factory()->create(['ativo' => true]);
    $donoB = User::factory()->create([
        'role'     => 'dono',
        'salao_id' => $outro->id,
        'ativo'    => true,
    ]);

    $inicio = Carbon::now()->addDay()->setTime(9, 0);
    $ag = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => 'confirmado',
    ]);

    $this->actingAs($donoB)
        ->get(route('dono.agendamentos.show', $ag))
        ->assertForbidden();
});
