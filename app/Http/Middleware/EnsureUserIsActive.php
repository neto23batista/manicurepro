<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->ativo) {
            $user->currentAccessToken()->delete();

            return \App\Support\ApiError::make('Conta inativa.', 401, 'inactive');
        }

        return $next($request);
    }
}
