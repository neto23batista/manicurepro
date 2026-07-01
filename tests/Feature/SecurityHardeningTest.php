<?php

use App\Models\Agendamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

// ---------- IDOR ----------
test('cliente sem cadastro não acessa agendamento de balcão (cliente_id null)', function () {
    $salao = Salao::factory()->create(['ativo' => true]);
    $manicure = Manicure::factory()->create(['salao_id' => $salao->id, 'ativo' => true]);
    $dono = User::factory()->create(['role' => 'dono', 'salao_id' => $salao->id]);

    $inicio = Carbon::now()->addDay()->setTime(10, 0);
    $balcao = Agendamento::factory()->create([
        'salao_id' => $salao->id,
        'manicure_id' => $manicure->id,
        'cliente_id' => null,
        'user_id' => $dono->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim' => $inicio->copy()->addMinutes(30),
        'status' => 'confirmado',
    ]);

    // Usuário com role cliente mas SEM registro Cliente (clienteId = null)
    $intruso = User::factory()->create(['role' => 'cliente']);

    $this->actingAs($intruso)
        ->get(route('cliente.agendamentos.show', $balcao))
        ->assertForbidden();
});

// ---------- Brute force no login ----------
test('login bloqueia após tentativas excessivas (mesmo com senha correta depois)', function () {
    $user = User::factory()->create(['password' => bcrypt('senhacerta'), 'ativo' => true]);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'errada']);
    }

    // 6ª tentativa, agora com a senha CORRETA, deve ser barrada pelo rate limiter
    $this->post(route('login.post'), ['email' => $user->email, 'password' => 'senhacerta'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

// ---------- Headers de segurança ----------
test('respostas web trazem headers de segurança', function () {
    Salao::factory()->create(['ativo' => true]);

    $this->get('/')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

// ---------- Webhook: assinatura ----------
test('webhook rejeita requisição sem assinatura quando o secret está configurado', function () {
    config(['manicure.pagamento.mercadopago.webhook_secret' => 'segredo-teste']);

    $this->postJson(route('webhooks.mercadopago'), ['type' => 'payment', 'data' => ['id' => '123']])
        ->assertStatus(401);
});

test('webhook aceita assinatura HMAC válida', function () {
    config([
        'manicure.pagamento.mercadopago.webhook_secret' => 'segredo-teste',
        'manicure.pagamento.mercadopago.access_token' => 'TOKEN',
    ]);
    Http::fake(['api.mercadopago.com/*' => Http::response(['id' => 123, 'status' => 'pending'], 200)]);

    $dataId = '123';
    $reqId = 'req-abc';
    $ts = (string) time();
    $v1 = hash_hmac('sha256', "id:{$dataId};request-id:{$reqId};ts:{$ts};", 'segredo-teste');

    $this->withHeaders([
        'x-signature' => "ts={$ts},v1={$v1}",
        'x-request-id' => $reqId,
    ])->postJson(route('webhooks.mercadopago') . '?data.id=' . $dataId, [
        'type' => 'payment',
        'data' => ['id' => $dataId],
    ])->assertOk();
});

// ---------- Content-Security-Policy ----------
test('resposta web traz CSP com nonce e sem unsafe-inline no script-src', function () {
    Salao::factory()->create(['ativo' => true]);

    $resp = $this->get('/');
    $resp->assertOk();

    $csp = $resp->headers->get('Content-Security-Policy');
    expect($csp)->not->toBeNull();
    expect(str_contains($csp, "default-src 'self'"))->toBeTrue();
    expect(str_contains($csp, "script-src 'self' 'nonce-"))->toBeTrue();

    // O script-src não pode liberar execução inline arbitrária.
    $scriptSrc = collect(explode(';', $csp))->first(fn ($d) => str_contains($d, 'script-src'));
    expect(str_contains($scriptSrc, "'unsafe-inline'"))->toBeFalse();

    // Scripts inline da página recebem o nonce (reescrita do corpo).
    $resp->assertSee('nonce="', false);
});

test('CSP pode ser desativada por configuração', function () {
    config(['manicure.security.csp_enabled' => false]);
    Salao::factory()->create(['ativo' => true]);

    $this->get('/')->assertHeaderMissing('Content-Security-Policy');
});
