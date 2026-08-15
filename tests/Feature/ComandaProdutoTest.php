<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Produto;
use App\Models\Salao;
use App\Models\User;
use App\Notifications\EstoqueZerado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->dono = User::factory()->create(['role' => 'dono', 'salao_id' => $this->salao->id, 'ativo' => true]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
    $this->cliente = Cliente::factory()->create(['salao_id' => $this->salao->id]);
    $this->produto = Produto::create([
        'salao_id'       => $this->salao->id,
        'nome'           => 'Esmalte Vermelho',
        'preco_venda'    => 20,
        'estoque_atual'  => 10,
        'estoque_minimo' => 1,
        'unidade'        => 'un',
        'ativo'          => true,
    ]);

    $inicio = now()->addDay()->setTime(10, 0);
    $this->agendamento = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(60),
        'status'           => 'confirmado',
        'valor_total'      => 50,
        'valor_desconto'   => 0,
    ]);
});

test('vender produto baixa o estoque e soma no total da comanda', function () {
    $this->actingAs($this->dono)
        ->post(route('dono.agendamentos.produto', $this->agendamento), [
            'produto_id' => $this->produto->id,
            'quantidade' => 2,
        ])->assertSessionHasNoErrors()->assertRedirect();

    expect((float) $this->produto->fresh()->estoque_atual)->toBe(8.0);

    $comanda = $this->agendamento->fresh()->comanda;
    expect($comanda)->not->toBeNull();
    expect((float) $comanda->valor_produtos)->toBe(40.0);
    expect((float) $comanda->total)->toBe(90.0); // 50 serviços + 40 produtos
});

test('estoque insuficiente bloqueia a venda', function () {
    $this->actingAs($this->dono)
        ->post(route('dono.agendamentos.produto', $this->agendamento), [
            'produto_id' => $this->produto->id,
            'quantidade' => 999,
        ])->assertSessionHasErrors('error');

    expect((float) $this->produto->fresh()->estoque_atual)->toBe(10.0);
});

test('remover item estorna o estoque', function () {
    $this->actingAs($this->dono)->post(route('dono.agendamentos.produto', $this->agendamento), [
        'produto_id' => $this->produto->id, 'quantidade' => 3,
    ]);
    expect((float) $this->produto->fresh()->estoque_atual)->toBe(7.0);

    $item = $this->agendamento->fresh()->comanda->itens()->where('tipo', 'produto')->first();

    $this->actingAs($this->dono)
        ->delete(route('dono.agendamentos.item.remover', [$this->agendamento, $item]))
        ->assertRedirect();

    expect((float) $this->produto->fresh()->estoque_atual)->toBe(10.0);
    expect((float) $this->agendamento->fresh()->comanda->valor_produtos)->toBe(0.0);
});

test('finalizar inclui produtos no total e no pagamento', function () {
    $this->actingAs($this->dono)->post(route('dono.agendamentos.produto', $this->agendamento), [
        'produto_id' => $this->produto->id, 'quantidade' => 1,
    ]);

    $this->actingAs($this->dono)->patch(route('dono.agendamentos.status', $this->agendamento), [
        'status'          => 'concluido',
        'forma_pagamento' => 'dinheiro',
    ])->assertRedirect();

    $comanda = $this->agendamento->fresh()->comanda;
    expect((float) $comanda->total)->toBe(70.0); // 50 + 20

    $pagamento = $comanda->pagamentos()->first();
    expect($pagamento)->not->toBeNull();
    expect((float) $pagamento->valor)->toBe(70.0);
});

test('não vende produto em atendimento concluído', function () {
    $this->agendamento->update(['status' => 'concluido']);

    $this->actingAs($this->dono)->post(route('dono.agendamentos.produto', $this->agendamento), [
        'produto_id' => $this->produto->id, 'quantidade' => 1,
    ])->assertSessionHasErrors('error');
});

test('venda que zera o estoque notifica o dono', function () {
    config(['manicure.estoque.notificar_zerado' => true]);
    $this->produto->update(['estoque_atual' => 2]);

    $this->actingAs($this->dono)
        ->post(route('dono.agendamentos.produto', $this->agendamento), [
            'produto_id' => $this->produto->id,
            'quantidade' => 2,
        ])->assertSessionHasNoErrors()->assertRedirect();

    expect((float) $this->produto->fresh()->estoque_atual)->toBe(0.0);
    Notification::assertSentTo($this->dono, EstoqueZerado::class);
});

test('venda que não zera o estoque não notifica', function () {
    config(['manicure.estoque.notificar_zerado' => true]);

    $this->actingAs($this->dono)
        ->post(route('dono.agendamentos.produto', $this->agendamento), [
            'produto_id' => $this->produto->id,
            'quantidade' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();

    Notification::assertNotSentTo($this->dono, EstoqueZerado::class);
});

test('notificação de estoque zerado respeita a flag de config', function () {
    config(['manicure.estoque.notificar_zerado' => false]);
    $this->produto->update(['estoque_atual' => 1]);

    $this->actingAs($this->dono)
        ->post(route('dono.agendamentos.produto', $this->agendamento), [
            'produto_id' => $this->produto->id,
            'quantidade' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();

    expect((float) $this->produto->fresh()->estoque_atual)->toBe(0.0);
    Notification::assertNotSentTo($this->dono, EstoqueZerado::class);
});
