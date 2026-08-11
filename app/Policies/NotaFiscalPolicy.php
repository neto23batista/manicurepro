<?php

namespace App\Policies;

use App\Models\NotaFiscal;
use App\Models\User;
use App\Services\PermissionService;

class NotaFiscalPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $this->podeGerenciar($user);
    }

    public function view(User $user, NotaFiscal $notaFiscal): bool
    {
        return $this->podeGerenciar($user) && $notaFiscal->salao_id === $user->salao_id;
    }

    public function create(User $user): bool
    {
        return $this->podeGerenciar($user);
    }

    private function podeGerenciar(User $user): bool
    {
        if ($user->isDono() && $user->salao_id !== null) {
            return true;
        }

        return app(PermissionService::class)->hasGrant($user, 'notas_fiscais.manage');
    }
}
