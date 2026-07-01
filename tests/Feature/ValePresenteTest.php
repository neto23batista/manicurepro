<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use App\Models\ValePresente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->dono = User::factory()->create(['role' => 'dono', 'salao_id' => $this->salao->id, 'ativo' => true]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
    $this->cliente = Cliente::factory()->create(['salao_id' => $this->salao->id]);

    $inicio = now()->addDay()->setTime(10, 0);
    $this->agendamento = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(60),
        'status'           => 'em_andamento',
        'valor_total'      => 100,
        'valor_desconto'   => 0,
    ]);
});

function valeAtivo(int $salaoId, float $valor = 50, array $attrs = []): ValePresente
{
    return ValePresente::create(array_merge([
        'salao_id' => $salaoId,
        'codigo'   => 'VP-TESTE01',
        'valor'    => $valor,
        'saldo'    => $valor,
        'status'   => ValePresente::STATUS_ATIVO,
    ], $attrs));
}

test('dono emite vale-presente com saldo igual ao valor', function () {
    $this->actingAs($this->dono)->post('/dono/vales', [
        'valor'          => 150,
        'comprador_nome' => 'Maria',
    ])->assertRedirect();

    $vale = ValePresente::first();
    expect($vale)->not->toBeNull();
    expect((float) $vale->saldo)->toBe(150.0);
    expect($vale->codigo)->toStartWith('VP-');
    expect($vale->salao_id)->toBe($this->salao->id);
});

test('aplicar vale debita o saldo e abate o total da comanda', function () {
    $vale = valeAtivo($this->salao->id, 30);

    $this->actingAs($this->dono)->post(route('dono.agendamentos.vale', $this->agendamento), [
        'codigo' => 'VP-TESTE01',
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect((float) $vale->fresh()->saldo)->toBe(0.0);
    expect($vale->fresh()->status)->toBe(ValePresente::STATUS_USADO);

    $comanda = $this->agendamento->fresh()->comanda;
    expect((float) $comanda->total_pago)->toBe(30.0);
});

test('vale parcial: finalizar paga apenas o saldo restante', function () {
    valeAtivo($this->salao->id, 30);

    $this->actingAs($this->dono)->post(route('dono.agendamentos.vale', $this->agendamento), ['codigo' => 'VP-TESTE01']);

    $this->actingAs($this->dono)->patch(route('dono.agendamentos.status', $this->agendamento), [
        'status'          => 'concluido',
        'forma_pagamento' => 'dinheiro',
    ])->assertRedirect();

    $comanda = $this->agendamento->fresh()->comanda;
    expect((float) $comanda->total)->toBe(100.0);
    expect((float) $comanda->total_pago)->toBe(100.0); // 30 voucher + 70 dinheiro

    $voucher = $comanda->pagamentos->firstWhere('forma', 'voucher');
    $dinheiro = $comanda->pagamentos->firstWhere('forma', 'dinheiro');
    expect((float) $voucher->valor)->toBe(30.0);
    expect((float) $dinheiro->valor)->toBe(70.0);
});

test('vale maior que o total não fica com saldo negativo e não há troco', function () {
    $vale = valeAtivo($this->salao->id, 250);

    $this->actingAs($this->dono)->post(route('dono.agendamentos.vale', $this->agendamento), ['codigo' => 'VP-TESTE01'])
        ->assertSessionHasNoErrors();

    // debita só o necessário (100), saldo restante 150
    expect((float) $vale->fresh()->saldo)->toBe(150.0);
    expect($vale->fresh()->status)->toBe(ValePresente::STATUS_ATIVO);
    expect((float) $this->agendamento->fresh()->comanda->total_pago)->toBe(100.0);
});

test('vale expirado não pode ser aplicado', function () {
    valeAtivo($this->salao->id, 50, ['validade' => now()->subDay()]);

    $this->actingAs($this->dono)->post(route('dono.agendamentos.vale', $this->agendamento), ['codigo' => 'VP-TESTE01'])
        ->assertSessionHasErrors('error');

    expect((float) $this->agendamento->fresh()->comanda?->total_pago ?? 0)->toBe(0.0);
});

test('vale cancelado não pode ser aplicado', function () {
    valeAtivo($this->salao->id, 50, ['status' => ValePresente::STATUS_CANCELADO]);

    $this->actingAs($this->dono)->post(route('dono.agendamentos.vale', $this->agendamento), ['codigo' => 'VP-TESTE01'])
        ->assertSessionHasErrors('error');
});

test('dono cancela vale e ele deixa de ser aplicável', function () {
    $vale = valeAtivo($this->salao->id, 50);

    $this->actingAs($this->dono)->delete(route('dono.vales.cancelar', $vale))->assertRedirect();
    expect($vale->fresh()->status)->toBe(ValePresente::STATUS_CANCELADO);
    expect($vale->fresh()->estaDisponivel())->toBeFalse();
});

test('dono não acessa vale de outro salão', function () {
    $outro = Salao::factory()->create();
    $vale = valeAtivo($outro->id, 50, ['codigo' => 'VP-OUTRO001']);

    $this->actingAs($this->dono)->get(route('dono.vales.show', $vale))->assertForbidden();
    $this->actingAs($this->dono)->delete(route('dono.vales.cancelar', $vale))->assertForbidden();
});

test('cliente não acessa vales', function () {
    $cliente = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->actingAs($cliente)->get('/dono/vales')->assertForbidden();
});
