<?php

use App\Models\AuditLog;
use App\Models\EstoqueMovimentacao;
use App\Models\Fornecedor;
use App\Models\Produto;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->dono = User::factory()->create(['role' => 'dono', 'salao_id' => $this->salao->id, 'ativo' => true]);
});

function produtoEstoque(int $salaoId, array $attrs = []): Produto
{
    return Produto::create(array_merge([
        'salao_id'       => $salaoId,
        'nome'           => 'Esmalte Teste',
        'preco_custo'    => 5,
        'preco_venda'    => 15,
        'estoque_atual'  => 10,
        'estoque_minimo' => 2,
        'unidade'        => 'un',
        'ativo'          => true,
    ], $attrs));
}

test('dono cadastra fornecedor e vincula ao produto', function () {
    $this->actingAs($this->dono)->post('/dono/fornecedores', [
        'nome'     => 'Distribuidora X',
        'telefone' => '11999990000',
    ])->assertRedirect('/dono/fornecedores');

    $fornecedor = Fornecedor::where('nome', 'Distribuidora X')->first();
    expect($fornecedor)->not->toBeNull();
    expect($fornecedor->salao_id)->toBe($this->salao->id);

    $this->actingAs($this->dono)->post('/dono/produtos', [
        'nome'           => 'Base Coat',
        'preco_venda'    => 40,
        'estoque_atual'  => 5,
        'unidade'        => 'un',
        'fornecedor_id'  => $fornecedor->id,
    ])->assertRedirect('/dono/produtos');

    $produto = Produto::where('nome', 'Base Coat')->first();
    expect($produto->fornecedor_id)->toBe($fornecedor->id);
});

test('dono não edita fornecedor de outro salão', function () {
    $outro = Salao::factory()->create();
    $fornecedor = Fornecedor::create([
        'salao_id' => $outro->id,
        'nome'     => 'Outro',
        'ativo'    => true,
    ]);

    $this->actingAs($this->dono)
        ->get("/dono/fornecedores/{$fornecedor->id}/edit")
        ->assertForbidden();
});

test('perda e consumo interno exigem motivo e baixam estoque', function () {
    $produto = produtoEstoque($this->salao->id, ['estoque_atual' => 10]);

    $this->actingAs($this->dono)
        ->post("/dono/produtos/{$produto->id}/estoque", [
            'tipo'       => 'perda',
            'quantidade' => 2,
        ])
        ->assertSessionHasErrors('motivo');

    expect((float) $produto->fresh()->estoque_atual)->toBe(10.0);

    $this->actingAs($this->dono)
        ->post("/dono/produtos/{$produto->id}/estoque", [
            'tipo'       => 'perda',
            'quantidade' => 2,
            'motivo'     => 'Frasco quebrado',
        ])
        ->assertRedirect();

    expect((float) $produto->fresh()->estoque_atual)->toBe(8.0);

    $this->actingAs($this->dono)
        ->post("/dono/produtos/{$produto->id}/estoque", [
            'tipo'       => 'consumo_interno',
            'quantidade' => 1,
            'motivo'     => 'Uso no salão',
        ])
        ->assertRedirect();

    expect((float) $produto->fresh()->estoque_atual)->toBe(7.0);
});

test('devolucao soma estoque com motivo obrigatório', function () {
    $produto = produtoEstoque($this->salao->id, ['estoque_atual' => 5]);

    $this->actingAs($this->dono)
        ->post("/dono/produtos/{$produto->id}/estoque", [
            'tipo'       => 'devolucao',
            'quantidade' => 3,
            'motivo'     => 'Cliente devolveu lacrado',
        ])
        ->assertRedirect();

    expect((float) $produto->fresh()->estoque_atual)->toBe(8.0);
    $this->assertDatabaseHas('estoque_movimentacoes', [
        'produto_id' => $produto->id,
        'tipo'       => 'devolucao',
    ]);
});

test('saida acima do estoque disponível é rejeitada sem alterar saldo', function () {
    $produto = produtoEstoque($this->salao->id, ['estoque_atual' => 5]);

    $this->actingAs($this->dono)
        ->post("/dono/produtos/{$produto->id}/estoque", [
            'tipo'       => 'saida',
            'quantidade' => 10,
        ])
        ->assertSessionHasErrors('quantidade');

    expect((float) $produto->fresh()->estoque_atual)->toBe(5.0);
    expect(EstoqueMovimentacao::where('produto_id', $produto->id)->count())->toBe(0);
});

test('inventario gera ajustes e registra auditoria', function () {
    $a = produtoEstoque($this->salao->id, ['nome' => 'A', 'estoque_atual' => 10]);
    $b = produtoEstoque($this->salao->id, ['nome' => 'B', 'estoque_atual' => 4]);

    $this->actingAs($this->dono)
        ->post('/dono/estoque/inventario', [
            'contagens' => [
                $a->id => 8,
                $b->id => 4,
            ],
        ])
        ->assertRedirect(route('dono.estoque.inventario.create'));

    expect((float) $a->fresh()->estoque_atual)->toBe(8.0);
    expect((float) $b->fresh()->estoque_atual)->toBe(4.0);

    expect(EstoqueMovimentacao::where('produto_id', $a->id)->where('tipo', 'ajuste')->count())->toBe(1);
    expect(EstoqueMovimentacao::where('produto_id', $b->id)->where('tipo', 'ajuste')->count())->toBe(0);

    expect(AuditLog::where('action', 'estoque.inventario')->exists())->toBeTrue();
});

test('relatorio mostra margem giro e exporta csv', function () {
    $produto = produtoEstoque($this->salao->id, [
        'nome'          => 'Top Coat',
        'preco_custo'   => 10,
        'preco_venda'   => 40,
        'estoque_atual' => 5,
    ]);

    EstoqueMovimentacao::create([
        'produto_id' => $produto->id,
        'salao_id'   => $this->salao->id,
        'user_id'    => $this->dono->id,
        'tipo'       => 'saida',
        'quantidade' => 10,
        'motivo'     => 'venda',
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    $this->actingAs($this->dono)
        ->get('/dono/estoque/relatorio')
        ->assertOk()
        ->assertSee('Top Coat')
        ->assertSee('75,0%') // margem (40-10)/40
        ->assertSee('Giro');

    $csv = $this->actingAs($this->dono)
        ->get('/dono/estoque/relatorio/csv')
        ->assertOk()
        ->assertHeader('content-disposition');

    expect($csv->streamedContent())->toContain('Top Coat');
});

test('cliente nao acessa fornecedores nem inventario', function () {
    $cliente = User::factory()->create(['role' => 'cliente', 'ativo' => true]);

    $this->actingAs($cliente)->get('/dono/fornecedores')->assertForbidden();
    $this->actingAs($cliente)->get('/dono/estoque/inventario')->assertForbidden();
    $this->actingAs($cliente)->get('/dono/estoque/relatorio')->assertForbidden();
});

test('manicure nao acessa fornecedores nem inventario', function () {
    $manicure = User::factory()->create([
        'role'     => 'manicure',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);

    $this->actingAs($manicure)->get('/dono/fornecedores')->assertForbidden();
    $this->actingAs($manicure)->get('/dono/estoque/inventario')->assertForbidden();
    $this->actingAs($manicure)->put("/dono/fornecedores/1", ['nome' => 'Hack'])->assertForbidden();
});
