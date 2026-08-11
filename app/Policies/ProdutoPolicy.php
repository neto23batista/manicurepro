<?php

namespace App\Policies;

use App\Models\Produto;
use App\Models\User;

class ProdutoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isDono() || $user->isAtendente();
    }

    public function view(User $user, Produto $produto): bool
    {
        return $produto->salao_id === $user->salao_id;
    }

    public function create(User $user): bool
    {
        return ($user->isDono() || $user->isAtendente()) && $user->salao_id !== null;
    }

    public function update(User $user, Produto $produto): bool
    {
        return $this->view($user, $produto);
    }

    public function delete(User $user, Produto $produto): bool
    {
        return $this->view($user, $produto);
    }
}
