<?php

use App\Models\Pagamento;
use App\Models\Salao;
use App\Models\ValePresente;
use App\Services\ValePresenteService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(ValePresenteService::class);
    $this->salao = Salao::factory()->create(['ativo' => true]);
});

function criarValeUnit(int $salaoId, float $valor = 100, array $attrs = []): ValePresente
{
    return ValePresente::create(array_merge([
        'salao_id' => $salaoId,
        'codigo'   => 'VP-' . strtoupper(uniqid()),
        'valor'    => $valor,
        'saldo'    => $valor,
        'status'   => ValePresente::STATUS_ATIVO,
    ], $attrs));
}

test('debitar parcial reduz saldo e mantém status ativo', function () {
    $vale = criarValeUnit($this->salao->id, 100);

    $debito = $this->service->debitar($vale, 40);

    expect($debito)->toBe(40.0)
        ->and((float) $vale->saldo)->toBe(60.0)
        ->and($vale->status)->toBe(ValePresente::STATUS_ATIVO)
        ->and((float) $vale->fresh()->saldo)->toBe(60.0);
});

test('debitar maior que o saldo só consome o restante (partial redeem)', function () {
    $vale = criarValeUnit($this->salao->id, 25);

    $debito = $this->service->debitar($vale, 100);

    expect($debito)->toBe(25.0)
        ->and((float) $vale->saldo)->toBe(0.0)
        ->and($vale->status)->toBe(ValePresente::STATUS_USADO);
});

test('debitar zera saldo e marca como usado', function () {
    $vale = criarValeUnit($this->salao->id, 50);

    $this->service->debitar($vale, 50);

    expect($vale->status)->toBe(ValePresente::STATUS_USADO)
        ->and((float) $vale->saldo)->toBe(0.0);
});

test('double redeem após uso total é rejeitado', function () {
    $vale = criarValeUnit($this->salao->id, 30);
    $this->service->debitar($vale, 30);

    expect(fn () => $this->service->debitar($vale->fresh(), 10))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('vale cancelado não pode ser debitado', function () {
    $vale = criarValeUnit($this->salao->id, 50, ['status' => ValePresente::STATUS_CANCELADO]);

    expect(fn () => $this->service->debitar($vale, 10))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('vale expirado não pode ser debitado', function () {
    $vale = criarValeUnit($this->salao->id, 50, ['validade' => now()->subDay()->toDateString()]);

    expect(fn () => $this->service->debitar($vale, 10))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('vale já marcado como usado não pode ser debitado', function () {
    $vale = criarValeUnit($this->salao->id, 50, [
        'saldo'  => 0,
        'status' => ValePresente::STATUS_USADO,
    ]);

    expect(fn () => $this->service->debitar($vale, 10))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('valor de débito zero ou negativo é rejeitado', function () {
    $vale = criarValeUnit($this->salao->id, 50);

    expect(fn () => $this->service->debitar($vale, 0))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect(fn () => $this->service->debitar($vale, -5))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect((float) $vale->fresh()->saldo)->toBe(50.0);
});

test('resgates parciais sucessivos até esgotar o saldo', function () {
    $vale = criarValeUnit($this->salao->id, 100);

    expect($this->service->debitar($vale, 30))->toBe(30.0);
    expect($this->service->debitar($vale->fresh(), 40))->toBe(40.0);
    expect($this->service->debitar($vale->fresh(), 50))->toBe(30.0); // só restavam 30

    $vale->refresh();
    expect((float) $vale->saldo)->toBe(0.0)
        ->and($vale->status)->toBe(ValePresente::STATUS_USADO);

    expect(fn () => $this->service->debitar($vale, 1))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('criar rejeita valor abaixo do mínimo', function () {
    expect(fn () => $this->service->criar($this->salao->id, ['valor' => 0.5]))
        ->toThrow(\InvalidArgumentException::class);
});

test('criar gera saldo igual ao valor e pagamento de venda', function () {
    $vale = $this->service->criar($this->salao->id, [
        'valor'          => 80,
        'comprador_nome' => 'Ana',
    ]);

    expect($vale->codigo)->toStartWith('VP-')
        ->and((float) $vale->saldo)->toBe(80.0)
        ->and($vale->status)->toBe(ValePresente::STATUS_ATIVO)
        ->and(Pagamento::where('referencia', 'vale:' . $vale->codigo)->exists())->toBeTrue();
});

test('validade no dia atual ainda permite débito (não expirado até fim do dia)', function () {
    $vale = criarValeUnit($this->salao->id, 40, ['validade' => today()->toDateString()]);

    $debito = $this->service->debitar($vale, 10);

    expect($debito)->toBe(10.0)
        ->and((float) $vale->fresh()->saldo)->toBe(30.0);
});