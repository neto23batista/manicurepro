<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Hierarquia de roles: cada chave herda automaticamente as permissões dos valores.
     * 'admin' engloba todos os perfis subordinados.
     */
    private const HIERARCHY = [
        'admin'     => [UserRole::Admin, UserRole::Dono, UserRole::Atendente, UserRole::Manicure, UserRole::Cliente],
        'dono'      => [UserRole::Dono, UserRole::Atendente, UserRole::Manicure, UserRole::Cliente],
        'atendente' => [UserRole::Atendente, UserRole::Cliente],
        'manicure'  => [UserRole::Manicure],
        'cliente'   => [UserRole::Cliente],
    ];

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $userRole = $user->roleEnum();
        if (!$userRole) {
            abort(403, 'Acesso não autorizado.');
        }

        $allowed = self::HIERARCHY[$userRole->value] ?? [$userRole];

        foreach ($roles as $required) {
            $requiredEnum = UserRole::tryFrom($required);
            if ($requiredEnum && in_array($requiredEnum, $allowed, true)) {
                return $next($request);
            }
        }

        abort(403, 'Acesso não autorizado.');
    }
}
