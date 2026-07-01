<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSalao
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) return redirect()->route('login');

        // Super admin não precisa de salão vinculado
        if ($user->isSuperAdmin()) return $next($request);

        if (!$user->salao_id) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Sua conta não está vinculada a um salão.']);
        }

        if (!$user->salao || !$user->salao->ativo) {
            return redirect()->route('login')
                ->withErrors(['email' => 'O salão associado à sua conta está inativo.']);
        }

        return $next($request);
    }
}
