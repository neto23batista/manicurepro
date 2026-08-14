<?php

namespace App\Policies;

use App\Models\Avaliacao;
use App\Models\User;

class AvaliacaoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isDono() || $user->isAtendente();
    }

    public function view(User $user, Avaliacao $avaliacao): bool
    {
        if (! $user->isDono() && ! $user->isAtendente()) {
            return false;
        }

        return $avaliacao->salao_id === $user->salao_id;
    }

    public function update(User $user, Avaliacao $avaliacao): bool
    {
        return $this->view($user, $avaliacao);
    }
}
