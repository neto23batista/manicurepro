<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ConfiguracaoSalao;
use App\Models\DisponibilidadeManicure;
use App\Models\HorarioFuncionamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\User;
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

    $this->userCliente = User::factory()->create(['role' => 'cliente']);
    $this->cliente = Cliente::create([
        'user_id' => $this->userCliente->id,
        'salao_id' => $this->salao->id,
        'nome' => $this->userCliente->name,
        'email' => $this->userCliente->email,
    ]);
});

function criarAgendamentoBase($self, Carbon $inicio, string $status = 'confirmado'): Agendamento
{
    $ag = Agendamento::factory()->create([
        'salao_id' => $self->salao->id,
        'cliente_id' => $self->cliente->id,
        'manicure_id' => $self->manicure->id,
        'user_id' => $self->userCliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim' => $inicio->copy()->addMinutes(30),
        'status' => $status,
    ]);
    $ag->servicos()->attach($self->servico->id, ['preco' => 30.00, 'duracao' => 30]);

    return $ag;
}

test('cliente remarca agendamento para novo horário válido', function () {
    $inicio = Carbon::now()->addDays(2)->setTime(10, 0);
    $ag = criarAgendamentoBase($this, $inicio);

    $novo = Carbon::now()->addDays(3)->setTime(14, 0);

    $this->actingAs($this->userCliente)
        ->post(route('cliente.agendamentos.reagendar', $ag), [
            'data_hora_inicio' => $novo->toDateTimeString(),
        ])
        ->assertRedirect(route('cliente.agendamentos.show', $ag));

    $ag->refresh();
    expect($ag->data_hora_inicio->format('Y-m-d H:i'))->toBe($novo->format('Y-m-d H:i'));
    expect($ag->data_hora_fim->format('H:i'))->toBe($novo->copy()->addMinutes(30)->format('H:i'));
});

test('remarcação rejeita horário em conflito com outro agendamento', function () {
    $inicio = Carbon::now()->addDays(2)->setTime(10, 0);
    $ag = criarAgendamentoBase($this, $inicio);

    // Outro agendamento ocupando 14:00 do dia seguinte
    $ocupado = Carbon::now()->addDays(3)->setTime(14, 0);
    criarAgendamentoBase($this, $ocupado);

    $this->actingAs($this->userCliente)
        ->post(route('cliente.agendamentos.reagendar', $ag), [
            'data_hora_inicio' => $ocupado->toDateTimeString(),
        ])
        ->assertSessionHasErrors('error');

    $ag->refresh();
    expect($ag->data_hora_inicio->format('Y-m-d H:i'))->toBe($inicio->format('Y-m-d H:i'));
});

test('agendamento concluído não pode ser remarcado', function () {
    $inicio = Carbon::now()->subDays(2)->setTime(10, 0);
    $ag = criarAgendamentoBase($this, $inicio, 'concluido');

    $novo = Carbon::now()->addDays(3)->setTime(14, 0);

    $this->actingAs($this->userCliente)
        ->post(route('cliente.agendamentos.reagendar', $ag), [
            'data_hora_inicio' => $novo->toDateTimeString(),
        ])
        ->assertSessionHasErrors('error');

    $ag->refresh();
    expect($ag->status)->toBe('concluido');
    expect($ag->data_hora_inicio->format('Y-m-d H:i'))->toBe($inicio->format('Y-m-d H:i'));
});

test('cliente não pode remarcar agendamento de outro cliente', function () {
    $inicio = Carbon::now()->addDays(2)->setTime(10, 0);
    $ag = criarAgendamentoBase($this, $inicio);

    $outro = User::factory()->create(['role' => 'cliente']);

    $novo = Carbon::now()->addDays(3)->setTime(14, 0);

    $this->actingAs($outro)
        ->post(route('cliente.agendamentos.reagendar', $ag), [
            'data_hora_inicio' => $novo->toDateTimeString(),
        ])
        ->assertForbidden();
});
