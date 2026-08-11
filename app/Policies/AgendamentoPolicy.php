<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Agendamento;
use App\Models\User;

class AgendamentoPolicy
{
    /**
     * Admin tem acesso total.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function create(User $user): bool
    {
        return $user->isCliente()
            || $user->isDono()
            || $user->isAtendente();
    }

    public function view(User $user, Agendamento $agendamento): bool
    {
        // Cliente: dono via cliente_id OU user_id.
        // Nunca deixar null === null casar (cliente sem cadastro x agendamento de balcão).
        if ($user->isCliente()) {
            $clienteId = $user->cliente?->id;

            return ($clienteId !== null && $agendamento->cliente_id === $clienteId)
                || $agendamento->user_id === $user->id;
        }

        // Manicure: só os próprios agendamentos
        if ($user->isManicure()) {
            $manicureId = $user->manicure?->id;

            return $manicureId !== null && $agendamento->manicure_id === $manicureId;
        }

        // Dono / Atendente: salão correspondente
        return $agendamento->salao_id === $user->salao_id;
    }

    public function update(User $user, Agendamento $agendamento): bool
    {
        if ($user->isManicure()) {
            $manicureId = $user->manicure?->id;

            return $manicureId !== null && $agendamento->manicure_id === $manicureId;
        }

        return $agendamento->salao_id === $user->salao_id
            && in_array($user->roleEnum(), [UserRole::Dono, UserRole::Atendente], true);
    }

    public function cancel(User $user, Agendamento $agendamento): bool
    {
        if (! $agendamento->podeSerCancelado()) {
            return false;
        }

        return $this->view($user, $agendamento);
    }

    public function finalize(User $user, Agendamento $agendamento): bool
    {
        return $this->update($user, $agendamento);
    }
}
