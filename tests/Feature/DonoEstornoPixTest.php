<?php

use App\Models\Agendamento;
use App\Models\AuditLog;
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
        'manicure.pagamento.mercadopago.enabled'        => true,
        'manicure.pagamento.mercadopago.access_token'   => 'TEST-TOKEN',
        'manicure.pagamento.mercadopago.webhook_secret' => 'segredo-teste',
    ]);

    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
    $this->dono = User::factory()->dono()->create([
        'salao_id'          => $this->salao->id,
        'ativo'             => true,
        'email_verified_at' => now(),
    ]);
    $this->atendente = User::factory()->create([
        'role'              => 'atendente',
        'salao_id'          => $this->salao->id,
        'ativo'             => true,
        'email_verified_at' => now(),
    ]);
});

function agendamentoComPixPago($self, array $extra = []): Agendamento
{
    $inicio = Carbon::now()->addDay()->setTime(10, 0);

    return Agendamento::factory()->create(array_merge([
        'salao_id'         => $self->salao->id,
        'manicure_id'      => $self->manicure->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => 'confirmado',
        'valor_total'      => 100,
        'valor_desconto'   => 0,
        'mp_payment_id'    => '999100',
        'mp_cobranca_tipo' => 'total',
        'mp_total_status'  => 'pago',
        'mp_total_valor'   => 100,
    ], $extra));
}

test('dono estorna pix aprovado sem cancelar agendamento', function () {
    $ag = agendamentoComPixPago($this);

    Pagamento::create([
        'agendamento_id' => $ag->id,
        'salao_id'       => $ag->salao_id,
        'forma'          => 'pix',
        'valor'          => 100,
        'status'         => 'confirmado',
        'referencia'     => '999100',
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments/999100'         => Http::response(['id' => 999100, 'status' => 'approved'], 200),
        'api.mercadopago.com/v1/payments/999100/refunds' => Http::response(['id' => 1, 'status' => 'approved'], 201),
    ]);

    $this->actingAs($this->dono)
        ->post(route('dono.agendamentos.estorno-pix', $ag), ['motivo' => 'Cliente pediu devolução'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $ag->refresh();
    expect($ag->status)->toBe('confirmado');
    expect($ag->mp_total_status)->toBe('estornado');
    expect(Pagamento::where('referencia', '999100')->value('status'))->toBe('estornado');

    $this->assertDatabaseHas('audit_logs', [
        'action'       => 'pagamento.estornado',
        'auditable_id' => $ag->id,
        'user_id'      => $this->dono->id,
    ]);

    $meta = AuditLog::where('action', 'pagamento.estornado')->latest('id')->value('meta');
    expect($meta['acao'] ?? null)->toBe('estornado');
    expect($meta['motivo'] ?? null)->toBe('Cliente pediu devolução');
});

test('dono cancela cobrança pix pendente', function () {
    $ag = agendamentoComPixPago($this, [
        'mp_cobranca_tipo' => 'sinal',
        'sinal_status'     => 'pendente',
        'sinal_valor'      => 30,
        'mp_total_status'  => null,
        'mp_total_valor'   => null,
        'mp_payment_id'    => '999101',
    ]);

    Http::fake([
        'api.mercadopago.com/v1/payments/999101' => Http::sequence()
            ->push(['id' => 999101, 'status' => 'pending'], 200)
            ->push(['id' => 999101, 'status' => 'cancelled'], 200),
    ]);

    $this->actingAs($this->dono)
        ->post(route('dono.agendamentos.estorno-pix', $ag))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($ag->fresh()->sinal_status)->toBe('cancelado');
    expect($ag->fresh()->status)->toBe('confirmado');
});

test('atendente sem grant financeiro recebe 403 no estorno', function () {
    $ag = agendamentoComPixPago($this);

    $this->actingAs($this->atendente)
        ->post(route('dono.agendamentos.estorno-pix', $ag))
        ->assertForbidden();
});

test('dono de outro salão não estorna (policy)', function () {
    $outro = Salao::factory()->create(['ativo' => true]);
    $donoOutro = User::factory()->dono()->create([
        'salao_id'          => $outro->id,
        'ativo'             => true,
        'email_verified_at' => now(),
    ]);
    $ag = agendamentoComPixPago($this);

    $this->actingAs($donoOutro)
        ->post(route('dono.agendamentos.estorno-pix', $ag))
        ->assertForbidden();
});

test('falha na API MP exibe erro e mantém status', function () {
    $ag = agendamentoComPixPago($this);

    Http::fake([
        'api.mercadopago.com/v1/payments/999100' => Http::response(['message' => 'error'], 500),
    ]);

    $this->actingAs($this->dono)
        ->post(route('dono.agendamentos.estorno-pix', $ag))
        ->assertRedirect()
        ->assertSessionHasErrors('error');

    expect($ag->fresh()->mp_total_status)->toBe('pago');
});

test('show do dono exibe bloco de estorno quando pix pago', function () {
    $ag = agendamentoComPixPago($this);

    $this->actingAs($this->dono)
        ->get(route('dono.agendamentos.show', $ag))
        ->assertOk()
        ->assertSee('Estornar Pix')
        ->assertSee('Pix online');
});
