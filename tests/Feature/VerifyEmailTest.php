<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('usuário não-verificado vê tela de verificação', function () {
    $user = User::factory()->unverified()->create(['ativo' => true]);

    $r = $this->actingAs($user)->get('/email/verify');
    $r->assertOk();
    $r->assertSee('Verifique seu e-mail');
});

test('usuário não-verificado é redirecionado do dashboard do cliente para verification.notice', function () {
    $user = User::factory()->unverified()->create(['role' => 'cliente', 'ativo' => true]);

    $this->actingAs($user)
        ->get('/cliente/dashboard')
        ->assertRedirect(route('verification.notice'));
});

test('middleware verified redireciona não-verificados dos painéis protegidos', function (string $role, string $uri) {
    $attrs = ['role' => $role, 'ativo' => true];
    if (in_array($role, ['dono', 'atendente', 'manicure'], true)) {
        $salao = \App\Models\Salao::factory()->create(['ativo' => true]);
        $attrs['salao_id'] = $salao->id;
    }

    $user = User::factory()->unverified()->create($attrs);

    $this->actingAs($user)
        ->get($uri)
        ->assertRedirect(route('verification.notice'));
})->with([
    ['cliente', '/cliente/dashboard'],
    ['cliente', '/cliente/agendamentos'],
    ['dono', '/dono/dashboard'],
    ['dono', '/dono/financeiro'],
    ['atendente', '/dono/agendamentos'],
    ['manicure', '/manicure/dashboard'],
    ['admin', '/admin/dashboard'],
]);

test('usuário já verificado é redirecionado ao dashboard', function () {
    $user = User::factory()->create(['ativo' => true, 'role' => 'cliente']);
    // create() default já vem verified pelo factory

    $this->actingAs($user)->get('/email/verify')
        ->assertRedirect(route('cliente.dashboard'));
});

test('link de verificação assinado marca e-mail como verificado', function () {
    Event::fake();
    $user = User::factory()->unverified()->create(['role' => 'cliente', 'ativo' => true]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->actingAs($user)->get($url)->assertRedirect(route('cliente.dashboard'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

test('reenviar e-mail dispara notificação', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create(['ativo' => true]);

    $this->actingAs($user)->post('/email/verification-notification')
        ->assertRedirect();

    Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
});

test('throttle bloqueia reenvio excessivo', function () {
    $user = User::factory()->unverified()->create(['ativo' => true]);

    for ($i = 0; $i < 6; $i++) {
        $this->actingAs($user)->post('/email/verification-notification');
    }

    $this->actingAs($user)->post('/email/verification-notification')
        ->assertStatus(429);
});

test('registro dispara evento Registered', function () {
    Event::fake();
    $this->post('/register', [
        'name'                  => 'Nova',
        'email'                 => 'nova@x.com',
        'password'              => 'senha12345',
        'password_confirmation' => 'senha12345',
    ]);

    Event::assertDispatched(\Illuminate\Auth\Events\Registered::class);
});
