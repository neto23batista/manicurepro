<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ValePresente;
use App\Services\PermissionService;

class ValePresentePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $this->podeGerenciar($user);
    }

    public function view(User $user, ValePresente $vale): bool
    {
        return $this->podeGerenciar($user) && $vale->salao_id === $user->salao_id;
    }

    public function create(User $user): bool
    {
        return $this->podeGerenciar($user);
    }

    public function update(User $user, ValePresente $vale): bool
    {
        return $this->view($user, $vale);
    }

    public function delete(User $user, ValePresente $vale): bool
    {
        return $this->view($user, $vale);
    }

    private function podeGerenciar(User $user): bool
    {
        if ($user->isDono() && $user->salao_id !== null) {
            return true;
        }

        return app(PermissionService::class)->hasGrant($user, 'vales.manage');
    }
}
