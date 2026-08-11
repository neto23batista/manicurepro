<?php

namespace App\Policies;

use App\Models\FolgaManicure;
use App\Models\User;

class FolgaManicurePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isManicure() && $user->manicure !== null;
    }

    public function view(User $user, FolgaManicure $folga): bool
    {
        return $user->isManicure()
            && $folga->manicure_id === $user->manicure?->id;
    }

    public function create(User $user): bool
    {
        return $user->isManicure() && $user->manicure !== null;
    }

    public function delete(User $user, FolgaManicure $folga): bool
    {
        return $this->view($user, $folga);
    }
}
