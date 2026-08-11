<?php

use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Messages\WhatsAppMessage;
use App\Support\WhatsApp;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['manicure.whatsapp.ddi_padrao' => '55']);
});

it('normaliza telefones brasileiros com DDI', function () {
    expect(WhatsApp::normalizarTelefone('(11) 98888-7777'))->toBe('5511988887777');
    expect(WhatsApp::normalizarTelefone('11988887777'))->toBe('5511988887777');
    expect(WhatsApp::normalizarTelefone('5511988887777'))->toBe('5511988887777');
    expect(WhatsApp::normalizarTelefone('+55 11 98888-7777'))->toBe('5511988887777');
});

it('normaliza edge cases BR (tronco, 00, fixo)', function () {
    // Zero de tronco local: 0 + DDD + número
    expect(WhatsApp::normalizarTelefone('011988887777'))->toBe('5511988887777');
    expect(WhatsApp::normalizarTelefone('(011) 98888-7777'))->toBe('5511988887777');

    // Zero de tronco após DDI já presente
    expect(WhatsApp::normalizarTelefone('55011988887777'))->toBe('5511988887777');

    // Prefixo internacional 00
    expect(WhatsApp::normalizarTelefone('005511988887777'))->toBe('5511988887777');

    // Fixo (8 dígitos locais)
    expect(WhatsApp::normalizarTelefone('(11) 3456-7890'))->toBe('551134567890');
    expect(WhatsApp::normalizarTelefone('1134567890'))->toBe('551134567890');
});

it('rejeita telefones inválidos', function () {
    expect(WhatsApp::normalizarTelefone(null))->toBeNull();
    expect(WhatsApp::normalizarTelefone(''))->toBeNull();
    expect(WhatsApp::normalizarTelefone('   '))->toBeNull();
    expect(WhatsApp::normalizarTelefone('123'))->toBeNull();
    expect(WhatsApp::normalizarTelefone('abc'))->toBeNull();
    expect(WhatsApp::normalizarTelefone('988887777'))->toBeNull(); // sem DDD
    expect(WhatsApp::normalizarTelefone('55988887777'))->toBeNull(); // DDI sem DDD
    expect(WhatsApp::normalizarTelefone('55119888877'))->toBeNull(); // nacional curto demais
});
it('monta payload de texto e de template', function () {
    $text = WhatsAppMessage::create('Oi')->toPayload('5511999');
    expect($text['type'])->toBe('text');
    expect($text['text']['body'])->toBe('Oi');

    $tpl = WhatsAppMessage::create()->template('lembrete', ['Ana', '13/06'])->toPayload('5511999');
    expect($tpl['type'])->toBe('template');
    expect($tpl['template']['name'])->toBe('lembrete');
    expect($tpl['template']['components'][0]['parameters'][0]['text'])->toBe('Ana');
});

it('envia para a Graph API quando habilitado', function () {
    config([
        'manicure.whatsapp.enabled' => true,
        'manicure.whatsapp.token' => 'TESTE-TOKEN-SECRETO',
        'manicure.whatsapp.phone_number_id' => '123456',
        'manicure.whatsapp.api_version' => 'v21.0',
    ]);

    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.x']]], 200)]);

    $user = User::factory()->make(['phone' => '(11) 98888-7777']);

    $notification = new class extends Notification {
        public function toWhatsApp($n): WhatsAppMessage
        {
            return WhatsAppMessage::create('Olá!');
        }
    };

    (new WhatsAppChannel)->send($user, $notification);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'graph.facebook.com/v21.0/123456/messages')
            && $request['to'] === '5511988887777'
            && $request['text']['body'] === 'Olá!';
    });
});

