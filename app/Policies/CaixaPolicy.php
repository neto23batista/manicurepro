<?php

namespace App\Policies;

use App\Models\Caixa;
use App\Models\User;
use App\Services\PermissionService;

class CaixaPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $this->pode($user, 'financeiro.caixa');
    }

    public function view(User $user, Caixa $caixa): bool
    {
        return $this->pode($user, 'financeiro.caixa') && $caixa->salao_id === $user->salao_id;
    }

    public function create(User $user): bool
    {
        return $this->pode($user, 'financeiro.caixa');
    }

    public function update(User $user, Caixa $caixa): bool
    {
        return $this->view($user, $caixa);
    }

    public function delete(User $user, Caixa $caixa): bool
    {
        return false;
    }

    private function pode(User $user, string $permission): bool
    {
        if ($user->isDono() && $user->salao_id !== null) {
            return true;
        }

        return app(PermissionService::class)->hasGrant($user, $permission);
    }
}
