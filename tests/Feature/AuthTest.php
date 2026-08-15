<?php

use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('página de login é acessível', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
    $response->assertSee('Fernanda Silva Nails');
});

test('usuário pode fazer login com credenciais corretas', function () {
    $user = User::factory()->create([
        'email'    => 'test@example.com',
        'password' => bcrypt('password123'),
        'role'     => 'cliente',
        'ativo'    => true,
    ]);

    $response = $this->post('/login', [
        'email'    => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('cliente.dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('login falha com credenciais incorretas', function () {
    $response = $this->post('/login', [
        'email'    => 'naoexiste@example.com',
        'password' => 'wrongpass',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('usuário inativo não pode fazer login', function () {
    User::factory()->create([
        'email'    => 'inativo@example.com',
        'password' => bcrypt('password123'),
        'role'     => 'cliente',
        'ativo'    => false,
    ]);

    $response = $this->post('/login', [
        'email'    => 'inativo@example.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('admin é redirecionado para dashboard admin após login', function () {
    $admin = User::factory()->create([
        'role'  => 'admin',
        'ativo' => true,
    ]);

    $response = $this->actingAs($admin)->get('/');
    $this->assertAuthenticated();
});

test('usuário pode fazer logout', function () {
    $user = User::factory()->create(['ativo' => true]);

    $this->actingAs($user)->post('/logout');

    $this->assertGuest();
});

test('registro de novo cliente funciona', function () {
    $salao = Salao::factory()->create(['ativo' => true]);

    $response = $this->post('/register', [
        'name'                  => 'Nova Usuária',
        'email'                 => 'nova@test.com',
        'password'              => 'minhasenha123',
        'password_confirmation' => 'minhasenha123',
        'salao_id'              => $salao->id,
    ]);

    $this->assertDatabaseHas('users', ['email' => 'nova@test.com', 'role' => 'cliente']);
    $response->assertRedirect(route('cliente.dashboard'));
});

test('cliente não pode acessar dashboard admin', function () {
    $cliente = User::factory()->create(['role' => 'cliente', 'ativo' => true]);

    $response = $this->actingAs($cliente)->get('/admin/dashboard');
    $response->assertStatus(403);
});

test('manicure não pode acessar área admin', function () {
    $salao = Salao::factory()->create();
    $manicure = User::factory()->create(['role' => 'manicure', 'salao_id' => $salao->id, 'ativo' => true]);

    $response = $this->actingAs($manicure)->get('/admin/dashboard');
    $response->assertStatus(403);
});
