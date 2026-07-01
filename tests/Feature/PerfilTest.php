<?php

use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('usuário acessa página de perfil', function () {
    $user = User::factory()->create(['role' => 'cliente', 'ativo' => true]);

    $this->actingAs($user)->get('/perfil')->assertOk();
});

test('guest não acessa perfil', function () {
    $this->get('/perfil')->assertRedirect('/login');
});

test('usuário atualiza nome e telefone', function () {
    $user = User::factory()->create([
        'role' => 'cliente', 'ativo' => true,
        'name' => 'Antigo', 'email' => 'antigo@x.com',
    ]);

    $this->actingAs($user)->from('/perfil')->put('/perfil', [
        'name'  => 'Novo Nome',
        'email' => $user->email,
        'phone' => '11999998888',
    ])->assertRedirect();

    $user->refresh();
    expect($user->name)->toBe('Novo Nome');
    expect($user->phone)->toBe('11999998888');
});

test('email já em uso por outro usuário é rejeitado', function () {
    User::factory()->create(['email' => 'usado@x.com', 'ativo' => true]);
    $user = User::factory()->create(['email' => 'meu@x.com', 'ativo' => true]);

    $this->actingAs($user)->from('/perfil')->put('/perfil', [
        'name'  => $user->name,
        'email' => 'usado@x.com',
    ])->assertSessionHasErrors(['email']);
});

test('usuário troca senha (com senha atual)', function () {
    $user = User::factory()->create([
        'ativo' => true,
        'password' => Hash::make('atual12345'),
    ]);

    $this->actingAs($user)->from('/perfil')->put('/perfil', [
        'name'     => $user->name,
        'email'    => $user->email,
        'current_password' => 'atual12345',
        'password' => 'nova123456',
        'password_confirmation' => 'nova123456',
    ])->assertRedirect();

    expect(Hash::check('nova123456', $user->fresh()->password))->toBeTrue();
});

test('senha atual incorreta impede troca', function () {
    $user = User::factory()->create([
        'ativo' => true,
        'password' => Hash::make('correta'),
    ]);

    $this->actingAs($user)->from('/perfil')->put('/perfil', [
        'name'     => $user->name,
        'email'    => $user->email,
        'current_password' => 'errada',
        'password' => 'nova123456',
        'password_confirmation' => 'nova123456',
    ])->assertSessionHasErrors(['current_password']);
});

test('upload de avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create(['ativo' => true]);

    $foto = UploadedFile::fake()->image('avatar.png', 200, 200);

    $this->actingAs($user)->from('/perfil')->put('/perfil', [
        'name'   => $user->name,
        'email'  => $user->email,
        'avatar' => $foto,
    ])->assertRedirect();

    expect($user->fresh()->avatar)->not->toBeNull();
});

test('remover avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create(['ativo' => true]);
    $user->update(['avatar' => UploadedFile::fake()->image('a.png')->store('avatars', 'public')]);

    $this->actingAs($user)->from('/perfil')->delete('/perfil/avatar')->assertRedirect();

    expect($user->fresh()->avatar)->toBeNull();
});

test('atualizar perfil sincroniza com registro Manicure', function () {
    $salao = Salao::factory()->create();
    $user = User::factory()->create([
        'role' => 'manicure', 'salao_id' => $salao->id, 'ativo' => true,
        'name' => 'Antigo',
    ]);
    $manicure = Manicure::factory()->create([
        'user_id' => $user->id, 'salao_id' => $salao->id, 'nome' => 'Antigo',
    ]);

    $this->actingAs($user)->from('/perfil')->put('/perfil', [
        'name'  => 'Manicure Atualizada',
        'email' => $user->email,
        'phone' => '11900001111',
    ])->assertRedirect();

    expect($manicure->fresh()->nome)->toBe('Manicure Atualizada');
    expect($manicure->fresh()->telefone)->toBe('11900001111');
});

test('atualizar perfil sincroniza com registro Cliente', function () {
    $salao = Salao::factory()->create();
    $user = User::factory()->create([
        'role' => 'cliente', 'salao_id' => $salao->id, 'ativo' => true,
    ]);
    $cliente = Cliente::factory()->create([
        'user_id' => $user->id, 'salao_id' => $salao->id, 'nome' => 'X',
    ]);

    $this->actingAs($user)->from('/perfil')->put('/perfil', [
        'name'  => 'Cliente Renovada',
        'email' => $user->email,
    ])->assertRedirect();

    expect($cliente->fresh()->nome)->toBe('Cliente Renovada');
});
