<?php

use App\Models\Agendamento;
use App\Models\ConfiguracaoSalao;
use App\Models\DisponibilidadeManicure;
use App\Models\HorarioFuncionamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\Servico;
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
        'salao_id'              => $this->salao->id,
        'intervalo_agendamento' => 30,
        'antecedencia_minima'   => 0,
        'antecedencia_maxima'   => 30,
    ]);

    $userManicure = User::factory()->create(['role' => 'manicure', 'salao_id' => $this->salao->id]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $userManicure->id,
        'ativo'    => true,
    ]);

    // Horários de funcionamento: seg-sex 08:00-18:00
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
        'preco'             => 30.00,
        'duracao'           => 30,
        'ativo'             => true,
        'disponivel_online' => true,
    ]);

    $this->agendaService = app(AgendaService::class);
});

test('AgendaService retorna slots disponíveis', function () {
    // Próxima segunda-feira
    $data = Carbon::now()->next(Carbon::MONDAY);
    $slots = $this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30);

    expect($slots)->not->toBeEmpty();
    expect($slots->first())->toHaveKey('hora');
    expect($slots->first())->toHaveKey('datetime');
});

test('AgendaService detecta conflito de horário', function () {
    $data = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);

    // Cria agendamento existente às 10:00
    $ag = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'data_hora_inicio' => $data,
        'data_hora_fim'    => $data->copy()->addMinutes(30),
        'status'           => 'confirmado',
    ]);

    $slots = $this->agendaService->getSlotsDisponiveis(
        $this->manicure,
        $data->copy()->startOfDay(),
        30,
    );

    // O slot das 10:00 não deve aparecer
    $horasDisponiveis = $slots->pluck('hora')->toArray();
    expect($horasDisponiveis)->not->toContain('10:00');
});

test('criação de agendamento funciona', function () {
    $data = Carbon::now()->next(Carbon::MONDAY)->setTime(9, 0);

    $ag = $this->agendaService->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $data->toDateTimeString(),
        'origem'           => 'web',
        'status'           => 'aguardando',
    ]);

    expect($ag)->toBeInstanceOf(Agendamento::class);
    expect($ag->status)->toBe('aguardando');
    expect((float) $ag->valor_total)->toBe(30.00);
    $this->assertDatabaseHas('agendamentos', ['id' => $ag->id]);
});

test('criação de agendamento conflitante lança exceção', function () {
    $data = Carbon::now()->next(Carbon::MONDAY)->setTime(9, 0);

    // Primeiro agendamento
    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'data_hora_inicio' => $data,
        'data_hora_fim'    => $data->copy()->addMinutes(30),
        'status'           => 'confirmado',
    ]);

    // Tenta criar segundo agendamento no mesmo horário
    expect(fn () => $this->agendaService->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $data->toDateTimeString(),
        'origem'           => 'web',
        'status'           => 'aguardando',
    ]))->toThrow(Exception::class);
});

test('finalização de atendimento cria comanda e pagamento', function () {
    $data = Carbon::now()->subHour();
    $ag = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'data_hora_inicio' => $data,
        'data_hora_fim'    => $data->copy()->addMinutes(30),
        'status'           => 'em_andamento',
        'valor_total'      => 30.00,
    ]);
    $ag->servicos()->attach($this->servico->id, ['preco' => 30.00, 'duracao' => 30]);

    $comanda = $this->agendaService->finalizarAtendimento($ag, ['forma' => 'pix']);

    $ag->refresh();
    expect($ag->status)->toBe('concluido');
    expect((float) $comanda->total)->toBe(30.00);
    $this->assertDatabaseHas('pagamentos', ['agendamento_id' => $ag->id, 'forma' => 'pix']);
});
