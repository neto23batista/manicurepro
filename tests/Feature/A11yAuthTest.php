<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login expõe skip-link, landmark main e labels acessíveis', function () {
    $html = $this->get(route('login'))->assertOk()->getContent();

    expect($html)
        ->toContain('class="skip-link"')
        ->toContain('href="#mainContent"')
        ->toContain('<main')
        ->toContain('id="mainContent"')
        ->toContain('for="email"')
        ->toContain('for="password"')
        ->toContain('aria-label="Mostrar senha"')
        ->toContain('aria-required="true"');
});

test('login com erro de validação renderiza aria-invalid no e-mail', function () {
    $response = $this->from(route('login'))->followingRedirects()->post(route('login.post'), [
        'email'    => 'naoexiste@example.com',
        'password' => 'errada',
    ]);

    $response->assertOk();
    $html = $response->getContent();

    expect($html)
        ->toContain('role="alert"')
        ->toContain('aria-invalid="true"')
        ->toContain('aria-describedby="email-error"');
});
