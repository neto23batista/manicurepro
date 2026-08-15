<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'manicure.pagamento.mercadopago.enabled'      => true,
        'manicure.pagamento.mercadopago.access_token' => 'TEST-TOKEN',
        'manicure.pagamento.sinal.habilitado'         => false,
        'manicure.pagamento.total.habilitado'         => true,
    ]);

    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
    $this->userCliente = User::factory()->create(['role' => 'cliente']);
    $this->cliente = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $this->userCliente->id,
    ]);
});

function agendamentoParaPagamentoTotal($self, string $status = 'aguardando'): Agendamento
{
    $inicio = Carbon::now()->addDay()->setTime(10, 0);

    return Agendamento::factory()->create([
        'salao_id'         => $self->salao->id,
        'cliente_id'       => $self->cliente->id,
        'manicure_id'      => $self->manicure->id,
        'user_id'          => $self->userCliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => $status,
        'valor_total'      => 100,
        'valor_desconto'   => 0,
    ]);
}

test('cliente abre a tela de pagamento total e a cobrança Pix é criada', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id'                   => 777001,
            'status'               => 'pending',
            'point_of_interaction' => ['transaction_data' => [
                'qr_code'        => 'pix-total-copia-cola',
                'qr_code_base64' => 'dG90YWw=',
            ]],
        ], 201),
    ]);

    $ag = agendamentoParaPagamentoTotal($this);

    $this->actingAs($this->userCliente)
        ->get(route('cliente.agendamentos.pagamento', $ag))
        ->assertOk()
        ->assertSee('Pix copia e cola')
        ->assertSee('pix-total-copia-cola');

    $ag->refresh();
    expect($ag->mp_payment_id)->toBe('777001');
    expect($ag->mp_cobranca_tipo)->toBe('total');
    expect($ag->mp_total_status)->toBe('pendente');
    expect((float) $ag->mp_total_valor)->toBe(100.0);
});

test('polling de pagamento total confirma e confirma o agendamento', function () {
    $ag = agendamentoParaPagamentoTotal($this);
    $ag->update([
        'mp_payment_id'    => '777002',
        'mp_cobranca_tipo' => 'total',
        'mp_total_status'  => 'pendente',
        'mp_total_valor'   => 100,
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments/777002' => Http::response(['id' => 777002, 'status' => 'approved'], 200),
    ]);

    $this->actingAs($this->userCliente)
        ->postJson(route('cliente.agendamentos.pagamento.status', $ag))
        ->assertOk()
        ->assertJson(['pago' => true]);

    $ag->refresh();
    expect($ag->mp_total_status)->toBe('pago');
    expect($ag->status)->toBe('confirmado');
});

test('pagamento total desabilitado redireciona com erro', function () {
    config(['manicure.pagamento.total.habilitado' => false]);

    $ag = agendamentoParaPagamentoTotal($this);

    $this->actingAs($this->userCliente)
        ->get(route('cliente.agendamentos.pagamento', $ag))
        ->assertRedirect(route('cliente.agendamentos.show', $ag));
});

test('outro cliente não acessa o pagamento total alheio', function () {
    $ag = agendamentoParaPagamentoTotal($this);
    $outro = User::factory()->create(['role' => 'cliente']);

    $this->actingAs($outro)
        ->get(route('cliente.agendamentos.pagamento', $ag))
        ->assertForbidden();
});

test('cliente paga restante após sinal já pago', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id'                   => 777003,
            'status'               => 'pending',
            'point_of_interaction' => ['transaction_data' => [
                'qr_code'        => 'pix-restante',
                'qr_code_base64' => 'cmVzdA==',
            ]],
        ], 201),
    ]);

    $ag = agendamentoParaPagamentoTotal($this, 'confirmado');
    $ag->update([
        'sinal_status' => 'pago',
        'sinal_valor'  => 30,
    ]);

    $this->actingAs($this->userCliente)
        ->get(route('cliente.agendamentos.pagamento', $ag))
        ->assertOk()
        ->assertSee('Valor restante')
        ->assertSee('pix-restante');

    expect((float) $ag->fresh()->mp_total_valor)->toBe(70.0);
});
