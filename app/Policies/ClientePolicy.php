<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isDono() || $user->isAtendente();
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return $cliente->salao_id === $user->salao_id;
    }

    public function create(User $user): bool
    {
        return ($user->isDono() || $user->isAtendente()) && $user->salao_id !== null;
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $this->view($user, $cliente);
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $this->view($user, $cliente);
    }
}
