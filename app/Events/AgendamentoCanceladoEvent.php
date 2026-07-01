<?php

namespace App\Events;

use App\Models\Agendamento;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgendamentoCanceladoEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Agendamento $agendamento,
        public string $motivo = '',
        public ?string $canceladoPor = null,
    ) {}
}
