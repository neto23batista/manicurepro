<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Throttle + honeypot for guest/public POST surfaces without rewriting routes/web.php.
 *
 * Public booking abuse surface: slot hold (already route-throttled), auth forms,
 * and authenticated public booking submit.
 */
class ProtectPublicForms
{
    /** @var array<string, array{0: int, 1: int}> route name => [maxAttempts, decayMinutes] */
    private const THROTTLE = [
        'login.post'      => [20, 1],
        'register.post'   => [5, 1],
        'password.email'  => [3, 1],
        'password.update' => [5, 1],
        // public.agendar.store already has throttle:8,1 on the route
        'public.slots.hold'          => [20, 1],
        'cliente.agendamentos.store' => [20, 1],
    ];

    /** Routes that must keep the honeypot field empty. */
    private const HONEYPOT = [
        'login.post',
        'register.post',
        'password.email',
        'password.update',
        'public.agendar.store',
        'cliente.agendamentos.store',
    ];

    public const HONEYPOT_FIELD = 'website';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        $name = $request->route()?->getName();

        if (! $name) {
            return $next($request);
        }

        if (in_array($name, self::HONEYPOT, true) && filled($request->input(self::HONEYPOT_FIELD))) {
            throw ValidationException::withMessages([
                self::HONEYPOT_FIELD => 'Envio rejeitado.',
            ]);
        }

        if (isset(self::THROTTLE[$name])) {
            [$max, $decayMinutes] = self::THROTTLE[$name];
            $key = 'public-form:'.$name.':'.$request->ip();

            if (RateLimiter::tooManyAttempts($key, $max)) {
                abort(429, 'Muitas tentativas. Aguarde um momento e tente novamente.');
            }

            RateLimiter::hit($key, $decayMinutes * 60);
        }

        return $next($request);
    }
}
