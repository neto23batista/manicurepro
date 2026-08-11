<?php

use App\Services\ProducaoChecker;

function checkItem(string $item): array
{
    return collect(app(ProducaoChecker::class)->verificar())->firstWhere('item', $item);
}

test('em produção, APP_DEBUG=true é erro crítico', function () {
    $this->app['env'] = 'production';
    config(['app.debug' => true]);

    expect(checkItem('Debug')['nivel'])->toBe(ProducaoChecker::ERRO);
    expect(app(ProducaoChecker::class)->temErroCritico())->toBeTrue();
});

test('fora de produção, APP_DEBUG=true é apenas aviso', function () {
    config(['app.debug' => true]);
    expect(checkItem('Debug')['nivel'])->toBe(ProducaoChecker::AVISO);
});

test('MAIL_MAILER=log gera aviso (e-mails não saem)', function () {
    config(['mail.default' => 'log']);
    expect(checkItem('E-mail')['nivel'])->toBe(ProducaoChecker::AVISO);
});

test('SMTP configurado fica OK', function () {
    config(['mail.default' => 'smtp']);
    expect(checkItem('E-mail')['nivel'])->toBe(ProducaoChecker::OK);
});

test('em produção, APP_URL sem https é erro', function () {
    $this->app['env'] = 'production';
    config(['app.url' => 'http://exemplo.com']);

    expect(checkItem('HTTPS')['nivel'])->toBe(ProducaoChecker::ERRO);
});

test('fila configurada (não-sync) fica OK', function () {
    config(['queue.default' => 'database']);
    expect(checkItem('Fila')['nivel'])->toBe(ProducaoChecker::OK);
});

test('fila sync gera aviso', function () {
    config(['queue.default' => 'sync']);
    expect(checkItem('Fila')['nivel'])->toBe(ProducaoChecker::AVISO);
});

test('em produção, MP_ENABLED sem webhook secret é erro crítico', function () {
    $this->app['env'] = 'production';
    config([
        'manicure.pagamento.mercadopago.enabled' => true,
        'manicure.pagamento.mercadopago.webhook_secret' => '',
    ]);

    expect(checkItem('MercadoPago')['nivel'])->toBe(ProducaoChecker::ERRO);
    expect(app(ProducaoChecker::class)->temErroCritico())->toBeTrue();
});

test('fora de produção, MP_ENABLED sem webhook secret é aviso', function () {
    config([
        'manicure.pagamento.mercadopago.enabled' => true,
        'manicure.pagamento.mercadopago.webhook_secret' => '',
    ]);

    expect(checkItem('MercadoPago')['nivel'])->toBe(ProducaoChecker::AVISO);
});

test('MP_ENABLED com webhook secret fica OK', function () {
    config([
        'manicure.pagamento.mercadopago.enabled' => true,
        'manicure.pagamento.mercadopago.webhook_secret' => 'segredo',
    ]);

    expect(checkItem('MercadoPago')['nivel'])->toBe(ProducaoChecker::OK);
});

test('o comando de verificação roda com sucesso fora de produção', function () {
    $this->artisan('manicure:verificar-producao')->assertSuccessful();
});
