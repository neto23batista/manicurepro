<?php

namespace App\Policies;

use App\Models\Cupom;
use App\Models\User;

class CupomPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isDono() || $user->isAtendente();
    }

    public function view(User $user, Cupom $cupom): bool
    {
        return $cupom->salao_id === $user->salao_id;
    }

    public function create(User $user): bool
    {
        return ($user->isDono() || $user->isAtendente()) && $user->salao_id !== null;
    }

    public function update(User $user, Cupom $cupom): bool
    {
        return $this->view($user, $cupom);
    }

    public function delete(User $user, Cupom $cupom): bool
    {
        return $this->view($user, $cupom);
    }
}
