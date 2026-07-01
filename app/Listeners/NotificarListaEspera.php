<?php

namespace App\Listeners;

use App\Events\AgendamentoCanceladoEvent;
use App\Models\ListaEspera;
use App\Models\User;
use App\Notifications\VagaDisponivel;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificarListaEspera implements ShouldQueue
{
    public function handle(AgendamentoCanceladoEvent $event): void
    {
        $agendamento = $event->agendamento;
        $data = $agendamento->data_hora_inicio;

        $entradas = ListaEspera::with('cliente', 'user')
            ->where('salao_id', $agendamento->salao_id)
            ->where('status', 'aguardando')
            ->where(fn($q) => $q->whereNull('manicure_id')->orWhere('manicure_id', $agendamento->manicure_id))
            ->where(fn($q) => $q->whereNull('data_preferida')->orWhereDate('data_preferida', $data->toDateString()))
            ->orderBy('created_at')
            ->limit(5)
            ->get();

        foreach ($entradas as $entrada) {
            $user = $entrada->user
                ?? User::where('email', $entrada->cliente?->email)->first();

            if ($user?->email) {
                $user->notify(new VagaDisponivel($entrada, $data));
            }

            $entrada->update(['status' => 'notificado', 'notificado_em' => now()]);
        }
    }
}
