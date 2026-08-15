<?php

use App\Models\Agendamento;
use App\Models\Manicure;
use App\Models\Pagamento;
use App\Models\Salao;
use App\Models\WebhookEvent;
use App\Services\ComandaService;
use App\Services\MercadoPagoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'manicure.pagamento.mercadopago.enabled'        => true,
        'manicure.pagamento.mercadopago.access_token'   => 'TEST-TOKEN',
        'manicure.pagamento.mercadopago.webhook_secret' => 'segredo-teste',
        'manicure.pagamento.sinal.habilitado'           => true,
        'manicure.pagamento.sinal.tipo'                 => 'percentual',
        'manicure.pagamento.sinal.valor'                => 30,
        'manicure.pagamento.total.habilitado'           => true,
    ]);

    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
    $this->mp = app(MercadoPagoService::class);
});

function novoAgendamentoPix($self, float $valor = 100.0, string $status = 'aguardando'): Agendamento
{
    $inicio = Carbon::now()->addDay()->setTime(10, 0);

    return Agendamento::factory()->create([
        'salao_id'         => $self->salao->id,
        'manicure_id'      => $self->manicure->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => $status,
        'valor_total'      => $valor,
        'valor_desconto'   => 0,
    ]);
}

function assinaturaWebhook(string $dataId, string $reqId = 'req-abc', ?string $ts = null): array
{
    $ts ??= (string) time();
    $v1 = hash_hmac('sha256', "id:{$dataId};request-id:{$reqId};ts:{$ts};", 'segredo-teste');

    return [
        'headers' => [
            'x-signature'  => "ts={$ts},v1={$v1}",
            'x-request-id' => $reqId,
        ],
        'query' => $dataId,
    ];
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
            'id'                   => 999001,
            'status'               => 'pending',
            'point_of_interaction' => [
                'transaction_data' => [
                    'qr_code'        => '00020126pix-copia-cola',
                    'qr_code_base64' => 'aGVsbG8=',
                    'ticket_url'     => 'https://mp/ticket/999001',
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
    expect($ag->mp_cobranca_tipo)->toBe('sinal');
    expect($ag->sinal_status)->toBe('pendente');
    expect((float) $ag->sinal_valor)->toBe(30.0);
});

test('cria cobrança Pix do valor total', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id'                   => 999010,
            'status'               => 'pending',
            'point_of_interaction' => [
                'transaction_data' => [
                    'qr_code'        => 'pix-total',
                    'qr_code_base64' => 'dG90YWw=',
                ],
            ],
        ], 201),
    ]);

    $ag = novoAgendamentoPix($this, 100.0);
    $resultado = $this->mp->criarPixTotal($ag);

    expect($resultado['valor'])->toBe(100.0);

    $ag->refresh();
    expect($ag->mp_payment_id)->toBe('999010');
    expect($ag->mp_cobranca_tipo)->toBe('total');
    expect($ag->mp_total_status)->toBe('pendente');
    expect((float) $ag->mp_total_valor)->toBe(100.0);
});

test('cria cobrança Pix do restante após sinal pago', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id'                   => 999011,
            'status'               => 'pending',
            'point_of_interaction' => [
                'transaction_data' => ['qr_code' => 'pix-restante'],
            ],
        ], 201),
    ]);

    $ag = novoAgendamentoPix($this, 100.0);
    $ag->update([
        'sinal_status' => 'pago',
        'sinal_valor'  => 30,
        'status'       => 'confirmado',
    ]);

    expect($this->mp->calcularValorTotal($ag))->toBe(70.0);

    $resultado = $this->mp->criarPixTotal($ag);
    expect($resultado['valor'])->toBe(70.0);
    expect((float) $ag->fresh()->mp_total_valor)->toBe(70.0);
});

test('webhook aprovado marca sinal como pago e confirma o agendamento', function () {
    $ag = novoAgendamentoPix($this, 100.0, 'aguardando');
    $ag->update([
        'mp_payment_id'    => '999002',
        'mp_cobranca_tipo' => 'sinal',
        'sinal_status'     => 'pendente',
        'sinal_valor'      => 30,
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments/999002' => Http::response([
            'id'     => 999002,
            'status' => 'approved',
        ], 200),
    ]);

    $sig = assinaturaWebhook('999002');

    $this->withHeaders($sig['headers'])
        ->postJson(route('webhooks.mercadopago').'?data.id=999002', [
            'type' => 'payment',
            'data' => ['id' => '999002'],
        ])
        ->assertOk();

    $ag->refresh();
    expect($ag->sinal_status)->toBe('pago');
    expect($ag->status)->toBe('confirmado');
    expect($ag->confirmado_em)->not->toBeNull();
    expect(Pagamento::where('referencia', '999002')->where('status', 'confirmado')->exists())->toBeTrue();
});

test('webhook de outro tópico é ignorado', function () {
    Http::fake();
    $sig = assinaturaWebhook('x');

    $this->withHeaders($sig['headers'])
        ->postJson(route('webhooks.mercadopago').'?data.id=x', [
            'type' => 'plan',
            'data' => ['id' => 'x'],
        ])
        ->assertOk()
        ->assertJson(['ignored' => true]);

    Http::assertNothingSent();
});

