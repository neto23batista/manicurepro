<?php

use App\Http\Middleware\CheckSalao;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\RoleOrPermissionMiddleware;
use App\Support\ApiError;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'         => RoleMiddleware::class,
            'role_or_perm' => RoleOrPermissionMiddleware::class,
            'check.salao'  => CheckSalao::class,
            'user.active'  => EnsureUserIsActive::class,
        ]);

        $middleware->web(append: [
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\ContentSecurityPolicy::class,
            \App\Http\Middleware\ProtectPublicForms::class,
        ]);

        // Webhooks externos não enviam token CSRF
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return ApiError::make(
                    $e->getMessage() ?: 'Os dados fornecidos são inválidos.',
                    $e->status,
                    'validation_error',
                    $e->errors(),
                );
            }

            if ($e instanceof AuthenticationException) {
                return ApiError::make('Não autenticado.', 401, 'unauthenticated');
            }

            if ($e instanceof AuthorizationException) {
                return ApiError::make(
                    $e->getMessage() ?: 'Esta ação não é autorizada.',
                    403,
                    'forbidden',
                );
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return ApiError::make('Recurso não encontrado.', 404, 'not_found');
            }

            if ($e instanceof TooManyRequestsHttpException) {
                return ApiError::make('Muitas requisições. Tente novamente em instantes.', 429, 'too_many_requests');
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                $message = $e->getMessage() !== ''
                    ? $e->getMessage()
                    : (string) (\Symfony\Component\HttpFoundation\Response::$statusTexts[$status] ?? 'Erro');

                // Em 5xx não vaza detalhe interno.
                if ($status >= 500) {
                    report($e);

                    return ApiError::make('Erro interno do servidor.', $status, 'server_error');
                }

                return ApiError::make($message, $status, 'http_error');
            }

            report($e);

            return ApiError::make('Erro interno do servidor.', 500, 'server_error');
        });
    })->create();
