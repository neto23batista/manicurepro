<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('tela de forgot password é pública', function () {
    $this->get('/password/forgot')->assertOk();
});

test('tela de reset é pública', function () {
    $this->get('/password/reset/qualquer-token?email=a@b.com')->assertOk();
});

test('solicitar reset envia notificação', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'reset@x.com', 'ativo' => true]);

    $this->post('/password/email', ['email' => 'reset@x.com'])
        ->assertSessionHas('success');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('solicitar reset para e-mail inexistente devolve erro', function () {
    $this->post('/password/email', ['email' => 'naoexiste@x.com'])
        ->assertSessionHasErrors(['email']);
});

test('reset de senha funciona com token válido', function () {
    $user = User::factory()->create([
        'email'    => 'r@x.com',
        'password' => Hash::make('antiga'),
        'ativo'    => true,
    ]);

    $token = Password::createToken($user);

    $this->post('/password/reset', [
        'token'                 => $token,
        'email'                 => 'r@x.com',
        'password'              => 'novasenha123',
        'password_confirmation' => 'novasenha123',
    ])->assertRedirect(route('login'));

    expect(Hash::check('novasenha123', $user->fresh()->password))->toBeTrue();
});

test('reset com token inválido falha', function () {
    User::factory()->create(['email' => 'r2@x.com', 'ativo' => true]);

    $this->post('/password/reset', [
        'token'                 => 'token-invalido',
        'email'                 => 'r2@x.com',
        'password'              => 'novasenha123',
        'password_confirmation' => 'novasenha123',
    ])->assertSessionHasErrors(['email']);
});

test('reset com senha curta falha', function () {
    $user = User::factory()->create(['email' => 'r3@x.com', 'ativo' => true]);
    $token = Password::createToken($user);

    $this->post('/password/reset', [
        'token'                 => $token,
        'email'                 => 'r3@x.com',
        'password'              => 'curt',
        'password_confirmation' => 'curt',
    ])->assertSessionHasErrors(['password']);
});

test('reset com confirmação errada falha', function () {
    $user = User::factory()->create(['email' => 'r4@x.com', 'ativo' => true]);
    $token = Password::createToken($user);

    $this->post('/password/reset', [
        'token'                 => $token,
        'email'                 => 'r4@x.com',
        'password'              => 'senha12345',
        'password_confirmation' => 'diferente12',
    ])->assertSessionHasErrors(['password']);
});
