<?php

use App\Models\Caixa;
use App\Models\Produto;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->dono = User::factory()->create([
        'role'     => 'dono',
        'salao_id' => $this->salao->id,
    ]);
});

test('dono lê financeiro estoque e caixa via API', function () {
    Produto::create([
        'salao_id'       => $this->salao->id,
        'nome'           => 'Esmalte API',
        'preco_custo'    => 5,
        'preco_venda'    => 15,
        'estoque_atual'  => 5,
        'estoque_minimo' => 1,
        'unidade'        => 'un',
        'ativo'          => true,
    ]);
    Caixa::create([
        'salao_id'      => $this->salao->id,
        'aberto_por'    => $this->dono->id,
        'saldo_inicial' => 50,
        'aberto_em'     => now(),
    ]);

    Sanctum::actingAs($this->dono);

    $this->getJson('/api/v1/financeiro')
        ->assertOk()
        ->assertJsonPath('salao_id', $this->salao->id)
        ->assertJsonStructure(['caixa', 'comissoes', 'fluxo', 'periodo']);

    $this->getJson('/api/v1/estoque')
        ->assertOk()
        ->assertJsonPath('salao_id', $this->salao->id)
        ->assertJsonStructure(['relatorio' => ['itens', 'resumo']]);

    $this->getJson('/api/v1/caixa')
        ->assertOk()
        ->assertJsonPath('salao_id', $this->salao->id)
        ->assertJsonPath('aberto.saldo_inicial', 50);
});

test('cliente e manicure recebem 403 nas rotas ops da API', function () {
    $cliente = User::factory()->create(['role' => 'cliente', 'salao_id' => $this->salao->id]);
    $manicure = User::factory()->create(['role' => 'manicure', 'salao_id' => $this->salao->id]);

    Sanctum::actingAs($cliente);
    $this->getJson('/api/v1/financeiro')->assertForbidden();
    $this->getJson('/api/v1/estoque')->assertForbidden();
    $this->getJson('/api/v1/caixa')->assertForbidden();

    Sanctum::actingAs($manicure);
    $this->getJson('/api/v1/financeiro')->assertForbidden();
});

test('dono não vê caixa de outro salão', function () {
    $outroSalao = Salao::factory()->create(['ativo' => true]);
    $caixaOutro = Caixa::create([
        'salao_id'      => $outroSalao->id,
        'aberto_por'    => $this->dono->id,
        'saldo_inicial' => 10,
        'aberto_em'     => now(),
    ]);

    Sanctum::actingAs($this->dono);
    $this->getJson('/api/v1/caixa/'.$caixaOutro->id)->assertForbidden();
});
