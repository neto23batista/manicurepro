<?php

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('codeAt segue o vetor de teste RFC 6238 (SHA1)', function () {
    $totp = new TotpService();
    // Base32 de "12345678901234567890"
    $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
    // RFC 6238: T=59s → slice 1 → 94287082 (6 dígitos = 287082)
    expect($totp->codeAt($secret, 1))->toBe('287082');
});

test('usuário ativa 2FA com um código válido', function () {
    $totp = app(TotpService::class);
    $user = User::factory()->create(['role' => 'cliente']);

    $this->actingAs($user)->get(route('2fa.setup'))->assertOk();

    $secret = session('2fa:setup_secret');
    expect($secret)->not->toBeNull();

    $code = $totp->codeAt($secret, (int) floor(time() / 30));

    $this->actingAs($user)
        ->post(route('2fa.enable'), ['code' => $code])
        ->assertRedirect(route('2fa.setup'));

    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

test('login com 2FA exige o desafio antes de autenticar', function () {
    $totp = app(TotpService::class);
    $secret = $totp->generateSecret();

    $user = User::factory()->create([
        'role' => 'cliente',
        'password' => bcrypt('senha123'),
        'ativo' => true,
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ]);

    // Senha correta NÃO autentica direto — vai para o desafio
    $this->post(route('login.post'), ['email' => $user->email, 'password' => 'senha123'])
        ->assertRedirect(route('2fa.challenge'));
    $this->assertGuest();

    // Código válido conclui o login
    $code = $totp->codeAt($secret, (int) floor(time() / 30));
    $this->post(route('2fa.challenge.verify'), ['code' => $code])
        ->assertRedirect(route('cliente.dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('código 2FA inválido no desafio é rejeitado', function () {
    $totp = app(TotpService::class);
    $secret = $totp->generateSecret();
    $user = User::factory()->create([
        'role' => 'cliente', 'password' => bcrypt('senha123'), 'ativo' => true,
        'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(),
    ]);

    $this->post(route('login.post'), ['email' => $user->email, 'password' => 'senha123']);

    $this->post(route('2fa.challenge.verify'), ['code' => '000000'])
        ->assertSessionHasErrors('code');
    $this->assertGuest();
});
