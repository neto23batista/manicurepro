<?php

use App\Models\Agendamento;
use App\Models\ConfiguracaoSalao;
use App\Models\DisponibilidadeManicure;
use App\Models\Feriado;
use App\Models\HorarioFuncionamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\User;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Cache::flush();

    $this->salao = Salao::factory()->create(['ativo' => true]);
    ConfiguracaoSalao::create([
        'salao_id' => $this->salao->id,
        'intervalo_agendamento' => 30,
        'antecedencia_minima' => 0,
        'antecedencia_maxima' => 60,
    ]);

    $this->dono = User::factory()->create([
        'role' => 'dono',
        'ativo' => true,
        'salao_id' => $this->salao->id,
        'email_verified_at' => now(),
    ]);

    $userManicure = User::factory()->create([
        'role' => 'manicure',
        'salao_id' => $this->salao->id,
        'email_verified_at' => now(),
    ]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id' => $userManicure->id,
        'ativo' => true,
    ]);
    $this->manicureUser = $userManicure;

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
            'pausa_inicio' => '12:00:00',
            'pausa_fim' => '13:00:00',
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

    $this->agenda = app(AgendaService::class);
    $this->proximaSegunda = Carbon::now()->next(Carbon::MONDAY)->startOfDay();
});

// ===== FERIADOS =====

test('feriado recorrente bloqueia slots no dia/mês correspondente', function () {
    $data = $this->proximaSegunda->copy();

    expect($this->agenda->getSlotsDisponiveis($this->manicure, $data, 30))->not->toBeEmpty();

    Feriado::create([
        'salao_id' => $this->salao->id,
        'nome' => 'Feriado teste',
        'mes' => (int) $data->month,
        'dia' => (int) $data->day,
        'dia_todo' => true,
        'ativo' => true,
    ]);

    expect($this->agenda->getSlotsDisponiveis($this->manicure, $data, 30))->toBeEmpty();
});

test('feriado parcial só bloqueia janela informada', function () {
    $data = $this->proximaSegunda->copy();

    Feriado::create([
        'salao_id' => $this->salao->id,
        'nome' => 'Meio período',
        'mes' => (int) $data->month,
        'dia' => (int) $data->day,
        'dia_todo' => false,
        'hora_inicio' => '10:00',
        'hora_fim' => '12:00',
        'ativo' => true,
    ]);

    $horas = $this->agenda->getSlotsDisponiveis($this->manicure, $data, 30)->pluck('hora')->all();

    expect($horas)->toContain('09:00')
        ->and($horas)->not->toContain('10:00')
        ->and($horas)->not->toContain('11:00')
        ->and($horas)->toContain('13:00'); // após pausa e após feriado parcial
});

