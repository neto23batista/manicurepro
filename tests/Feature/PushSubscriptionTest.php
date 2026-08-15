<?php

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishlink\WebPush\WebPush;

uses(RefreshDatabase::class);

test('usuário autenticado pode salvar push subscription', function () {
    $user = User::factory()->create(['ativo' => true]);

    $payload = [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint-abc',
        'keys'     => [
            'p256dh' => 'BNcRdytQsLG_p...',
            'auth'   => 'tBHItJI5svbpez7KI4CCXg==',
        ],
        'contentEncoding' => 'aes128gcm',
    ];

    $this->actingAs($user)
        ->postJson(route('push-subscriptions.store'), $payload)
        ->assertCreated()
        ->assertJson(['ok' => true]);

    $this->assertDatabaseHas('push_subscriptions', [
        'user_id'          => $user->id,
        'endpoint'         => $payload['endpoint'],
        'public_key'       => $payload['keys']['p256dh'],
        'auth_token'       => $payload['keys']['auth'],
        'content_encoding' => 'aes128gcm',
    ]);
});

test('salvar o mesmo endpoint atualiza a subscription existente', function () {
    $user = User::factory()->create(['ativo' => true]);
    $endpoint = 'https://updates.push.services.mozilla.com/wpush/v2/gAAAAA';

    PushSubscription::create([
        'user_id'          => $user->id,
        'endpoint'         => $endpoint,
        'public_key'       => 'old-key',
        'auth_token'       => 'old-auth',
        'content_encoding' => 'aesgcm',
    ]);

    $this->actingAs($user)
        ->postJson(route('push-subscriptions.store'), [
            'endpoint' => $endpoint,
            'keys'     => [
                'p256dh' => 'new-key',
                'auth'   => 'new-auth',
            ],
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect(PushSubscription::where('endpoint', $endpoint)->count())->toBe(1);
    $this->assertDatabaseHas('push_subscriptions', [
        'endpoint'   => $endpoint,
        'public_key' => 'new-key',
        'auth_token' => 'new-auth',
    ]);
});

test('convidado não pode salvar push subscription', function () {
    $this->postJson(route('push-subscriptions.store'), [
        'endpoint' => 'https://example.com/push/1',
        'keys'     => ['p256dh' => 'x', 'auth' => 'y'],
    ])->assertUnauthorized();
});

test('validação exige endpoint e keys', function () {
    $user = User::factory()->create(['ativo' => true]);

    $this->actingAs($user)
        ->postJson(route('push-subscriptions.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);
});

test('usuário pode remover a própria push subscription', function () {
    $user = User::factory()->create(['ativo' => true]);
    $endpoint = 'https://fcm.googleapis.com/fcm/send/to-delete';

    PushSubscription::create([
        'user_id'    => $user->id,
        'endpoint'   => $endpoint,
        'public_key' => 'k',
        'auth_token' => 'a',
    ]);

    $this->actingAs($user)
        ->deleteJson(route('push-subscriptions.destroy'), ['endpoint' => $endpoint])
        ->assertOk()
        ->assertJson(['ok' => true]);

    $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $endpoint]);
});

test('WebPushService::sendToUser é no-op sem VAPID', function () {
    config([
        'manicure.webpush.subscribe_ui'      => true,
        'manicure.webpush.vapid.public_key'  => null,
        'manicure.webpush.vapid.private_key' => null,
    ]);

    $user = User::factory()->create();
    PushSubscription::create([
        'user_id'    => $user->id,
        'endpoint'   => 'https://example.com/push/x',
        'public_key' => 'k',
        'auth_token' => 'a',
    ]);

    $sent = app(WebPushService::class)->sendToUser($user, 'Título', 'Corpo');

    expect($sent)->toBe(0);
    expect(app(WebPushService::class)->configurado())->toBeFalse();
});

test('WebPushService::envioDisponivel exige UI + VAPID + pacote', function () {
    config([
        'manicure.webpush.subscribe_ui'      => false,
        'manicure.webpush.vapid.public_key'  => 'BNcRdytQsLG',
        'manicure.webpush.vapid.private_key' => 'private',
    ]);

    expect(app(WebPushService::class)->envioDisponivel())->toBeFalse();

    config(['manicure.webpush.subscribe_ui' => true]);

    $disponivel = app(WebPushService::class)->envioDisponivel();
    expect($disponivel)->toBe(class_exists(WebPush::class));
});
