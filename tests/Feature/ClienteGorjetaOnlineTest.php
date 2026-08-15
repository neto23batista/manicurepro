<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Comanda;
use App\Models\Manicure;
use App\Models\Pagamento;
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
        'manicure.pagamento.gorjeta.habilitado'       => true,
        'manicure.pagamento.total.habilitado'         => false,
        'manicure.pagamento.sinal.habilitado'         => false,
    ]);

    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
    $this->userCliente = User::factory()->create(['role' => 'cliente']);
    $this->cliente = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $this->userCliente->id,
    ]);
});

function agendamentoConcluidoParaGorjeta($self): Agendamento
{
    $inicio = Carbon::now()->subDay()->setTime(10, 0);

    return Agendamento::factory()->create([
        'salao_id'         => $self->salao->id,
        'cliente_id'       => $self->cliente->id,
        'manicure_id'      => $self->manicure->id,
        'user_id'          => $self->userCliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => 'concluido',
        'valor_total'      => 100,
        'valor_desconto'   => 0,
    ]);
}

test('cliente vê formulário de gorjeta após atendimento concluído', function () {
    $ag = agendamentoConcluidoParaGorjeta($this);

    $this->actingAs($this->userCliente)
        ->get(route('cliente.agendamentos.gorjeta', $ag))
        ->assertOk()
        ->assertSee('Valor da gorjeta');
});

test('cliente gera Pix de gorjeta e confirma via polling', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id'                   => 888001,
            'status'               => 'pending',
            'point_of_interaction' => ['transaction_data' => [
                'qr_code'        => 'pix-gorjeta',
                'qr_code_base64' => 'Z29yamV0YQ==',
            ]],
        ], 201),
        'api.mercadopago.com/v1/payments/888001' => Http::response([
            'id'     => 888001,
            'status' => 'approved',
        ], 200),
    ]);

    $ag = agendamentoConcluidoParaGorjeta($this);

    $this->actingAs($this->userCliente)
        ->post(route('cliente.agendamentos.gorjeta', $ag), ['valor' => 20])
        ->assertOk()
        ->assertSee('pix-gorjeta');

    $ag->refresh();
    expect($ag->mp_payment_id)->toBe('888001');
    expect($ag->mp_cobranca_tipo)->toBe('gorjeta');
    expect($ag->mp_gorjeta_status)->toBe('pendente');
    expect((float) $ag->mp_gorjeta_valor)->toBe(20.0);

    $this->actingAs($this->userCliente)
        ->postJson(route('cliente.agendamentos.gorjeta.status', $ag))
        ->assertOk()
        ->assertJson(['pago' => true]);

    $ag->refresh();
    expect($ag->mp_gorjeta_status)->toBe('pago');
    expect($ag->status)->toBe('concluido');

    $comanda = Comanda::where('agendamento_id', $ag->id)->first();
    expect($comanda)->not->toBeNull();
    expect((float) $comanda->gorjeta)->toBe(20.0);
    expect(Pagamento::where('referencia', '888001')->exists())->toBeTrue();
});

test('gorjeta desabilitada redireciona com erro', function () {
    config(['manicure.pagamento.gorjeta.habilitado' => false]);
    $ag = agendamentoConcluidoParaGorjeta($this);

    $this->actingAs($this->userCliente)
        ->get(route('cliente.agendamentos.gorjeta', $ag))
        ->assertRedirect(route('cliente.agendamentos.show', $ag));
});

test('outro cliente não acessa gorjeta alheia', function () {
    $ag = agendamentoConcluidoParaGorjeta($this);
    $outro = User::factory()->create(['role' => 'cliente']);

    $this->actingAs($outro)
        ->get(route('cliente.agendamentos.gorjeta', $ag))
        ->assertForbidden();
});
