<?php

use App\Http\Controllers\Concerns\HandlesDomainExceptions;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->handler = new class
    {
        use HandlesDomainExceptions;

        public function back(Throwable $e, string $message, bool $withInput = false)
        {
            return $this->domainExceptionBack($e, $message, $withInput);
        }

        public function json(Throwable $e, string $message, int $status = 422)
        {
            return $this->domainExceptionJson($e, $message, $status);
        }
    };
});

test('domainExceptionBack reporta exceção e devolve mensagem genérica sem vazar getMessage', function () {
    Log::spy();

    // Simula request web para back()
    app()->instance('request', Request::create('/teste', 'POST'));

    $secreta = 'SQLSTATE[HY000]: segredo interno';
    $response = $this->handler->back(new RuntimeException($secreta), 'Não foi possível criar o agendamento.');

    expect($response->getSession()->get('errors')->first('error'))
        ->toBe('Não foi possível criar o agendamento.')
        ->and($response->getSession()->get('errors')->first('error'))
        ->not->toContain('SQLSTATE')
        ->not->toContain('segredo');

    Log::shouldHaveReceived('error')->once();
});

test('domainExceptionJson reporta exceção e devolve mensagem genérica', function () {
    Log::spy();

    $response = $this->handler->json(
        new RuntimeException('detalhe interno sensível'),
        'Não foi possível criar o agendamento.',
    );

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toBe([
            'message' => 'Não foi possível criar o agendamento.',
            'code'    => 'domain_error',
        ]);

    Log::shouldHaveReceived('error')->once();
});

test('ValidationException é re-lançada', function () {
    $e = ValidationException::withMessages(['campo' => 'inválido']);

    expect(fn () => $this->handler->json($e, 'genérico'))
        ->toThrow(ValidationException::class);
});

test('AuthorizationException é re-lançada', function () {
    expect(fn () => $this->handler->json(new AuthorizationException('negado'), 'genérico'))
        ->toThrow(AuthorizationException::class);
});
