<?php

use App\Models\Cliente;
use App\Models\ConfiguracaoSalao;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create();
    ConfiguracaoSalao::create(['salao_id' => $this->salao->id]);
    $this->dono = User::factory()->create([
        'role' => 'dono', 'salao_id' => $this->salao->id, 'ativo' => true,
    ]);
});

test('dono lista clientes do salão', function () {
    Cliente::factory()->count(3)->create(['salao_id' => $this->salao->id]);
    Cliente::factory()->create(); // outro salão

    $r = $this->actingAs($this->dono)->get('/dono/clientes');
    $r->assertOk();
    expect($r->viewData('clientes')->total())->toBe(3);
});

test('dono busca cliente por nome', function () {
    Cliente::factory()->create(['salao_id' => $this->salao->id, 'nome' => 'Joana Silva']);
    Cliente::factory()->create(['salao_id' => $this->salao->id, 'nome' => 'Maria Santos']);

    $r = $this->actingAs($this->dono)->get('/dono/clientes?search=Joana');
    expect($r->viewData('clientes')->total())->toBe(1);
});

test('dono cadastra novo cliente', function () {
    $this->actingAs($this->dono)->from('/dono/clientes/create')->post('/dono/clientes', [
        'nome'     => 'Nova Cliente',
        'email'    => 'nova@cliente.com',
        'telefone' => '11999998888',
    ])->assertRedirect('/dono/clientes');

    $this->assertDatabaseHas('clientes', [
        'nome'     => 'Nova Cliente',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
});

test('cliente com email inválido falha', function () {
    $this->actingAs($this->dono)->from('/dono/clientes/create')->post('/dono/clientes', [
        'nome'  => 'X',
        'email' => 'nao-eh-email',
    ])->assertSessionHasErrors(['email']);
});

test('cliente com data_nascimento no futuro falha', function () {
    $this->actingAs($this->dono)->from('/dono/clientes/create')->post('/dono/clientes', [
        'nome'            => 'X',
        'data_nascimento' => now()->addYear()->toDateString(),
    ])->assertSessionHasErrors(['data_nascimento']);
});

test('dono visualiza cliente', function () {
    $cli = Cliente::factory()->create(['salao_id' => $this->salao->id]);

    $this->actingAs($this->dono)->get("/dono/clientes/{$cli->id}")->assertOk();
});

test('dono edita cliente', function () {
    $cli = Cliente::factory()->create(['salao_id' => $this->salao->id, 'nome' => 'Antigo']);

    $this->actingAs($this->dono)->from('/dono/clientes')->put("/dono/clientes/{$cli->id}", [
        'nome' => 'Novo Nome',
    ])->assertRedirect('/dono/clientes');

    expect($cli->fresh()->nome)->toBe('Novo Nome');
});

test('dono desativa cliente (destroy)', function () {
    $cli = Cliente::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);

    $this->actingAs($this->dono)->from('/dono/clientes')->delete("/dono/clientes/{$cli->id}")
        ->assertRedirect();

    expect($cli->fresh()->ativo)->toBeFalse();
});

test('dono A não pode ver cliente do salão B', function () {
    $outroSalao = Salao::factory()->create();
    $cliOutro = Cliente::factory()->create(['salao_id' => $outroSalao->id]);

    $this->actingAs($this->dono)->get("/dono/clientes/{$cliOutro->id}")
        ->assertStatus(403);
});

test('dono filtra clientes por segmento VIP', function () {
    Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'nome'          => 'Vip Ada',
        'total_gasto'   => 800,
        'total_visitas' => 2,
    ]);
    Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'nome'          => 'Comum Bea',
        'total_gasto'   => 20,
        'total_visitas' => 1,
    ]);

    $r = $this->actingAs($this->dono)->get('/dono/clientes?segmento=vip');
    $r->assertOk();
    expect($r->viewData('clientes')->total())->toBe(1);
    expect($r->viewData('segmentoAtual'))->toBe('vip');
});

test('show do cliente inclui métricas CRM', function () {
    $cli = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'total_gasto'   => 200,
        'total_visitas' => 2,
    ]);

    $r = $this->actingAs($this->dono)->get("/dono/clientes/{$cli->id}");
    $r->assertOk();
    $metricas = $r->viewData('metricas');
    expect($metricas)->toHaveKeys(['ticket_medio', 'ltv', 'ultima_visita', 'proxima_visita', 'segmentos']);
});

test('dono gera cupom de reativação para cliente inativo', function () {
    $cli = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'total_visitas' => 0,
        'created_at'    => now()->subDays(90),
    ]);

    $this->actingAs($this->dono)
        ->from("/dono/clientes/{$cli->id}")
        ->post(route('dono.clientes.reativar', $cli))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('cupons', [
        'salao_id' => $this->salao->id,
        'codigo'   => 'REATIVA-'.$cli->id.'-'.now()->format('Ym'),
    ]);
});

test('dono não gera cupom de reativação para cliente recente', function () {
    $cli = Cliente::factory()->create([
        'salao_id'   => $this->salao->id,
        'created_at' => now()->subDays(2),
    ]);

    $this->actingAs($this->dono)
        ->from("/dono/clientes/{$cli->id}")
        ->post(route('dono.clientes.reativar', $cli))
        ->assertSessionHasErrors();
});
