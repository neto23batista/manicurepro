<?php

use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
});

test('admin acessa painel do dono mas não cliente nem manicure', function () {
    $admin = User::factory()->admin()->create(['salao_id' => null]);

    $this->actingAs($admin)->get(route('dono.dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('cliente.dashboard'))->assertForbidden();
    $this->actingAs($admin)->get(route('manicure.dashboard'))->assertForbidden();
});

test('dono acessa operação do salão mas não cliente nem manicure', function () {
    $dono = User::factory()->dono()->create(['salao_id' => $this->salao->id]);

    $this->actingAs($dono)->get(route('dono.dashboard'))->assertOk();
    $this->actingAs($dono)->get(route('dono.financeiro.index'))->assertOk();
    $this->actingAs($dono)->get(route('cliente.dashboard'))->assertForbidden();
    $this->actingAs($dono)->get(route('manicure.dashboard'))->assertForbidden();
    $this->actingAs($dono)->get(route('admin.dashboard'))->assertForbidden();
});

test('dono é proibido em rotas do cliente (hierarquia sem herança cliente)', function (string $uri) {
    $dono = User::factory()->dono()->create(['salao_id' => $this->salao->id]);

    $this->actingAs($dono)->get($uri)->assertForbidden();
})->with([
    '/cliente/dashboard',
    '/cliente/agendamentos',
    '/cliente/agendamentos/novo',
    '/cliente/lista-espera',
    '/cliente/fidelidade',
]);

test('atendente acessa operação mas não caixa exclusivo do dono', function () {
    $atendente = User::factory()->create([
        'role' => 'atendente',
        'salao_id' => $this->salao->id,
    ]);

    $this->actingAs($atendente)->get(route('dono.dashboard'))->assertOk();
    $this->actingAs($atendente)->get(route('dono.financeiro.index'))->assertForbidden();
    $this->actingAs($atendente)->get(route('cliente.dashboard'))->assertForbidden();
});

test('manicure não acessa financeiro nem demais rotas do dono', function () {
    $manicure = User::factory()->manicure()->create(['salao_id' => $this->salao->id]);

    $this->actingAs($manicure)->get(route('manicure.dashboard'))->assertOk();
    $this->actingAs($manicure)->get('/dono/financeiro')->assertForbidden();
    $this->actingAs($manicure)->get('/dono/vales')->assertForbidden();
    $this->actingAs($manicure)->get('/dono/configuracao')->assertForbidden();
    $this->actingAs($manicure)->get('/dono/dashboard')->assertForbidden();
    $this->actingAs($manicure)->get('/dono/agendamentos')->assertForbidden();
});

test('manicure e cliente ficam restritos às próprias áreas', function () {
    $manicure = User::factory()->manicure()->create(['salao_id' => $this->salao->id]);
    $cliente = User::factory()->cliente()->create();

    $this->actingAs($manicure)->get(route('manicure.dashboard'))->assertOk();
    $this->actingAs($manicure)->get(route('cliente.dashboard'))->assertForbidden();
    $this->actingAs($manicure)->get(route('dono.dashboard'))->assertForbidden();

    $this->actingAs($cliente)->get(route('cliente.dashboard'))->assertOk();
    $this->actingAs($cliente)->get(route('manicure.dashboard'))->assertForbidden();
    $this->actingAs($cliente)->get(route('dono.dashboard'))->assertForbidden();
});