test('webhook duplicado ainda sincroniza status (pending→approved)', function () {
    $ag = novoAgendamentoPix($this, 100.0, 'aguardando');
    $ag->update([
        'mp_payment_id'    => '999050',
        'mp_cobranca_tipo' => 'sinal',
        'sinal_status'     => 'pendente',
        'sinal_valor'      => 30,
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments/999050' => Http::sequence()
            ->push(['id' => 999050, 'status' => 'pending'], 200)
            ->push(['id' => 999050, 'status' => 'approved'], 200),
    ]);

    $sig = assinaturaWebhook('999050');
    $payload = [
        'type' => 'payment',
        'data' => ['id' => '999050'],
    ];

    $this->withHeaders($sig['headers'])
        ->postJson(route('webhooks.mercadopago').'?data.id=999050', $payload)
        ->assertOk()
        ->assertJsonMissing(['duplicate' => true]);

    expect($ag->fresh()->sinal_status)->toBe('pendente');
    expect(WebhookEvent::where('provider', 'mercadopago')->where('event_id', '999050')->count())->toBe(1);

    // Segunda entrega: duplicate=true, mas ainda sincroniza pending→approved.
    $this->withHeaders($sig['headers'])
        ->postJson(route('webhooks.mercadopago').'?data.id=999050', $payload)
        ->assertOk()
        ->assertJson(['ok' => true, 'duplicate' => true]);

    expect($ag->fresh()->sinal_status)->toBe('pago');
    expect(WebhookEvent::where('provider', 'mercadopago')->where('event_id', '999050')->count())->toBe(1);
});

test('webhook sem agendamento libera reserva para reentrega', function () {
    $sig = assinaturaWebhook('999051');
    $payload = [
        'type' => 'payment',
        'data' => ['id' => '999051'],
    ];

    $this->withHeaders($sig['headers'])
        ->postJson(route('webhooks.mercadopago').'?data.id=999051', $payload)
        ->assertOk();

    expect(WebhookEvent::where('event_id', '999051')->exists())->toBeFalse();

    $this->withHeaders(assinaturaWebhook('999051', 'req-retry')['headers'])
        ->postJson(route('webhooks.mercadopago').'?data.id=999051', $payload)
        ->assertOk()
        ->assertJsonMissing(['duplicate' => true]);
});

test('sincronizarStatus não regride pagamento pago para pendente', function () {
    $ag = novoAgendamentoPix($this, 100.0, 'confirmado');
    $ag->update([
        'mp_payment_id'    => '999060',
        'mp_cobranca_tipo' => 'sinal',
        'sinal_status'     => 'pago',
        'sinal_valor'      => 30,
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments/999060' => Http::response([
            'id'     => 999060,
            'status' => 'pending',
        ], 200),
    ]);

    $status = $this->mp->sincronizarStatus($ag);

    expect($status)->toBe('pago');
    expect($ag->fresh()->sinal_status)->toBe('pago');
});

test('cancelarOuEstornar cancela cobrança pendente', function () {
    $ag = novoAgendamentoPix($this, 100.0);
    $ag->update([
        'mp_payment_id'    => '999020',
        'mp_cobranca_tipo' => 'sinal',
        'sinal_status'     => 'pendente',
        'sinal_valor'      => 30,
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments/999020' => Http::sequence()
            ->push(['id' => 999020, 'status' => 'pending'], 200)
            ->push(['id' => 999020, 'status' => 'cancelled'], 200),
    ]);

    $resultado = $this->mp->cancelarOuEstornar($ag);

    expect($resultado['ok'])->toBeTrue();
    expect($resultado['acao'])->toBe('cancelado');
    expect($ag->fresh()->sinal_status)->toBe('cancelado');

    Http::assertSent(function ($request) {
        return $request->method() === 'PUT'
            && str_contains($request->url(), '/v1/payments/999020')
            && ($request['status'] ?? null) === 'cancelled';
    });
});

test('cancelarOuEstornar estorna pagamento aprovado', function () {
    $ag = novoAgendamentoPix($this, 100.0, 'confirmado');
    $ag->update([
        'mp_payment_id'    => '999021',
        'mp_cobranca_tipo' => 'total',
        'mp_total_status'  => 'pago',
        'mp_total_valor'   => 100,
    ]);

    Pagamento::create([
        'agendamento_id' => $ag->id,
        'salao_id'       => $ag->salao_id,
        'forma'          => 'pix',
        'valor'          => 100,
        'status'         => 'confirmado',
        'referencia'     => '999021',
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments/999021'         => Http::response(['id' => 999021, 'status' => 'approved'], 200),
        'api.mercadopago.com/v1/payments/999021/refunds' => Http::response(['id' => 1, 'status' => 'approved'], 201),
    ]);

    $resultado = $this->mp->cancelarOuEstornar($ag);

    expect($resultado['ok'])->toBeTrue();
    expect($resultado['acao'])->toBe('estornado');
    expect($ag->fresh()->mp_total_status)->toBe('estornado');
    expect(Pagamento::where('referencia', '999021')->value('status'))->toBe('estornado');
});

test('fechar comanda aceita gorjeta no total e no pagamento', function () {
    $ag = novoAgendamentoPix($this, 100.0, 'em_andamento');
    $comanda = app(ComandaService::class)->fecharComanda($ag, [
        'forma'   => 'dinheiro',
        'gorjeta' => 15,
    ]);

    expect((float) $comanda->gorjeta)->toBe(15.0);
    expect((float) $comanda->total)->toBe(115.0);
    expect((float) $comanda->pagamentos()->sum('valor'))->toBe(115.0);
});
