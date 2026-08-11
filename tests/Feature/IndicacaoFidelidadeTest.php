<?php

use App\Models\Cliente;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['manicure.indicacao.enabled' => true]);

    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->user = User::factory()->create([
        'role' => 'cliente',
        'email_verified_at' => now(),
    ]);
    $this->cliente = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $this->user->id,
        'codigo_indicacao' => 'ABC12345',
    ]);
});

test('página de fidelidade exibe o código de indicação', function () {
    $this->actingAs($this->user)
        ->get(route('cliente.fidelidade.index'))
        ->assertOk()
        ->assertSee('Indique amigas')
        ->assertSee('ABC12345')
        ->assertSee('codigo-indicacao', false);
});

test('página de fidelidade oculta indicação quando desativada', function () {
    config(['manicure.indicacao.enabled' => false]);

    $this->actingAs($this->user)
        ->get(route('cliente.fidelidade.index'))
        ->assertOk()
        ->assertDontSee('Indique amigas')
        ->assertDontSee('ABC12345');
});