test('dono cria e remove feriado recorrente', function () {
    $this->actingAs($this->dono)->from('/dono/folgas')->post('/dono/feriados', [
        'nome' => 'Natal',
        'mes' => 12,
        'dia' => 25,
        'dia_todo' => '1',
    ])->assertRedirect('/dono/folgas');

    $feriado = Feriado::where('salao_id', $this->salao->id)->where('mes', 12)->where('dia', 25)->first();
    expect($feriado)->not->toBeNull();

    $this->actingAs($this->dono)->from('/dono/folgas')->delete("/dono/feriados/{$feriado->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('feriados', ['id' => $feriado->id]);
});

test('criar feriado invalida cache de slots', function () {
    $data = $this->proximaSegunda->copy();
    expect($this->agenda->getSlotsDisponiveis($this->manicure, $data, 30))->not->toBeEmpty();

    Feriado::create([
        'salao_id' => $this->salao->id,
        'nome' => 'Cache feriado',
        'mes' => (int) $data->month,
        'dia' => (int) $data->day,
        'dia_todo' => true,
        'ativo' => true,
    ]);

    expect($this->agenda->getSlotsDisponiveis($this->manicure, $data, 30))->toBeEmpty();
});

// ===== PAUSAS =====

test('pausa almoço remove slots no intervalo', function () {
    $data = $this->proximaSegunda->copy();
    $horas = $this->agenda->getSlotsDisponiveis($this->manicure, $data, 30)->pluck('hora')->all();

    expect($horas)->toContain('11:30')
        ->and($horas)->not->toContain('12:00')
        ->and($horas)->not->toContain('12:30')
        ->and($horas)->toContain('13:00');
});

test('atualizar pausa invalida cache de slots', function () {
    $data = $this->proximaSegunda->copy();
    $horasAntes = $this->agenda->getSlotsDisponiveis($this->manicure, $data, 30)->pluck('hora')->all();
    expect($horasAntes)->toContain('14:00');

    $disp = DisponibilidadeManicure::where('manicure_id', $this->manicure->id)
        ->where('dia_semana', 1)
        ->first();
    $disp->update(['pausa_inicio' => '14:00:00', 'pausa_fim' => '15:00:00']);

    // Nova carga (como num request HTTP após o save) — relação em memória não fica stale.
    $manicure = Manicure::with(['salao.horarios', 'disponibilidades'])->findOrFail($this->manicure->id);
    $horas = $this->agenda->getSlotsDisponiveis($manicure, $data, 30)->pluck('hora')->all();
    expect($horas)->not->toContain('14:00')
        ->and($horas)->not->toContain('14:30')
        ->and($horas)->toContain('15:00');
});

test('manicure atualiza própria disponibilidade com pausa', function () {
    $dias = [];
    for ($d = 0; $d <= 6; $d++) {
        $dias[$d] = [
            'ativo' => $d >= 1 && $d <= 5 ? '1' : '0',
            'hora_inicio' => '09:00',
            'hora_fim' => '17:00',
            'pausa_inicio' => $d >= 1 && $d <= 5 ? '12:30' : '',
            'pausa_fim' => $d >= 1 && $d <= 5 ? '13:30' : '',
        ];
    }

    $this->actingAs($this->manicureUser)
        ->from('/manicure/disponibilidade')
        ->put('/manicure/disponibilidade', ['dias' => $dias])
        ->assertRedirect();

    $disp = DisponibilidadeManicure::where('manicure_id', $this->manicure->id)->where('dia_semana', 1)->first();
    expect($disp->ativo)->toBeTrue()
        ->and(substr((string) $disp->pausa_inicio, 0, 5))->toBe('12:30');
});

// ===== ENCAIXE =====

test('encaixe permite horário na pausa sem overlap', function () {
    $inicio = $this->proximaSegunda->copy()->setTime(12, 0);

    $ag = $this->agenda->criarAgendamento([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'servico_ids' => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'origem' => 'balcao',
        'status' => 'confirmado',
        'encaixe' => true,
    ]);

    expect($ag->encaixe)->toBeTrue()
        ->and($ag->data_hora_inicio->format('H:i'))->toBe('12:00');
});

test('sem encaixe rejeita horário na pausa', function () {
    $inicio = $this->proximaSegunda->copy()->setTime(12, 0);

    expect(fn () => $this->agenda->criarAgendamento([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'servico_ids' => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'origem' => 'web',
        'status' => 'aguardando',
        'encaixe' => false,
    ]))->toThrow(ValidationException::class);
});

test('encaixe respeita conflito duro com outro agendamento', function () {
    $inicio = $this->proximaSegunda->copy()->setTime(10, 0);

    $this->agenda->criarAgendamento([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'servico_ids' => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'origem' => 'balcao',
        'status' => 'confirmado',
    ]);

    expect(fn () => $this->agenda->criarAgendamento([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'servico_ids' => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'origem' => 'balcao',
        'status' => 'confirmado',
        'encaixe' => true,
    ]))->toThrow(ValidationException::class);
});

test('cliente não pode enviar flag encaixe', function () {
    $cliente = User::factory()->create([
        'role' => 'cliente',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);

    $inicio = $this->proximaSegunda->copy()->setTime(12, 0);

    $this->actingAs($cliente)->post('/cliente/agendamentos', [
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'servico_ids' => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'encaixe' => true,
    ])->assertSessionHasErrors(['encaixe']);
});

test('dono cria encaixe via formulário', function () {
    $inicio = $this->proximaSegunda->copy()->setTime(12, 15);

    $this->actingAs($this->dono)->post('/dono/agendamentos', [
        'manicure_id' => $this->manicure->id,
        'servico_ids' => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'nome_cliente' => 'Cliente Encaixe',
        'encaixe' => '1',
    ])->assertRedirect();

    expect(Agendamento::where('encaixe', true)->where('nome_cliente', 'Cliente Encaixe')->exists())->toBeTrue();
});

// ===== VISÃO SEMANAL =====

test('dono acessa visão semanal da agenda', function () {
    $inicio = $this->proximaSegunda->copy()->setTime(9, 0);
    Agendamento::factory()->create([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim' => $inicio->copy()->addMinutes(30),
        'status' => 'confirmado',
        'nome_cliente' => 'Maria Semanal',
    ]);

    $this->actingAs($this->dono)
        ->get('/dono/agendamentos-semana?data='.$this->proximaSegunda->toDateString())
        ->assertOk()
        ->assertSee('Maria Semanal')
        ->assertSee($this->manicure->nome);
});
