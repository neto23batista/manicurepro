<?php

use App\Events\AgendamentoCanceladoEvent;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);

    $this->userA = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->clienteA = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $this->userA->id,
    ]);

    $this->userB = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->clienteB = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $this->userB->id,
    ]);

    $inicio = Carbon::now()->addDay()->setTime(10, 0);
    $this->agendamentoB = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->clienteB->id,
        'user_id'          => $this->userB->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => 'confirmado',
        'nome_cliente'     => $this->userB->name,
    ]);
});

test('cliente A não pode ver agendamento do cliente B via API', function () {
    $token = $this->userA->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/agendamentos/'.$this->agendamentoB->id)
        ->assertForbidden();
});

test('cliente A não pode cancelar agendamento do cliente B via API', function () {
    $this->actingAs($this->userA, 'sanctum')
        ->postJson('/api/v1/agendamentos/'.$this->agendamentoB->id.'/cancelar')
        ->assertForbidden();

    expect($this->agendamentoB->fresh()->status)->toBe('confirmado');
});

test('dono do agendamento pode ver via API', function () {
    $token = $this->userB->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/agendamentos/'.$this->agendamentoB->id)
        ->assertOk()
        ->assertJsonPath('data.id', $this->agendamentoB->id);
});

test('dono do agendamento pode cancelar via API e dispara evento', function () {
    Event::fake([AgendamentoCanceladoEvent::class]);

    $token = $this->userB->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/agendamentos/'.$this->agendamentoB->id.'/cancelar')
        ->assertOk()
        ->assertJsonPath('message', 'Agendamento cancelado com sucesso.')
        ->assertJsonPath('data.id', $this->agendamentoB->id)
        ->assertJsonPath('data.status', 'cancelado');

    expect($this->agendamentoB->fresh()->status)->toBe('cancelado');

    Event::assertDispatched(AgendamentoCanceladoEvent::class, function (AgendamentoCanceladoEvent $e) {
        return $e->agendamento->id === $this->agendamentoB->id;
    });
});

test('cliente A não pode avaliar agendamento do cliente B via API', function () {
    $this->agendamentoB->update(['status' => 'concluido']);

    $token = $this->userA->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/agendamentos/'.$this->agendamentoB->id.'/avaliar', [
            'nota'       => 5,
            'comentario' => 'Intrusão',
        ])
        ->assertForbidden();
});

test('cliente de outro salão não vê agendamento via API (IDOR cross-tenant)', function () {
    $outroSalao = Salao::factory()->create(['ativo' => true]);
    $intruso = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    Cliente::factory()->create([
        'salao_id' => $outroSalao->id,
        'user_id'  => $intruso->id,
    ]);

    $token = $intruso->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/agendamentos/'.$this->agendamentoB->id)
        ->assertForbidden();
});

test('manicure não cria agendamento via API', function () {
    $userManicure = User::factory()->create([
        'role'     => 'manicure',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
    $token = $userManicure->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/agendamentos', [
            'salao_id'         => $this->salao->id,
            'manicure_id'      => $this->manicure->id,
            'servico_ids'      => [1],
            'data_hora_inicio' => now()->addDay()->toIso8601String(),
        ])
        ->assertForbidden();
});
