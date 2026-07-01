<?php

namespace App\Notifications;

use App\Models\Agendamento;
use App\Notifications\Concerns\FormataAgendamentoMail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgendamentoReagendado extends Notification implements ShouldQueue
{
    use Queueable, FormataAgendamentoMail;

    public function __construct(
        public Agendamento $agendamento,
        public Carbon $dataAnterior,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->baseMail('Agendamento Remarcado', $notifiable)
            ->line('Seu agendamento foi remarcado.')
            ->line('**Antes:** ' . $this->dataAnterior->format('d/m/Y H:i'));

        $this->appendAgendamentoLines($mail);

        return $mail
            ->action('Ver Agendamento', route('cliente.agendamentos.show', $this->agendamento))
            ->line('Até breve! 💅');
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload(
            'Agendamento Remarcado',
            'Novo horário: ' . $this->agendamento->data_hora_inicio->format('d/m/Y H:i')
                . ' (antes: ' . $this->dataAnterior->format('d/m/Y H:i') . ').'
        );
    }
}
