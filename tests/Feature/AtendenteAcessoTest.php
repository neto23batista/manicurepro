<?php

use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->dono = User::factory()->dono()->create([
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
    $this->atendente = User::factory()->create([
        'role'     => 'atendente',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
});

/**
 * Rotas compartilhadas (role:dono,atendente) — ambos podem GET.
 */
test('matriz atendente vs dono: operação compartilhada', function (string $uri) {
    $this->actingAs($this->atendente)->get($uri)->assertOk();
    $this->actingAs($this->dono)->get($uri)->assertOk();
})->with([
    '/dono/dashboard',
    '/dono/agendamentos',
    '/dono/clientes',
    '/dono/cupons',
    '/dono/produtos',
    '/dono/galeria',
    '/dono/avaliacoes',
    '/dono/folgas',
]);

/**
 * Rotas exclusivas do dono (role:dono) — atendente 403, dono 200.
 */
test('matriz atendente vs dono: caixa exclusivo do dono', function (string $uri) {
    $this->actingAs($this->atendente)->get($uri)->assertForbidden();
    $this->actingAs($this->dono)->get($uri)->assertOk();
})->with([
    '/dono/financeiro',
    '/dono/vales',
    '/dono/configuracao',
    '/dono/auditoria',
]);

test('atendente não acessa financeiro, configuração nem vales', function () {
    $this->actingAs($this->atendente);

    $this->get('/dono/financeiro')->assertForbidden();
    $this->get('/dono/configuracao')->assertForbidden();
    $this->get('/dono/vales')->assertForbidden();
});

test('atendente pode acessar agendamentos', function () {
    $this->actingAs($this->atendente)
        ->get('/dono/agendamentos')
        ->assertOk();
});

test('atendente não acessa painel do cliente nem da manicure', function () {
    $this->actingAs($this->atendente);

    $this->get('/cliente/dashboard')->assertForbidden();
    $this->get('/manicure/dashboard')->assertForbidden();
    $this->get('/admin/dashboard')->assertForbidden();
});
