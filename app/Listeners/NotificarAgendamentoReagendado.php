<?php

namespace App\Listeners;

use App\Events\AgendamentoReagendado as AgendamentoReagendadoEvent;
use App\Notifications\AgendamentoReagendado as AgendamentoReagendadoNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificarAgendamentoReagendado implements ShouldQueue
{
    public function handle(AgendamentoReagendadoEvent $event): void
    {
        $agendamento = $event->agendamento;

        $userCliente = $agendamento->user;
        if ($userCliente?->email) {
            $userCliente->notify(new AgendamentoReagendadoNotification($agendamento, $event->dataAnterior));
        }

        $userManicure = $agendamento->manicure?->user;
        if ($userManicure?->email) {
            $userManicure->notify(new AgendamentoReagendadoNotification($agendamento, $event->dataAnterior));
        }
    }
}
