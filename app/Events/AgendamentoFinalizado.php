<?php

namespace App\Events;

use App\Models\Agendamento;
use App\Models\Comanda;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgendamentoFinalizado
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Agendamento $agendamento,
        public Comanda $comanda,
    ) {}
}
