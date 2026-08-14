<?php

namespace App\Policies;

use App\Models\Fornecedor;
use App\Models\User;

class FornecedorPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isDono() || $user->isAtendente();
    }

    public function view(User $user, Fornecedor $fornecedor): bool
    {
        if (! $user->isDono() && ! $user->isAtendente()) {
            return false;
        }

        return $fornecedor->salao_id === $user->salao_id;
    }

    public function create(User $user): bool
    {
        return ($user->isDono() || $user->isAtendente()) && $user->salao_id !== null;
    }

    public function update(User $user, Fornecedor $fornecedor): bool
    {
        return $this->view($user, $fornecedor);
    }

    public function delete(User $user, Fornecedor $fornecedor): bool
    {
        return $this->view($user, $fornecedor);
    }
}
