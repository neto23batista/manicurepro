<?php

use App\Models\CategoriaServico;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'ativo' => true]);
    $this->salao = Salao::factory()->create();
});

test('admin lista categorias', function () {
    CategoriaServico::factory()->count(3)->create(['salao_id' => $this->salao->id]);

    $r = $this->actingAs($this->admin)->get('/admin/categorias');
    $r->assertOk();
    $r->assertViewHas('categorias');
});

test('admin cria categoria', function () {
    $this->actingAs($this->admin)->from('/admin/categorias')->post('/admin/categorias', [
        'salao_id' => $this->salao->id,
        'nome'     => 'Spa dos Pés',
        'ordem'    => 5,
    ])->assertRedirect('/admin/categorias');

    $this->assertDatabaseHas('categorias_servico', ['nome' => 'Spa dos Pés', 'ordem' => 5]);
});

test('criar categoria sem nome falha', function () {
    $this->actingAs($this->admin)->from('/admin/categorias')->post('/admin/categorias', [
        'salao_id' => $this->salao->id,
        'ordem'    => 1,
    ])->assertSessionHasErrors(['nome']);
});

test('admin atualiza categoria', function () {
    $cat = CategoriaServico::factory()->create(['salao_id' => $this->salao->id, 'nome' => 'Antigo']);

    $this->actingAs($this->admin)->from('/admin/categorias')->put("/admin/categorias/{$cat->id}", [
        'salao_id' => $this->salao->id,
        'nome'     => 'Atualizado',
        'ordem'    => 9,
        'ativo'    => '1',
    ])->assertRedirect();

    $this->assertDatabaseHas('categorias_servico', ['id' => $cat->id, 'nome' => 'Atualizado', 'ordem' => 9]);
});

test('admin exclui categoria sem serviços', function () {
    $cat = CategoriaServico::factory()->create(['salao_id' => $this->salao->id]);

    $this->actingAs($this->admin)->from('/admin/categorias')->delete("/admin/categorias/{$cat->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('categorias_servico', ['id' => $cat->id]);
});

test('admin NÃO pode excluir categoria com serviços vinculados', function () {
    $cat = CategoriaServico::factory()->create(['salao_id' => $this->salao->id]);
    Servico::factory()->create(['salao_id' => $this->salao->id, 'categoria_id' => $cat->id]);

    $this->actingAs($this->admin)->from('/admin/categorias')->delete("/admin/categorias/{$cat->id}")
        ->assertSessionHasErrors(['error']);

    $this->assertDatabaseHas('categorias_servico', ['id' => $cat->id]);
});

test('não-admin não pode acessar categorias', function () {
    $cliente = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->actingAs($cliente)->get('/admin/categorias')->assertStatus(403);
});
