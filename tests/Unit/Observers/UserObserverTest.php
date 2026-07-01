<?php

use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create();
});

test('observer sincroniza nome no registro Manicure quando User é atualizado', function () {
    $user = User::factory()->create([
        'role'     => 'manicure',
        'salao_id' => $this->salao->id,
        'name'     => 'Antigo Nome',
    ]);
    $manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $user->id,
        'nome'     => 'Antigo Nome',
    ]);

    $user->update(['name' => 'Novo Nome']);

    $manicure->refresh();
    expect($manicure->nome)->toBe('Novo Nome');
});

test('observer sincroniza nome/email/telefone no registro Cliente', function () {
    $user = User::factory()->create([
        'role'  => 'cliente',
        'name'  => 'Cliente Antigo',
        'email' => 'antigo@x.com',
        'phone' => '11999990000',
    ]);
    $cliente = Cliente::create([
        'user_id'  => $user->id,
        'salao_id' => $this->salao->id,
        'nome'     => 'Cliente Antigo',
        'email'    => 'antigo@x.com',
        'telefone' => '11999990000',
    ]);

    $user->update([
        'name'  => 'Cliente Novo',
        'email' => 'novo@x.com',
        'phone' => '11888880000',
    ]);

    $cliente->refresh();
    expect($cliente->nome)->toBe('Cliente Novo');
    expect($cliente->email)->toBe('novo@x.com');
    expect($cliente->telefone)->toBe('11888880000');
});

test('observer não dispara quando campos relevantes não mudam', function () {
    $user = User::factory()->create([
        'role'     => 'manicure',
        'salao_id' => $this->salao->id,
        'name'     => 'Estável',
    ]);
    $manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $user->id,
        'nome'     => 'Estável',
    ]);

    $user->update(['ativo' => false]); // muda campo irrelevante

    $manicure->refresh();
    expect($manicure->nome)->toBe('Estável');
});

test('observer replica avatar do User para foto da Manicure', function () {
    $user = User::factory()->create([
        'role'     => 'manicure',
        'salao_id' => $this->salao->id,
        'avatar'   => null,
    ]);
    $manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $user->id,
        'foto'     => null,
    ]);

    $user->update(['avatar' => 'avatars/test.png']);

    $manicure->refresh();
    expect($manicure->foto)->toBe('avatars/test.png');
});
