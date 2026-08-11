<?php

namespace App\Policies;

use App\Models\GaleriaFoto;
use App\Models\User;

class GaleriaFotoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isDono() || $user->isAtendente();
    }

    public function view(User $user, GaleriaFoto $foto): bool
    {
        return $foto->salao_id === $user->salao_id;
    }

    public function create(User $user): bool
    {
        return ($user->isDono() || $user->isAtendente()) && $user->salao_id !== null;
    }

    public function update(User $user, GaleriaFoto $foto): bool
    {
        return $this->view($user, $foto);
    }

    public function delete(User $user, GaleriaFoto $foto): bool
    {
        return $this->view($user, $foto);
    }
}
