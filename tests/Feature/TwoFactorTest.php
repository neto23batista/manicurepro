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

test('gera e consome códigos de recuperação com hash', function () {
    $totp = new TotpService();
    $plain = $totp->generateRecoveryCodes(8);

    expect($plain)->toHaveCount(8)
        ->and($plain[0])->toMatch('/^[A-Z2-7]{4}-[A-Z2-7]{4}$/');

    $hashed = $totp->hashRecoveryCodes($plain);
    expect($hashed)->toHaveCount(8)
        ->and($hashed[0])->not->toBe($plain[0]);

    $remaining = $totp->consumeRecoveryCode($hashed, strtolower(str_replace('-', '', $plain[0])));
    expect($remaining)->toHaveCount(7)
        ->and($totp->consumeRecoveryCode($remaining, $plain[0]))->toBeNull();
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
        ->assertRedirect(route('2fa.setup'))
        ->assertSessionHas('2fa_recovery_codes');

    $user->refresh();
    expect($user->hasTwoFactorEnabled())->toBeTrue()
        ->and($user->two_factor_recovery_codes)->toHaveCount(8);

    $plain = session('2fa_recovery_codes');
    expect($plain)->toHaveCount(8)
        ->and($user->two_factor_recovery_codes)->not->toContain($plain[0]);
});

test('ativar 2FA não regenera códigos se já existirem', function () {
    $totp = app(TotpService::class);
    $existing = $totp->hashRecoveryCodes(['AAAA-BBBB', 'CCCC-DDDD']);
    $user = User::factory()->create([
        'role' => 'cliente',
        'two_factor_recovery_codes' => $existing,
    ]);

    $this->actingAs($user)->get(route('2fa.setup'));
    $secret = session('2fa:setup_secret');
    $code = $totp->codeAt($secret, (int) floor(time() / 30));

    $this->actingAs($user)
        ->post(route('2fa.enable'), ['code' => $code])
        ->assertRedirect(route('2fa.setup'))
        ->assertSessionMissing('2fa_recovery_codes');

    expect($user->fresh()->two_factor_recovery_codes)->toBe($existing);
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

test('código de recuperação válido conclui o desafio 2FA e é consumido', function () {
    $totp = app(TotpService::class);
    $secret = $totp->generateSecret();
    $plain = $totp->generateRecoveryCodes(2);

    $user = User::factory()->create([
        'role' => 'cliente',
        'password' => bcrypt('senha123'),
        'ativo' => true,
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => $totp->hashRecoveryCodes($plain),
    ]);

    $this->post(route('login.post'), ['email' => $user->email, 'password' => 'senha123'])
        ->assertRedirect(route('2fa.challenge'));

    $this->post(route('2fa.challenge.verify'), ['code' => $plain[0]])
        ->assertRedirect(route('cliente.dashboard'));
    $this->assertAuthenticatedAs($user);

    expect($user->fresh()->two_factor_recovery_codes)->toHaveCount(1);

    auth()->logout();
    $this->post(route('login.post'), ['email' => $user->email, 'password' => 'senha123']);
    $this->post(route('2fa.challenge.verify'), ['code' => $plain[0]])
        ->assertSessionHasErrors('code');
    $this->assertGuest();
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
