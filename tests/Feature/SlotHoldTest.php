<?php

use App\Models\Agendamento;
use App\Models\ConfiguracaoSalao;
use App\Models\DisponibilidadeManicure;
use App\Models\HorarioFuncionamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\SlotHold;
use App\Models\User;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->salao = Salao::factory()->create(['ativo' => true]);
    ConfiguracaoSalao::create([
        'salao_id' => $this->salao->id,
        'intervalo_agendamento' => 30,
        'antecedencia_minima' => 0,
        'antecedencia_maxima' => 30,
    ]);
    $userManicure = User::factory()->create(['role' => 'manicure', 'salao_id' => $this->salao->id]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id' => $userManicure->id,
        'ativo' => true,
    ]);
    for ($dia = 0; $dia <= 6; $dia++) {
        HorarioFuncionamento::create([
            'salao_id' => $this->salao->id, 'dia_semana' => $dia,
            'hora_abertura' => '08:00:00', 'hora_fechamento' => '18:00:00', 'ativo' => true,
        ]);
        DisponibilidadeManicure::create([
            'manicure_id' => $this->manicure->id, 'dia_semana' => $dia,
            'hora_inicio' => '08:00:00', 'hora_fim' => '18:00:00', 'ativo' => true,
        ]);
    }
    $this->servico = Servico::factory()->create([
        'salao_id' => $this->salao->id, 'preco' => 30, 'duracao' => 30,
        'ativo' => true, 'disponivel_online' => true,
    ]);
    $this->agendaService = app(AgendaService::class);
});

test('reserva ativa remove o slot da lista de disponíveis', function () {
    $data = Carbon::now()->addDay()->setTime(10, 0);

    $this->agendaService->criarHold($this->manicure->id, $data, 30, 'token-A');

    $slots = $this->agendaService->getSlotsDisponiveis($this->manicure, $data->copy()->startOfDay(), 30);

    expect($slots->pluck('hora')->toArray())->not->toContain('10:00');
});

test('endpoint de hold retorna 409 para horário já agendado', function () {
    $data = Carbon::now()->addDay()->setTime(11, 0);
    Agendamento::factory()->create([
        'salao_id' => $this->salao->id, 'manicure_id' => $this->manicure->id,
        'data_hora_inicio' => $data, 'data_hora_fim' => $data->copy()->addMinutes(30),
        'status' => 'confirmado',
    ]);

    $this->postJson(route('public.slots.hold'), [
        'manicure_id' => $this->manicure->id,
        'data_hora_inicio' => $data->toDateTimeString(),
        'duracao' => 30,
    ])->assertStatus(409);
});

test('reserva expirada não bloqueia o slot', function () {
    $data = Carbon::now()->addDay()->setTime(12, 0);

    SlotHold::create([
        'manicure_id' => $this->manicure->id,
        'data_hora_inicio' => $data,
        'data_hora_fim' => $data->copy()->addMinutes(30),
        'token' => 'expirado',
        'expires_at' => Carbon::now()->subMinute(),
    ]);

    $slots = $this->agendaService->getSlotsDisponiveis($this->manicure, $data->copy()->startOfDay(), 30);
    expect($slots->pluck('hora')->toArray())->toContain('12:00');
});

test('criar agendamento libera reservas sobrepostas', function () {
    $data = Carbon::now()->addDay()->setTime(9, 0);
    $this->agendaService->criarHold($this->manicure->id, $data, 30, 'token-B');

    expect(SlotHold::count())->toBe(1);

    $this->agendaService->criarAgendamento([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'servico_ids' => [$this->servico->id],
        'data_hora_inicio' => $data->toDateTimeString(),
        'origem' => 'web',
        'status' => 'aguardando',
    ]);

    expect(SlotHold::count())->toBe(0);
});
