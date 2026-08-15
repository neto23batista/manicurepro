<?php

use App\Models\Cliente;
use App\Models\Cupom;
use App\Models\Salao;
use App\Models\User;
use App\Services\FidelidadeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['manicure.fidelidade.pontos_para_desconto' => 100, 'manicure.fidelidade.valor_desconto' => 10]);

    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->userCliente = User::factory()->create(['role' => 'cliente']);
    $this->cliente = Cliente::factory()->create([
        'salao_id'          => $this->salao->id,
        'user_id'           => $this->userCliente->id,
        'pontos_fidelidade' => 250,
    ]);
    $this->fidelidade = app(FidelidadeService::class);
});

test('resgate gera cupom fixo e debita os pontos', function () {
    $cupom = $this->fidelidade->resgatar($this->cliente, 1);

    expect($cupom)->toBeInstanceOf(Cupom::class);
    expect($cupom->tipo)->toBe('fixo');
    expect((float) $cupom->valor)->toBe(10.0);
    expect($this->cliente->fresh()->pontos_fidelidade)->toBe(150);

    $this->assertDatabaseHas('fidelidade_pontos', [
        'cliente_id' => $this->cliente->id,
        'tipo'       => 'resgatado',
        'pontos'     => -100,
    ]);
});

test('resgate sem pontos suficientes lança exceção', function () {
    $this->cliente->update(['pontos_fidelidade' => 50]);

    expect(fn () => $this->fidelidade->resgatar($this->cliente, 1))
        ->toThrow(ValidationException::class);
});

test('segunda instância do cliente não resgata os mesmos pontos', function () {
    $this->cliente->update(['pontos_fidelidade' => 100]);
    $stale = Cliente::find($this->cliente->id);

    $this->fidelidade->resgatar($this->cliente, 1);

    expect(fn () => $this->fidelidade->resgatar($stale, 1))
        ->toThrow(ValidationException::class);

    expect($this->cliente->fresh()->pontos_fidelidade)->toBe(0);
    expect(Cupom::where('cliente_id', $this->cliente->id)->where('origem', 'fidelidade')->count())->toBe(1);
});

test('cliente resgata pela rota e recebe o código do cupom', function () {
    $this->actingAs($this->userCliente)
        ->post(route('cliente.fidelidade.resgatar'), ['blocos' => 2])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($this->cliente->fresh()->pontos_fidelidade)->toBe(50); // 250 - 200
    expect(Cupom::where('salao_id', $this->salao->id)->count())->toBe(1);
    expect((float) Cupom::first()->valor)->toBe(20.0);
});
