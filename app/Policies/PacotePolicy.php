<?php

namespace App\Policies;

use App\Models\Pacote;
use App\Models\User;

class PacotePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return ($user->isDono() || $user->isAtendente()) && $user->salao_id !== null;
    }

    public function view(User $user, Pacote $pacote): bool
    {
        return $this->viewAny($user) && $pacote->salao_id === $user->salao_id;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Pacote $pacote): bool
    {
        return $this->view($user, $pacote);
    }

    public function delete(User $user, Pacote $pacote): bool
    {
        return $this->view($user, $pacote);
    }

    public function atribuir(User $user, Pacote $pacote): bool
    {
        return $this->view($user, $pacote);
    }
}
