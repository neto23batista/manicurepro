<?php

use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);

    $this->user = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->cliente = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $this->user->id,
    ]);

    $this->outroUser = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->outroCliente = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $this->outroUser->id,
    ]);

    $inicio = Carbon::now()->subDay()->setTime(10, 0);
    $this->agendamento = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'user_id'          => $this->user->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => 'concluido',
        'nome_cliente'     => $this->user->name,
    ]);
});

test('cliente dono pode avaliar agendamento concluído via API', function () {
    $token = $this->user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/agendamentos/'.$this->agendamento->id.'/avaliar', [
            'nota'       => 5,
            'comentario' => 'Excelente atendimento',
        ])
        ->assertCreated()
        ->assertJsonPath('message', 'Avaliação registrada com sucesso.')
        ->assertJsonPath('avaliacao.nota', 5)
        ->assertJsonPath('avaliacao.comentario', 'Excelente atendimento');

    $this->assertDatabaseHas('avaliacoes', [
        'agendamento_id' => $this->agendamento->id,
        'cliente_id'     => $this->cliente->id,
        'manicure_id'    => $this->manicure->id,
        'salao_id'       => $this->salao->id,
        'nota'           => 5,
        'comentario'     => 'Excelente atendimento',
    ]);
});

test('não autenticado não pode avaliar', function () {
    $this->postJson('/api/v1/agendamentos/'.$this->agendamento->id.'/avaliar', [
        'nota' => 5,
    ])->assertUnauthorized();
});

test('cliente não dono não pode avaliar agendamento de outro via API', function () {
    $token = $this->outroUser->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/agendamentos/'.$this->agendamento->id.'/avaliar', [
            'nota' => 4,
        ])
        ->assertForbidden();

    expect(Avaliacao::where('agendamento_id', $this->agendamento->id)->exists())->toBeFalse();
});

test('só permite avaliar agendamento concluído', function () {
    $this->agendamento->update(['status' => 'confirmado']);
    $token = $this->user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/agendamentos/'.$this->agendamento->id.'/avaliar', [
            'nota' => 5,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Só é possível avaliar agendamentos concluídos.');
});

test('não permite avaliar duas vezes o mesmo agendamento', function () {
    Avaliacao::create([
        'agendamento_id' => $this->agendamento->id,
        'cliente_id'     => $this->cliente->id,
        'manicure_id'    => $this->manicure->id,
        'salao_id'       => $this->salao->id,
        'nota'           => 4,
    ]);

    $token = $this->user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/agendamentos/'.$this->agendamento->id.'/avaliar', [
            'nota' => 5,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Você já avaliou este atendimento.');

    expect(Avaliacao::where('agendamento_id', $this->agendamento->id)->count())->toBe(1);
});

test('valida nota obrigatória entre 1 e 5', function () {
    $token = $this->user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/agendamentos/'.$this->agendamento->id.'/avaliar', [
            'nota' => 6,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nota']);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/agendamentos/'.$this->agendamento->id.'/avaliar', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nota']);
});

test('manicure não avalia o próprio atendimento via API', function () {
    $userManicure = User::factory()->create([
        'role'     => 'manicure',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
    $this->manicure->update(['user_id' => $userManicure->id]);

    $token = $userManicure->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/agendamentos/'.$this->agendamento->id.'/avaliar', [
            'nota'       => 5,
            'comentario' => 'Autoavaliação',
        ])
        ->assertForbidden();

    expect(Avaliacao::where('agendamento_id', $this->agendamento->id)->exists())->toBeFalse();
});

test('dono não avalia agendamento do salão via API', function () {
    $dono = User::factory()->create([
        'role'     => 'dono',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
    $token = $dono->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/agendamentos/'.$this->agendamento->id.'/avaliar', [
            'nota' => 5,
        ])
        ->assertForbidden();
});
