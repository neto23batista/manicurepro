<?php

namespace App\Policies;

use App\Models\Despesa;
use App\Models\User;
use App\Services\PermissionService;

class DespesaPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $this->pode($user);
    }

    public function view(User $user, Despesa $despesa): bool
    {
        return $this->pode($user) && $despesa->salao_id === $user->salao_id;
    }

    public function create(User $user): bool
    {
        return $this->pode($user);
    }

    public function update(User $user, Despesa $despesa): bool
    {
        return $this->view($user, $despesa);
    }

    public function delete(User $user, Despesa $despesa): bool
    {
        return $this->view($user, $despesa);
    }

    private function pode(User $user): bool
    {
        if ($user->isDono() && $user->salao_id !== null) {
            return true;
        }

        return app(PermissionService::class)->hasGrant($user, 'financeiro.despesas');
    }
}
