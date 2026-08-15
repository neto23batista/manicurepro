<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('login api retorna token e user com shape consistente', function () {
    $user = User::factory()->create([
        'email'    => 'api@example.com',
        'password' => Hash::make('senha12345'),
        'role'     => 'cliente',
        'ativo'    => true,
        'phone'    => '11999990000',
    ]);

    $this->postJson('/api/v1/login', [
        'email'       => 'api@example.com',
        'password'    => 'senha12345',
        'device_name' => 'pest',
    ])
        ->assertOk()
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'name', 'email', 'role', 'phone', 'ativo'],
        ])
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.ativo', true);
});

test('login api rejeita usuário inativo', function () {
    User::factory()->create([
        'email'    => 'inativo-api@example.com',
        'password' => Hash::make('senha12345'),
        'ativo'    => false,
    ]);

    $this->postJson('/api/v1/login', [
        'email'    => 'inativo-api@example.com',
        'password' => 'senha12345',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('token de usuário inativo é rejeitado e revogado', function () {
    $user = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $token = $user->createToken('t')->plainTextToken;

    $user->update(['ativo' => false]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Conta inativa.');

    expect($user->fresh()->tokens()->count())->toBe(0);
});

test('me retorna o mesmo shape de user do login', function () {
    $user = User::factory()->create([
        'role'  => 'cliente',
        'ativo' => true,
        'phone' => '11988887777',
    ]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'role', 'phone', 'ativo'],
        ])
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.phone', '11988887777');
});

test('logout invalida o token atual', function () {
    $user = User::factory()->create(['ativo' => true]);
    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Logout realizado.');

    expect($user->fresh()->tokens()->count())->toBe(0);

    // Nova instância de auth: o guard de teste pode manter o user do request anterior.
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/me')
        ->assertUnauthorized();
});

test('troca de senha no perfil revoga todos os tokens sanctum', function () {
    $user = User::factory()->create([
        'ativo'    => true,
        'password' => Hash::make('atual12345'),
    ]);
    $user->createToken('mobile');
    $user->createToken('web');

    expect($user->tokens()->count())->toBe(2);

    $this->actingAs($user)->from('/perfil')->put('/perfil', [
        'name'                  => $user->name,
        'email'                 => $user->email,
        'current_password'      => 'atual12345',
        'password'              => 'nova123456',
        'password_confirmation' => 'nova123456',
    ])->assertRedirect();

    expect($user->fresh()->tokens()->count())->toBe(0);
    expect(Hash::check('nova123456', $user->fresh()->password))->toBeTrue();
});

test('atualização de perfil sem senha não revoga tokens', function () {
    $user = User::factory()->create(['ativo' => true]);
    $user->createToken('mobile');

    $this->actingAs($user)->from('/perfil')->put('/perfil', [
        'name'  => 'Nome Novo',
        'email' => $user->email,
    ])->assertRedirect();

    expect($user->fresh()->tokens()->count())->toBe(1);
});

test('cancelar via api retorna AgendamentoResource com message', function () {
    $salao = Salao::factory()->create(['ativo' => true]);
    $manicure = Manicure::factory()->create(['salao_id' => $salao->id, 'ativo' => true]);
    $user = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $cliente = Cliente::factory()->create([
        'salao_id' => $salao->id,
        'user_id'  => $user->id,
    ]);

    $inicio = Carbon::now()->addDay()->setTime(10, 0);
    $agendamento = Agendamento::factory()->create([
        'salao_id'         => $salao->id,
        'manicure_id'      => $manicure->id,
        'cliente_id'       => $cliente->id,
        'user_id'          => $user->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => 'confirmado',
        'nome_cliente'     => $user->name,
    ]);

    $token = $user->createToken('t')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/agendamentos/'.$agendamento->id.'/cancelar')
        ->assertOk()
        ->assertJsonPath('message', 'Agendamento cancelado com sucesso.')
        ->assertJsonPath('data.id', $agendamento->id)
        ->assertJsonPath('data.status', 'cancelado')
        ->assertJsonStructure([
            'data' => [
                'id', 'status', 'status_label', 'status_color',
                'data_hora_inicio', 'data_hora_fim',
                'salao'    => ['id', 'slug', 'nome', 'endereco'],
                'manicure' => ['id', 'nome', 'foto_url', 'nota_media'],
                'servicos',
                'criado_em',
            ],
        ]);
});
