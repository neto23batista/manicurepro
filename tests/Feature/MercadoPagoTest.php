<?php

use App\Models\Agendamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Services\MercadoPagoService;
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
    $this->mp = app(MercadoPagoService::class);
});

function novoAgendamentoPix($self, float $valor = 100.0, string $status = 'aguardando'): Agendamento
{
    $inicio = Carbon::now()->addDay()->setTime(10, 0);
    return Agendamento::factory()->create([
        'salao_id' => $self->salao->id,
        'manicure_id' => $self->manicure->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim' => $inicio->copy()->addMinutes(30),
        'status' => $status,
        'valor_total' => $valor,
        'valor_desconto' => 0,
    ]);
}

test('calcula sinal percentual e fixo', function () {
    expect($this->mp->calcularSinal(100))->toBe(30.0);

    config(['manicure.pagamento.sinal.tipo' => 'fixo', 'manicure.pagamento.sinal.valor' => 25]);
    expect($this->mp->calcularSinal(100))->toBe(25.0);
    expect($this->mp->calcularSinal(20))->toBe(20.0); // nunca maior que o total
});

test('cria cobrança Pix e persiste a referência', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id' => 999001,
            'status' => 'pending',
            'point_of_interaction' => [
                'transaction_data' => [
                    'qr_code' => '00020126pix-copia-cola',
                    'qr_code_base64' => 'aGVsbG8=',
                    'ticket_url' => 'https://mp/ticket/999001',
                ],
            ],
        ], 201),
    ]);

    $ag = novoAgendamentoPix($this, 100.0);
    $resultado = $this->mp->criarPixSinal($ag);

    expect($resultado['valor'])->toBe(30.0);
    expect($resultado['qr_code'])->toBe('00020126pix-copia-cola');

    $ag->refresh();
    expect($ag->mp_payment_id)->toBe('999001');
    expect($ag->sinal_status)->toBe('pendente');
    expect((float) $ag->sinal_valor)->toBe(30.0);
});

test('webhook aprovado marca sinal como pago e confirma o agendamento', function () {
    $ag = novoAgendamentoPix($this, 100.0, 'aguardando');
    $ag->update(['mp_payment_id' => '999002', 'sinal_status' => 'pendente', 'sinal_valor' => 30]);

    Http::fake([
        'api.mercadopago.com/v1/payments/999002' => Http::response([
            'id' => 999002,
            'status' => 'approved',
        ], 200),
    ]);

    $this->postJson(route('webhooks.mercadopago'), [
        'type' => 'payment',
        'data' => ['id' => '999002'],
    ])->assertOk();

    $ag->refresh();
    expect($ag->sinal_status)->toBe('pago');
    expect($ag->status)->toBe('confirmado');
    expect($ag->confirmado_em)->not->toBeNull();
});

test('webhook de outro tópico é ignorado', function () {
    Http::fake();

    $this->postJson(route('webhooks.mercadopago'), [
        'type' => 'plan',
        'data' => ['id' => 'x'],
    ])->assertOk()->assertJson(['ignored' => true]);

    Http::assertNothingSent();
});
