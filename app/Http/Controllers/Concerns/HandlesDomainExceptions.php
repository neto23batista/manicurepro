<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Padroniza o tratamento de exceções de domínio nos controllers:
 * ValidationException / AuthorizationException sobem; demais são
 * reportadas e viram mensagem genérica ao usuário (sem vazar getMessage).
 */
trait HandlesDomainExceptions
{
    /**
     * Re-lança exceções que o framework deve tratar; reporta as demais.
     *
     * @throws ValidationException
     * @throws AuthorizationException
     */
    protected function reportUnlessFramework(Throwable $e): void
    {
        if ($e instanceof ValidationException || $e instanceof AuthorizationException) {
            throw $e;
        }

        report($e);
    }

    /**
     * Resposta web: back() com erro genérico (opcionalmente com input).
     *
     * @throws ValidationException
     * @throws AuthorizationException
     */
    protected function domainExceptionBack(
        Throwable $e,
        string $message = 'Não foi possível concluir a operação. Tente novamente.',
        bool $withInput = false,
    ): RedirectResponse {
        $this->reportUnlessFramework($e);

        $response = back()->withErrors(['error' => $message]);

        return $withInput ? $response->withInput() : $response;
    }

    /**
     * Resposta JSON com mensagem genérica.
     *
     * @throws ValidationException
     * @throws AuthorizationException
     */
    protected function domainExceptionJson(
        Throwable $e,
        string $message = 'Não foi possível concluir a operação. Tente novamente.',
        int $status = 422,
    ): JsonResponse {
        $this->reportUnlessFramework($e);

        return \App\Support\ApiError::make($message, $status, 'domain_error');
    }
}
