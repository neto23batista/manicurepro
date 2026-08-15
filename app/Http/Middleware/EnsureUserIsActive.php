<?php

namespace App\Http\Middleware;

use App\Support\ApiError;
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

            return ApiError::make('Conta inativa.', 401, 'inactive');
        }

        return $next($request);
    }
}
