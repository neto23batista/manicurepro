<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\PermissionService;

class AuditLogPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        if ($user->isDono() && $user->salao_id !== null) {
            return true;
        }

        return app(PermissionService::class)->hasGrant($user, 'auditoria.view');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $this->viewAny($user);
    }
}
