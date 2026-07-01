<?php

namespace App\Events;

use App\Models\Agendamento;
use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgendamentoReagendado
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Agendamento $agendamento,
        public Carbon $dataAnterior,
    ) {}
}
