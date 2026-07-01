<?php

use App\Models\Produto;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->dono = User::factory()->create(['role' => 'dono', 'salao_id' => $this->salao->id, 'ativo' => true]);
});

function novoProduto(int $salaoId, array $attrs = []): Produto
{
    return Produto::create(array_merge([
        'salao_id'       => $salaoId,
        'nome'           => 'Esmalte',
        'preco_venda'    => 10,
        'estoque_atual'  => 5,
        'estoque_minimo' => 2,
        'unidade'        => 'un',
        'ativo'          => true,
    ], $attrs));
}

test('dono cadastra produto e o estoque inicial vira movimentação de entrada', function () {
    $this->actingAs($this->dono)->post('/dono/produtos', [
        'nome'           => 'Esmalte Rosa',
        'preco_venda'    => 12.90,
        'estoque_atual'  => 10,
        'estoque_minimo' => 3,
        'unidade'        => 'un',
    ])->assertRedirect('/dono/produtos');

    $produto = Produto::where('nome', 'Esmalte Rosa')->first();
    expect($produto)->not->toBeNull();
    expect((float) $produto->estoque_atual)->toBe(10.0);
    expect($produto->salao_id)->toBe($this->salao->id);
    $this->assertDatabaseHas('estoque_movimentacoes', [
        'produto_id' => $produto->id,
        'tipo'       => 'entrada',
    ]);
});

test('entrada, saída e ajuste atualizam o estoque corretamente', function () {
    $produto = novoProduto($this->salao->id, ['estoque_atual' => 5]);

    $this->actingAs($this->dono)->post("/dono/produtos/{$produto->id}/estoque", ['tipo' => 'entrada', 'quantidade' => 3])->assertRedirect();
    expect((float) $produto->fresh()->estoque_atual)->toBe(8.0);

    $this->actingAs($this->dono)->post("/dono/produtos/{$produto->id}/estoque", ['tipo' => 'saida', 'quantidade' => 2])->assertRedirect();
    expect((float) $produto->fresh()->estoque_atual)->toBe(6.0);

    $this->actingAs($this->dono)->post("/dono/produtos/{$produto->id}/estoque", ['tipo' => 'ajuste', 'quantidade' => 20])->assertRedirect();
    expect((float) $produto->fresh()->estoque_atual)->toBe(20.0);
});

test('saída não deixa o estoque negativo', function () {
    $produto = novoProduto($this->salao->id, ['estoque_atual' => 2]);

    $this->actingAs($this->dono)->post("/dono/produtos/{$produto->id}/estoque", ['tipo' => 'saida', 'quantidade' => 5])->assertRedirect();
    expect((float) $produto->fresh()->estoque_atual)->toBe(0.0);
});

test('estoque_baixo sinaliza quando no mínimo ou abaixo', function () {
    $baixo = novoProduto($this->salao->id, ['estoque_atual' => 2, 'estoque_minimo' => 3]);
    $ok    = novoProduto($this->salao->id, ['estoque_atual' => 10, 'estoque_minimo' => 3]);

    expect($baixo->estoque_baixo)->toBeTrue();
    expect($ok->estoque_baixo)->toBeFalse();
});

test('dono não acessa nem movimenta produto de outro salão', function () {
    $outro = Salao::factory()->create();
    $produto = novoProduto($outro->id);

    $this->actingAs($this->dono)->get("/dono/produtos/{$produto->id}/edit")->assertForbidden();
    $this->actingAs($this->dono)
        ->post("/dono/produtos/{$produto->id}/estoque", ['tipo' => 'entrada', 'quantidade' => 1])
        ->assertForbidden();
});

test('cliente não acessa o módulo de produtos', function () {
    $cliente = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->actingAs($cliente)->get('/dono/produtos')->assertForbidden();
});
