<?php

use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use App\Notifications\Messages\WhatsAppMessage;
use App\Support\WhatsApp;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

it('normaliza telefones brasileiros com DDI', function () {
    config(['manicure.whatsapp.ddi_padrao' => '55']);

    expect(WhatsApp::normalizarTelefone('(11) 98888-7777'))->toBe('5511988887777');
    expect(WhatsApp::normalizarTelefone('5511988887777'))->toBe('5511988887777');
    expect(WhatsApp::normalizarTelefone('123'))->toBeNull();
    expect(WhatsApp::normalizarTelefone(null))->toBeNull();
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
        'manicure.whatsapp.token' => 'TESTE',
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
    config(['manicure.whatsapp.enabled' => false]);
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
