<?php

use App\Models\Folga;
use App\Models\FolgaManicure;
use App\Models\GaleriaFoto;
use App\Models\Manicure;
use App\Models\Produto;
use App\Models\Salao;
use App\Models\User;
use App\Policies\FolgaManicurePolicy;
use App\Policies\FolgaPolicy;
use App\Policies\GaleriaFotoPolicy;
use App\Policies\ProdutoPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salaoA = Salao::factory()->create();
    $this->salaoB = Salao::factory()->create();

    $this->dono = User::factory()->create(['role' => 'dono', 'salao_id' => $this->salaoA->id]);
    $this->admin = User::factory()->create(['role' => 'admin', 'salao_id' => null]);
    $this->cliente = User::factory()->create(['role' => 'cliente']);

    $userManicure = User::factory()->create(['role' => 'manicure', 'salao_id' => $this->salaoA->id]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salaoA->id,
        'user_id'  => $userManicure->id,
    ]);
    $this->userManicure = $userManicure;

    $this->produtoA = Produto::create([
        'salao_id' => $this->salaoA->id,
        'nome' => 'Esmalte',
        'preco_venda' => 10,
        'estoque_atual' => 5,
        'estoque_minimo' => 1,
        'unidade' => 'un',
        'ativo' => true,
    ]);
    $this->produtoB = Produto::create([
        'salao_id' => $this->salaoB->id,
        'nome' => 'Acetona',
        'preco_venda' => 8,
        'estoque_atual' => 3,
        'estoque_minimo' => 1,
        'unidade' => 'un',
        'ativo' => true,
    ]);

    $this->fotoA = GaleriaFoto::create([
        'salao_id' => $this->salaoA->id,
        'caminho' => 'galeria/a.jpg',
        'publicar' => true,
    ]);
    $this->fotoB = GaleriaFoto::create([
        'salao_id' => $this->salaoB->id,
        'caminho' => 'galeria/b.jpg',
        'publicar' => true,
    ]);

    $this->folgaA = Folga::factory()->create(['salao_id' => $this->salaoA->id]);
    $this->folgaB = Folga::factory()->create(['salao_id' => $this->salaoB->id]);

    $outroMan = Manicure::factory()->create(['salao_id' => $this->salaoA->id]);
    $this->folgaManA = FolgaManicure::factory()->create(['manicure_id' => $this->manicure->id]);
    $this->folgaManB = FolgaManicure::factory()->create(['manicure_id' => $outroMan->id]);
});

// ---------- Produto ----------

test('admin pode gerenciar produto via Gate (before)', function () {
    expect($this->admin->can('viewAny', Produto::class))->toBeTrue();
    expect($this->admin->can('update', $this->produtoA))->toBeTrue();
    expect($this->admin->can('update', $this->produtoB))->toBeTrue();
});

test('dono só atualiza produto do próprio salão', function () {
    $policy = new ProdutoPolicy();
    expect($policy->update($this->dono, $this->produtoA))->toBeTrue();
    expect($policy->update($this->dono, $this->produtoB))->toBeFalse();
});

test('cliente não acessa produtos', function () {
    expect($this->cliente->can('viewAny', Produto::class))->toBeFalse();
    expect($this->cliente->can('update', $this->produtoA))->toBeFalse();
});

test('manicure não gerencia produto nem fornecedor do próprio salão', function () {
    $fornecedor = \App\Models\Fornecedor::create([
        'salao_id' => $this->salaoA->id,
        'nome'     => 'Fornecedor A',
        'ativo'    => true,
    ]);

    expect($this->userManicure->can('viewAny', Produto::class))->toBeFalse();
    expect($this->userManicure->can('update', $this->produtoA))->toBeFalse();
    expect($this->userManicure->can('viewAny', \App\Models\Fornecedor::class))->toBeFalse();
    expect($this->userManicure->can('update', $fornecedor))->toBeFalse();
});

// ---------- GaleriaFoto ----------

test('admin pode gerenciar galeria via Gate (before)', function () {
    expect($this->admin->can('viewAny', GaleriaFoto::class))->toBeTrue();
    expect($this->admin->can('delete', $this->fotoA))->toBeTrue();
});

test('dono só gerencia foto do próprio salão', function () {
    $policy = new GaleriaFotoPolicy();
    expect($policy->update($this->dono, $this->fotoA))->toBeTrue();
    expect($policy->delete($this->dono, $this->fotoB))->toBeFalse();
});

// ---------- Folga (salão) ----------

test('admin pode remover folga do salão via Gate', function () {
    expect($this->admin->can('delete', $this->folgaA))->toBeTrue();
});

test('dono só remove folga do próprio salão', function () {
    $policy = new FolgaPolicy();
    expect($policy->delete($this->dono, $this->folgaA))->toBeTrue();
    expect($policy->delete($this->dono, $this->folgaB))->toBeFalse();
});

// ---------- FolgaManicure ----------

test('manicure só remove a própria folga', function () {
    $policy = new FolgaManicurePolicy();
    expect($policy->delete($this->userManicure, $this->folgaManA))->toBeTrue();
    expect($policy->delete($this->userManicure, $this->folgaManB))->toBeFalse();
});

test('cliente não cria folga de manicure', function () {
    expect($this->cliente->can('create', FolgaManicure::class))->toBeFalse();
});
