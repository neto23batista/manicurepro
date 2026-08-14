<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aceita a role (com a mesma hierarquia do RoleMiddleware) OU um grant extra.
 * Sem grants configurados, o comportamento é idêntico a role:dono (defaults intactos).
 *
 * Uso: role_or_perm:dono,financeiro.view
 */
class RoleOrPermissionMiddleware
{
    private const HIERARCHY = [
        'admin'     => [UserRole::Admin, UserRole::Dono, UserRole::Atendente],
        'dono'      => [UserRole::Dono, UserRole::Atendente],
        'atendente' => [UserRole::Atendente],
        'manicure'  => [UserRole::Manicure],
        'cliente'   => [UserRole::Cliente],
    ];

    public function __construct(private PermissionService $permissions) {}

    public function handle(Request $request, Closure $next, string $role, ?string $permission = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $userRole = $user->roleEnum();
        if (! $userRole) {
            abort(403, 'Acesso não autorizado.');
        }

        $allowed = self::HIERARCHY[$userRole->value] ?? [];
        $requiredEnum = UserRole::tryFrom($role);

        if ($requiredEnum && in_array($requiredEnum, $allowed, true)) {
            return $next($request);
        }

        // Grants extras só para atendente (staff operacional). Cliente/manicure nunca
        // herdam rotas do painel dono via JSON de permissões.
        if (
            $userRole === UserRole::Atendente
            && $permission
            && $this->permissions->hasGrant($user, $permission)
        ) {
            return $next($request);
        }

        abort(403, 'Acesso não autorizado.');
    }
}
