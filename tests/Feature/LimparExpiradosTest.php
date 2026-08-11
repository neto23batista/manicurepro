<?php

use App\Models\Agendamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\SlotHold;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
});

test('marca como não compareceu agendamentos abertos com fim há mais de 2h', function () {
    $fim = Carbon::now()->subHours(3);

    $noShow = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'data_hora_inicio' => $fim->copy()->subMinutes(30),
        'data_hora_fim'    => $fim,
        'status'           => 'confirmado',
    ]);

    $emAndamento = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'data_hora_inicio' => $fim->copy()->subHour(),
        'data_hora_fim'    => $fim->copy()->subMinutes(10),
        'status'           => 'em_andamento',
    ]);

    $this->artisan('manicure:limpar-expirados')->assertSuccessful();

    expect($noShow->fresh()->status)->toBe('nao_compareceu');
    expect($emAndamento->fresh()->status)->toBe('nao_compareceu');
});

test('não altera agendamentos recentes, concluídos ou cancelados', function () {
    $recente = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'data_hora_inicio' => Carbon::now()->subMinutes(30),
        'data_hora_fim'    => Carbon::now()->subMinutes(5),
        'status'           => 'confirmado',
    ]);

    $concluido = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'data_hora_inicio' => Carbon::now()->subHours(5),
        'data_hora_fim'    => Carbon::now()->subHours(4),
        'status'           => 'concluido',
    ]);

    $cancelado = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'data_hora_inicio' => Carbon::now()->subHours(5),
        'data_hora_fim'    => Carbon::now()->subHours(4),
        'status'           => 'cancelado',
    ]);

    $this->artisan('manicure:limpar-expirados')->assertSuccessful();

    expect($recente->fresh()->status)->toBe('confirmado');
    expect($concluido->fresh()->status)->toBe('concluido');
    expect($cancelado->fresh()->status)->toBe('cancelado');
});

test('é idempotente ao marcar no-shows', function () {
    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'data_hora_inicio' => Carbon::now()->subHours(5),
        'data_hora_fim'    => Carbon::now()->subHours(4),
        'status'           => 'aguardando',
    ]);

    $this->artisan('manicure:limpar-expirados')->assertSuccessful();
    $this->artisan('manicure:limpar-expirados')
        ->assertSuccessful()
        ->expectsOutputToContain('Agendamentos expirados marcados: 0');
});

test('remove apenas holds expirados', function () {
    SlotHold::create([
        'manicure_id'      => $this->manicure->id,
        'data_hora_inicio' => Carbon::now()->addDay()->setTime(10, 0),
        'data_hora_fim'    => Carbon::now()->addDay()->setTime(10, 30),
        'token'            => 'expirado',
        'expires_at'       => Carbon::now()->subMinute(),
    ]);

    SlotHold::create([
        'manicure_id'      => $this->manicure->id,
        'data_hora_inicio' => Carbon::now()->addDay()->setTime(11, 0),
        'data_hora_fim'    => Carbon::now()->addDay()->setTime(11, 30),
        'token'            => 'ativo',
        'expires_at'       => Carbon::now()->addMinutes(10),
    ]);

    $this->artisan('manicure:limpar-expirados')->assertSuccessful();

    expect(SlotHold::count())->toBe(1);
    expect(SlotHold::first()->token)->toBe('ativo');
});
