<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ConfiguracaoSalao;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use App\Services\FidelidadeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(FidelidadeService::class);

    $this->salao = Salao::factory()->create();
    $userManicure = User::factory()->create(['role' => 'manicure', 'salao_id' => $this->salao->id]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $userManicure->id,
    ]);

    $userCliente = User::factory()->create(['role' => 'cliente']);
    $this->cliente = Cliente::create([
        'user_id'  => $userCliente->id,
        'salao_id' => $this->salao->id,
        'nome'     => 'Cliente Teste',
        'email'    => 'cliente@test.com',
    ]);
});

test('não credita pontos quando agendamento não tem cliente', function () {
    $ag = Agendamento::factory()->create([
        'salao_id'    => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'cliente_id'  => null,
    ]);

    $this->service->creditarPorAtendimento($ag, 100.0);

    // Sem cliente: nada acontece, sem exceção
    expect(true)->toBeTrue();
});

test('credita pontos quando fidelidade está ativa', function () {
    ConfiguracaoSalao::create([
        'salao_id'         => $this->salao->id,
        'fidelidade_ativo' => true,
        'pontos_por_real'  => 1,
    ]);

    $ag = Agendamento::factory()->create([
        'salao_id'    => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'cliente_id'  => $this->cliente->id,
    ]);

    $this->service->creditarPorAtendimento($ag, 50.0);

    $this->cliente->refresh();
    expect($this->cliente->pontos_fidelidade)->toBe(50);
    expect((int) $this->cliente->total_visitas)->toBe(1);
    expect((float) $this->cliente->total_gasto)->toBe(50.0);
});

test('não credita pontos quando fidelidade está desativada', function () {
    ConfiguracaoSalao::create([
        'salao_id'         => $this->salao->id,
        'fidelidade_ativo' => false,
        'pontos_por_real'  => 10,
    ]);

    $ag = Agendamento::factory()->create([
        'salao_id'    => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'cliente_id'  => $this->cliente->id,
    ]);

    $this->service->creditarPorAtendimento($ag, 100.0);

    $this->cliente->refresh();
    expect($this->cliente->pontos_fidelidade)->toBe(0);
    // mas total_visitas / total_gasto são atualizados independente
    expect((int) $this->cliente->total_visitas)->toBe(1);
});
