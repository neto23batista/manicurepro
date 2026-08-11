<?php

namespace App\Policies;

use App\Models\Folga;
use App\Models\User;

class FolgaPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isDono() || $user->isAtendente();
    }

    public function view(User $user, Folga $folga): bool
    {
        return $folga->salao_id === $user->salao_id;
    }

    public function create(User $user): bool
    {
        return ($user->isDono() || $user->isAtendente()) && $user->salao_id !== null;
    }

    public function delete(User $user, Folga $folga): bool
    {
        return $this->view($user, $folga);
    }
}
