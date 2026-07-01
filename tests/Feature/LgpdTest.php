<?php

use App\Models\Cliente;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create();
    $this->user = User::factory()->create(['role' => 'cliente', 'password' => bcrypt('senha123')]);
    $this->cliente = Cliente::factory()->create(['user_id' => $this->user->id, 'salao_id' => $this->salao->id]);
});

test('cliente exporta seus dados em JSON', function () {
    $r = $this->actingAs($this->user)->get(route('perfil.exportar'));

    $r->assertOk();
    $r->assertHeader('content-disposition');
    expect($r->json('usuario.email'))->toBe($this->user->email);
    expect($r->json())->toHaveKeys(['usuario', 'cliente', 'agendamentos', 'fidelidade']);
});

test('cliente exclui a conta confirmando a senha', function () {
    $this->actingAs($this->user)
        ->delete(route('perfil.conta.destroy'), ['password' => 'senha123'])
        ->assertRedirect(route('public.index'));

    $this->assertGuest();
    $this->assertSoftDeleted('clientes', ['id' => $this->cliente->id]);
    $this->assertDatabaseMissing('users', ['id' => $this->user->id, 'deleted_at' => null]);
});

test('exclusão exige a senha correta', function () {
    $this->actingAs($this->user)
        ->delete(route('perfil.conta.destroy'), ['password' => 'errada'])
        ->assertSessionHasErrors('password');

    $this->assertDatabaseHas('users', ['id' => $this->user->id]);
});

test('dono não pode autoexcluir a conta', function () {
    $dono = User::factory()->create(['role' => 'dono', 'salao_id' => $this->salao->id, 'password' => bcrypt('senha123')]);

    $this->actingAs($dono)
        ->delete(route('perfil.conta.destroy'), ['password' => 'senha123'])
        ->assertSessionHasErrors('password');

    $this->assertDatabaseHas('users', ['id' => $dono->id]);
});
