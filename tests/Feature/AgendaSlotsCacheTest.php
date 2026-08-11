<?php

use App\Enums\AgendamentoStatus;
use App\Events\AgendamentoCanceladoEvent;
use App\Models\Agendamento;
use App\Models\ConfiguracaoSalao;
use App\Models\DisponibilidadeManicure;
use App\Models\Folga;
use App\Models\FolgaManicure;
use App\Models\HorarioFuncionamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\User;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Cache::flush();

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

    for ($dia = 1; $dia <= 5; $dia++) {
        HorarioFuncionamento::create([
            'salao_id' => $this->salao->id,
            'dia_semana' => $dia,
            'hora_abertura' => '08:00:00',
            'hora_fechamento' => '18:00:00',
            'ativo' => true,
        ]);
        DisponibilidadeManicure::create([
            'manicure_id' => $this->manicure->id,
            'dia_semana' => $dia,
            'hora_inicio' => '08:00:00',
            'hora_fim' => '18:00:00',
            'ativo' => true,
        ]);
    }

    $this->servico = Servico::factory()->create([
        'salao_id' => $this->salao->id,
        'preco' => 30.00,
        'duracao' => 30,
        'ativo' => true,
        'disponivel_online' => true,
    ]);

    $this->agendaService = app(AgendaService::class);
    $this->proximaSegunda = Carbon::now()->next(Carbon::MONDAY)->startOfDay();
});

test('slots disponíveis respeitam conflito e permanecem corretos com cache', function () {
    $data = $this->proximaSegunda->copy();
    $ocupado = $data->copy()->setTime(10, 0);

    Agendamento::factory()->create([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'data_hora_inicio' => $ocupado,
        'data_hora_fim' => $ocupado->copy()->addMinutes(30),
        'status' => 'confirmado',
    ]);

    $slots = $this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30);
    $horas = $slots->pluck('hora')->all();

    expect($horas)->toContain('09:00')
        ->and($horas)->not->toContain('10:00')
        ->and($slots->first())->toHaveKeys(['hora', 'datetime', 'disponivel']);

    // Segunda chamada deve vir do cache e manter o mesmo resultado
    $slotsCache = $this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30);
    expect($slotsCache->pluck('hora')->all())->toBe($horas);
});

test('getSlotsDisponiveis evita N+1 ao usar relações eager-loaded', function () {
    $manicure = Manicure::with(['salao.horarios', 'disponibilidades'])
        ->findOrFail($this->manicure->id);

    $data = $this->proximaSegunda->copy();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->agendaService->getSlotsDisponiveis($manicure, $data, 30);

    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    // Não deve reconsultar horários/disponibilidades já carregadas
    expect($queries->contains(fn ($q) => str_contains($q, 'horarios_funcionamento')))->toBeFalse()
        ->and($queries->contains(fn ($q) => str_contains($q, 'disponibilidades_manicure')))->toBeFalse();
});

test('criar agendamento invalida cache de slots', function () {
    $data = $this->proximaSegunda->copy();
    $inicio = $data->copy()->setTime(9, 0);

    $antes = $this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30);
    expect($antes->pluck('hora')->all())->toContain('09:00');

    $this->agendaService->criarAgendamento([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'servico_ids' => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'origem' => 'web',
        'status' => 'aguardando',
    ]);

    $depois = $this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30);
    expect($depois->pluck('hora')->all())->not->toContain('09:00');
});

test('hold invalida cache de slots', function () {
    $data = $this->proximaSegunda->copy();
    $inicio = $data->copy()->setTime(11, 0);

    expect($this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30)->pluck('hora')->all())
        ->toContain('11:00');

    $this->agendaService->criarHold($this->manicure->id, $inicio, 30, 'token-cache');

    expect($this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30)->pluck('hora')->all())
        ->not->toContain('11:00');
});

test('cancelamento invalida cache de slots', function () {
    $data = $this->proximaSegunda->copy();
    $inicio = $data->copy()->setTime(14, 0);

    $ag = Agendamento::factory()->create([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim' => $inicio->copy()->addMinutes(30),
        'status' => 'confirmado',
    ]);

    expect($this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30)->pluck('hora')->all())
        ->not->toContain('14:00');

    $ag->update(['status' => AgendamentoStatus::Cancelado->value]);
    AgendamentoCanceladoEvent::dispatch($ag, 'Teste cache', 'teste');

    expect($this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30)->pluck('hora')->all())
        ->toContain('14:00');
});

test('reagendar invalida cache das datas anterior e nova', function () {
    $data = $this->proximaSegunda->copy();
    $inicio = $data->copy()->setTime(9, 0);
    $novoInicio = $data->copy()->setTime(15, 0);

    $ag = Agendamento::factory()->create([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim' => $inicio->copy()->addMinutes(30),
        'status' => 'confirmado',
        'valor_total' => 30,
    ]);
    $ag->servicos()->attach($this->servico->id, ['preco' => 30, 'duracao' => 30]);

    expect($this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30)->pluck('hora')->all())
        ->not->toContain('09:00')
        ->toContain('15:00');

    $this->agendaService->reagendar($ag, $novoInicio);

    $horas = $this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30)->pluck('hora')->all();
    expect($horas)->toContain('09:00')
        ->and($horas)->not->toContain('15:00');
});

test('folga da manicure invalida cache de slots', function () {
    $data = $this->proximaSegunda->copy();

    expect($this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30))->not->toBeEmpty();

    FolgaManicure::create([
        'manicure_id' => $this->manicure->id,
        'data' => $data->toDateString(),
        'dia_todo' => true,
        'motivo' => 'Folga teste',
    ]);

    expect($this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30))->toBeEmpty();
});

test('folga do salão invalida cache de slots', function () {
    $data = $this->proximaSegunda->copy();

    expect($this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30))->not->toBeEmpty();

    Folga::create([
        'salao_id' => $this->salao->id,
        'data' => $data->toDateString(),
        'dia_todo' => true,
        'motivo' => 'Feriado',
    ]);

    expect($this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30))->toBeEmpty();
});

test('agendamentoIgnorar usa chave de cache distinta', function () {
    $data = $this->proximaSegunda->copy();
    $inicio = $data->copy()->setTime(10, 0);

    $ag = Agendamento::factory()->create([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim' => $inicio->copy()->addMinutes(30),
        'status' => 'confirmado',
    ]);

    $semIgnore = $this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30);
    $comIgnore = $this->agendaService->getSlotsDisponiveis($this->manicure, $data, 30, $ag->id);

    expect($semIgnore->pluck('hora')->all())->not->toContain('10:00')
        ->and($comIgnore->pluck('hora')->all())->toContain('10:00');
});
