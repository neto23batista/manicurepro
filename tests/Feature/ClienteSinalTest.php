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
        'manicure.pagamento.mercadopago.enabled' => true,
        'manicure.pagamento.mercadopago.access_token' => 'TEST-TOKEN',
        'manicure.pagamento.sinal.habilitado' => true,
        'manicure.pagamento.sinal.tipo' => 'percentual',
        'manicure.pagamento.sinal.valor' => 30,
    ]);

    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
    $this->userCliente = User::factory()->create(['role' => 'cliente']);
    $this->cliente = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id' => $this->userCliente->id,
    ]);
});

function agendamentoComSinal($self, string $status = 'aguardando'): Agendamento
{
    $inicio = Carbon::now()->addDay()->setTime(10, 0);
    return Agendamento::factory()->create([
        'salao_id' => $self->salao->id,
        'cliente_id' => $self->cliente->id,
        'manicure_id' => $self->manicure->id,
        'user_id' => $self->userCliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim' => $inicio->copy()->addMinutes(30),
        'status' => $status,
        'valor_total' => 100,
        'valor_desconto' => 0,
    ]);
}

test('cliente abre a tela de sinal e a cobrança Pix é criada', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id' => 555001,
            'status' => 'pending',
            'point_of_interaction' => ['transaction_data' => [
                'qr_code' => 'pix-copia-cola-xyz',
                'qr_code_base64' => 'aGVsbG8=',
            ]],
        ], 201),
    ]);

    $ag = agendamentoComSinal($this);

    $this->actingAs($this->userCliente)
        ->get(route('cliente.agendamentos.sinal', $ag))
        ->assertOk()
        ->assertSee('Pix copia e cola')
        ->assertSee('pix-copia-cola-xyz');

    expect($ag->fresh()->mp_payment_id)->toBe('555001');
});

test('polling de status confirma o pagamento e confirma o agendamento', function () {
    $ag = agendamentoComSinal($this);
    $ag->update(['mp_payment_id' => '555002', 'sinal_status' => 'pendente', 'sinal_valor' => 30]);

    Http::fake([
        'api.mercadopago.com/v1/payments/555002' => Http::response(['id' => 555002, 'status' => 'approved'], 200),
    ]);

    $this->actingAs($this->userCliente)
        ->postJson(route('cliente.agendamentos.sinal.status', $ag))
        ->assertOk()
        ->assertJson(['pago' => true]);

    $ag->refresh();
    expect($ag->sinal_status)->toBe('pago');
    expect($ag->status)->toBe('confirmado');
});

test('outro cliente não acessa o sinal alheio', function () {
    $ag = agendamentoComSinal($this);
    $outro = User::factory()->create(['role' => 'cliente']);

    $this->actingAs($outro)
        ->get(route('cliente.agendamentos.sinal', $ag))
        ->assertForbidden();
});
