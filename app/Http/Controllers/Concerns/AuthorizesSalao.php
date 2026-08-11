<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\UserRole;
use App\Models\Agendamento;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait reutilizável com helpers de ownership por salão / cliente / manicure.
 *
 * Preferir `$this->authorize()` + Policies para view/update/cancel.
 * Estes helpers permanecem para checks pontuais quando a Policy não cobre o caso.
 */
trait AuthorizesSalao
{
    /**
     * Garante que qualquer Model com coluna `salao_id` pertence ao salão do usuário.
     * Admin (super) ignora a verificação.
     */
    protected function authorizeSalaoOwnership(Model $resource): void
    {
        $user = auth()->user();

        if ($user === null) {
            abort(403, 'Você não tem acesso a este recurso.');
        }

        if ($user->roleEnum() === UserRole::Admin) {
            return;
        }

        $resourceSalaoId = (int) ($resource->salao_id ?? 0);
        $userSalaoId = (int) ($user->salao_id ?? 0);

        if ($resourceSalaoId === 0 || $resourceSalaoId !== $userSalaoId) {
            abort(403, 'Você não tem acesso a este recurso.');
        }
    }

    /**
     * Atalho histórico para Agendamentos — delega para o método genérico.
     */
    protected function authorizeSalaoAccess(Agendamento $agendamento): void
    {
        $this->authorizeSalaoOwnership($agendamento);
    }

    /**
     * Garante que o agendamento pertence ao cliente autenticado
     * (via cliente_id ou user_id direto).
     */
    protected function authorizeClienteAccess(Agendamento $agendamento): void
    {
        $user = auth()->user();
        $clienteId = $user?->cliente?->id;

        // IMPORTANTE: nunca deixar null === null casar (cliente sem cadastro x
        // agendamento de balcão sem cliente_id) — isso vazaria acesso cruzado.
        $isOwner = ($clienteId !== null && $agendamento->cliente_id === $clienteId)
                || ($user !== null && $agendamento->user_id === $user->id);

        if (! $isOwner) {
            abort(403, 'Você não tem acesso a este agendamento.');
        }
    }

    /**
     * Garante que o agendamento pertence à manicure autenticada.
     */
    protected function authorizeManicureAccess(Agendamento $agendamento): void
    {
        $manicureId = auth()->user()?->manicure?->id;

        if (! $manicureId || $agendamento->manicure_id !== $manicureId) {
            abort(403, 'Este agendamento não é seu.');
        }
    }
}