it('não envia quando a integração está desabilitada', function () {
    config([
        'manicure.whatsapp.enabled' => false,
        'manicure.whatsapp.token' => 'NAO-DEVE-USAR',
        'manicure.whatsapp.phone_number_id' => '123456',
    ]);
    Http::fake();

    $user = User::factory()->make(['phone' => '11988887777']);
    $notification = new class extends Notification {
        public function toWhatsApp($n): WhatsAppMessage
        {
            return WhatsAppMessage::create('x');
        }
    };

    (new WhatsAppChannel)->send($user, $notification);

    Http::assertNothingSent();
});

it('não envia quando habilitado mas sem credenciais', function () {
    config([
        'manicure.whatsapp.enabled' => true,
        'manicure.whatsapp.token' => null,
        'manicure.whatsapp.phone_number_id' => '123456',
    ]);
    Http::fake();

    $user = User::factory()->make(['phone' => '11988887777']);
    $notification = new class extends Notification {
        public function toWhatsApp($n): WhatsAppMessage
        {
            return WhatsAppMessage::create('x');
        }
    };

    (new WhatsAppChannel)->send($user, $notification);

    Http::assertNothingSent();
});

it('loga falha da API sem vazar o token', function () {
    $token = 'SUPER-SECRET-WHATSAPP-TOKEN-XYZ';

    config([
        'manicure.whatsapp.enabled' => true,
        'manicure.whatsapp.token' => $token,
        'manicure.whatsapp.phone_number_id' => '123456',
        'manicure.whatsapp.api_version' => 'v21.0',
    ]);

    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'error' => [
                'message' => 'Invalid OAuth access token. Bearer '.$token,
                'type' => 'OAuthException',
                'code' => 190,
            ],
        ], 401),
    ]);

    Event::fake([MessageLogged::class]);

    $user = User::factory()->make(['phone' => '11988887777']);
    $notification = new class extends Notification {
        public function toWhatsApp($n): WhatsAppMessage
        {
            return WhatsAppMessage::create('Oi');
        }
    };

    (new WhatsAppChannel)->send($user, $notification);

    Event::assertDispatched(MessageLogged::class, function (MessageLogged $log) use ($token) {
        return $log->level === 'warning'
            && str_contains($log->message, 'WhatsApp: falha ao enviar')
            && ($log->context['status'] ?? null) === 401
            && ($log->context['error_code'] ?? null) === 190
            && ! str_contains(json_encode($log->context), $token);
    });
});

it('falha com graça quando a Graph API lança exceção', function () {
    config([
        'manicure.whatsapp.enabled' => true,
        'manicure.whatsapp.token' => 'TOKEN-EXCECAO',
        'manicure.whatsapp.phone_number_id' => '123456',
    ]);

    Http::fake(function () {
        throw new \RuntimeException('cURL error with Bearer TOKEN-EXCECAO in message');
    });

    Event::fake([MessageLogged::class]);

    $user = User::factory()->make(['phone' => '11988887777']);
    $notification = new class extends Notification {
        public function toWhatsApp($n): WhatsAppMessage
        {
            return WhatsAppMessage::create('Oi');
        }
    };

    // Não deve propagar a exceção
    (new WhatsAppChannel)->send($user, $notification);

    Event::assertDispatched(MessageLogged::class, function (MessageLogged $log) {
        return $log->level === 'warning'
            && str_contains($log->message, 'WhatsApp: exceção ao enviar')
            && ! str_contains(json_encode($log->context), 'TOKEN-EXCECAO')
            && str_contains($log->context['erro'] ?? '', '[redacted]');
    });
});

it('falha com graça quando desabilitado mesmo se toWhatsApp quebrar', function () {
    config(['manicure.whatsapp.enabled' => false]);
    Http::fake();

    $user = User::factory()->make(['phone' => '11988887777']);
    $notification = new class extends Notification {
        public function toWhatsApp($n): WhatsAppMessage
        {
            throw new \RuntimeException('não deveria ser chamado');
        }
    };

    (new WhatsAppChannel)->send($user, $notification);

    Http::assertNothingSent();
});
