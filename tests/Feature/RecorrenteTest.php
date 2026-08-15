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
        'antecedencia_maxima'   => 120,
    ]);
    $userManicure = User::factory()->create(['role' => 'manicure', 'salao_id' => $this->salao->id]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id, 'user_id' => $userManicure->id, 'ativo' => true,
    ]);
    for ($dia = 0; $dia <= 6; $dia++) {
        HorarioFuncionamento::create([
            'salao_id'      => $this->salao->id, 'dia_semana' => $dia,
            'hora_abertura' => '08:00:00', 'hora_fechamento' => '18:00:00', 'ativo' => true,
        ]);
        DisponibilidadeManicure::create([
            'manicure_id' => $this->manicure->id, 'dia_semana' => $dia,
            'hora_inicio' => '08:00:00', 'hora_fim' => '18:00:00', 'ativo' => true,
        ]);
    }
    $this->servico = Servico::factory()->create([
        'salao_id' => $this->salao->id, 'preco' => 30, 'duracao' => 30,
        'ativo'    => true, 'disponivel_online' => true,
    ]);
    $this->agendaService = app(AgendaService::class);
});

test('cria série semanal de agendamentos', function () {
    $base = Carbon::now()->addDay()->setTime(10, 0);

    $res = $this->agendaService->criarRecorrente([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $base->toDateTimeString(),
        'origem'           => 'balcao',
        'status'           => 'confirmado',
    ], 'semanal', 4);

    expect($res['criados'])->toHaveCount(4);
    expect($res['pulados'])->toBeEmpty();
    expect(Agendamento::count())->toBe(4);

    // 2ª ocorrência é exatamente 7 dias depois
    expect(Agendamento::orderBy('data_hora_inicio')->get()[1]->data_hora_inicio->format('Y-m-d H:i'))
        ->toBe($base->copy()->addWeek()->format('Y-m-d H:i'));
});

test('pula ocorrência em conflito sem interromper a série', function () {
    $base = Carbon::now()->addDay()->setTime(11, 0);

    // Ocupa a 3ª ocorrência (2 semanas à frente)
    $conflito = $base->copy()->addWeeks(2);
    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id, 'manicure_id' => $this->manicure->id,
        'data_hora_inicio' => $conflito, 'data_hora_fim' => $conflito->copy()->addMinutes(30),
        'status'           => 'confirmado',
    ]);

    $res = $this->agendaService->criarRecorrente([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $base->toDateTimeString(),
        'origem'           => 'balcao',
        'status'           => 'confirmado',
    ], 'semanal', 4);

    expect($res['criados'])->toHaveCount(3);
    expect($res['pulados'])->toHaveCount(1)
        ->and($res['pulados'][0])->toBe($conflito->format('d/m/Y H:i'));

    // Série parcial: 4 solicitadas − 1 conflito pré-existente = 3 criados + 1 prévio
    expect(Agendamento::count())->toBe(4);

    $datasCriadas = collect($res['criados'])
        ->map(fn ($a) => $a->data_hora_inicio->format('Y-m-d H:i'))
        ->all();

    expect($datasCriadas)->toBe([
        $base->format('Y-m-d H:i'),
        $base->copy()->addWeek()->format('Y-m-d H:i'),
        $base->copy()->addWeeks(3)->format('Y-m-d H:i'),
    ]);
});

test('série parcial reporta múltiplas datas em conflito', function () {
    $base = Carbon::now()->addDay()->setTime(14, 0);

    $conflitos = [
        $base->copy()->addWeeks(1),
        $base->copy()->addWeeks(3),
    ];

    foreach ($conflitos as $conflito) {
        Agendamento::factory()->create([
            'salao_id'         => $this->salao->id,
            'manicure_id'      => $this->manicure->id,
            'data_hora_inicio' => $conflito,
            'data_hora_fim'    => $conflito->copy()->addMinutes(30),
            'status'           => 'confirmado',
        ]);
    }

    $res = $this->agendaService->criarRecorrente([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $base->toDateTimeString(),
        'origem'           => 'balcao',
        'status'           => 'confirmado',
    ], 'semanal', 5);

    expect($res['criados'])->toHaveCount(3);
    expect($res['pulados'])->toBe([
        $conflitos[0]->format('d/m/Y H:i'),
        $conflitos[1]->format('d/m/Y H:i'),
    ]);
});

test('limita recorrência a no máximo 12 ocorrências', function () {
    $base = Carbon::now()->addDay()->setTime(9, 0);

    $res = $this->agendaService->criarRecorrente([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $base->toDateTimeString(),
        'origem'           => 'balcao',
        'status'           => 'confirmado',
    ], 'semanal', 20);

    expect($res['criados'])->toHaveCount(12);
    expect($res['pulados'])->toBeEmpty();
    expect(Agendamento::count())->toBe(12);
});
