<?php

use App\Models\Cliente;
use App\Models\Cupom;
use App\Models\Salao;
use App\Models\User;
use App\Policies\ClientePolicy;
use App\Policies\CupomPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salaoA = Salao::factory()->create();
    $this->salaoB = Salao::factory()->create();
    $this->dono = User::factory()->create(['role' => 'dono', 'salao_id' => $this->salaoA->id]);
    $this->admin = User::factory()->create(['role' => 'admin', 'salao_id' => null]);

    $this->clienteA = Cliente::create([
        'salao_id' => $this->salaoA->id,
        'nome'     => 'Ana',
        'email'    => 'ana@x.com',
        'ativo'    => true,
    ]);
    $this->clienteB = Cliente::create([
        'salao_id' => $this->salaoB->id,
        'nome'     => 'Bia',
        'email'    => 'bia@x.com',
        'ativo'    => true,
    ]);

    $this->cupomA = Cupom::create([
        'salao_id'  => $this->salaoA->id,
        'codigo'    => 'PROMOA',
        'tipo'      => 'percentual',
        'valor'     => 10,
        'uso_atual' => 0,
        'ativo'     => true,
    ]);
    $this->cupomB = Cupom::create([
        'salao_id'  => $this->salaoB->id,
        'codigo'    => 'PROMOB',
        'tipo'      => 'percentual',
        'valor'     => 10,
        'uso_atual' => 0,
        'ativo'     => true,
    ]);
});

test('admin acessa cliente e cupom de qualquer salão via Gate', function () {
    expect($this->admin->can('view', $this->clienteA))->toBeTrue();
    expect($this->admin->can('view', $this->clienteB))->toBeTrue();
    expect($this->admin->can('update', $this->cupomA))->toBeTrue();
    expect($this->admin->can('delete', $this->cupomB))->toBeTrue();
});

test('dono só vê cliente e cupom do próprio salão', function () {
    $clientePolicy = new ClientePolicy;
    $cupomPolicy = new CupomPolicy;

    expect($clientePolicy->view($this->dono, $this->clienteA))->toBeTrue();
    expect($clientePolicy->view($this->dono, $this->clienteB))->toBeFalse();
    expect($cupomPolicy->update($this->dono, $this->cupomA))->toBeTrue();
    expect($cupomPolicy->delete($this->dono, $this->cupomB))->toBeFalse();
});
