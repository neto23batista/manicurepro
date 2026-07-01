<?php

use App\Events\AgendamentoCanceladoEvent;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ListaEspera;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use App\Notifications\VagaDisponivel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
    $this->userCliente = User::factory()->create(['role' => 'cliente']);
    $this->cliente = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id' => $this->userCliente->id,
    ]);
});

test('cliente entra na lista de espera', function () {
    $this->actingAs($this->userCliente)
        ->post(route('cliente.lista-espera.store'), [
            'salao_id' => $this->salao->id,
            'periodo' => 'tarde',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('listas_espera', [
        'salao_id' => $this->salao->id,
        'user_id' => $this->userCliente->id,
        'status' => 'aguardando',
    ]);
});

test('cancelamento de agendamento avisa quem está na lista de espera', function () {
    $data = Carbon::now()->addDays(2)->setTime(10, 0);

    $entrada = ListaEspera::create([
        'salao_id' => $this->salao->id,
        'manicure_id' => null,
        'cliente_id' => $this->cliente->id,
        'user_id' => $this->userCliente->id,
        'data_preferida' => null,
        'periodo' => 'qualquer',
        'status' => 'aguardando',
    ]);

    $ag = Agendamento::factory()->create([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'data_hora_inicio' => $data,
        'data_hora_fim' => $data->copy()->addMinutes(30),
        'status' => 'confirmado',
    ]);

    AgendamentoCanceladoEvent::dispatch($ag, 'Teste', 'cliente');

    Notification::assertSentTo($this->userCliente, VagaDisponivel::class);
    expect($entrada->fresh()->status)->toBe('notificado');
});

test('inscrição que não casa com o salão não é avisada', function () {
    $outroSalao = Salao::factory()->create(['ativo' => true]);

    ListaEspera::create([
        'salao_id' => $outroSalao->id,
        'user_id' => $this->userCliente->id,
        'periodo' => 'qualquer',
        'status' => 'aguardando',
    ]);

    $data = Carbon::now()->addDay()->setTime(10, 0);
    $ag = Agendamento::factory()->create([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'data_hora_inicio' => $data,
        'data_hora_fim' => $data->copy()->addMinutes(30),
        'status' => 'confirmado',
    ]);

    AgendamentoCanceladoEvent::dispatch($ag, 'Teste', 'cliente');

    Notification::assertNothingSent();
});

test('cliente não remove inscrição de outro', function () {
    $entrada = ListaEspera::create([
        'salao_id' => $this->salao->id,
        'user_id' => $this->userCliente->id,
        'periodo' => 'qualquer',
        'status' => 'aguardando',
    ]);

    $outro = User::factory()->create(['role' => 'cliente']);

    $this->actingAs($outro)
        ->delete(route('cliente.lista-espera.destroy', $entrada))
        ->assertForbidden();
});
