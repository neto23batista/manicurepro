<?php

namespace App\Listeners;

use App\Events\AgendamentoCriado;
use App\Notifications\AgendamentoConfirmado;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificarAgendamentoCriado implements ShouldQueue
{
    public function handle(AgendamentoCriado $event): void
    {
        $agendamento = $event->agendamento;

        // Notifica o cliente (usa User vinculado se houver)
        $userCliente = $agendamento->user;
        if ($userCliente?->email) {
            $userCliente->notify(new AgendamentoConfirmado($agendamento));
        }

        // Notifica a manicure (via User da manicure)
        $userManicure = $agendamento->manicure?->user;
        if ($userManicure?->email) {
            $userManicure->notify(new AgendamentoConfirmado($agendamento));
        }
    }
}
