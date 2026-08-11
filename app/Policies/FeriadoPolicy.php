<?php

namespace App\Policies;

use App\Models\Feriado;
use App\Models\User;

class FeriadoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isDono() || $user->isAtendente();
    }

    public function view(User $user, Feriado $feriado): bool
    {
        return $feriado->salao_id === $user->salao_id;
    }

    public function create(User $user): bool
    {
        return ($user->isDono() || $user->isAtendente()) && $user->salao_id !== null;
    }

    public function delete(User $user, Feriado $feriado): bool
    {
        return $this->view($user, $feriado);
    }
}
