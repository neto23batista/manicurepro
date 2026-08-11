<?php

namespace App\Notifications;

use App\Models\Agendamento;
use App\Notifications\Concerns\FormataAgendamentoMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgendamentoCancelado extends Notification implements ShouldQueue
{
    use FormataAgendamentoMail, Queueable;

    public function __construct(
        public Agendamento $agendamento,
        public string $motivo = ''
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->baseMail('Agendamento cancelado', $notifiable)
            ->line('Infelizmente seu agendamento na **'.$this->brandName().'** foi cancelado.')
            ->line('**Data:** '.$this->agendamento->data_hora_inicio->format('d/m/Y'))
            ->line('**Horário:** '.$this->agendamento->data_hora_inicio->format('H:i'));

        if ($this->motivo) {
            $mail->line('**Motivo:** '.$this->motivo);
        }

        return $mail
            ->action('Agendar novamente', $this->urlAgendarSalao())
            ->line('Pedimos desculpas pelo inconveniente. Estamos à disposição para remarcar.');
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload(
            'Agendamento Cancelado',
            'Seu agendamento de '.$this->agendamento->data_hora_inicio->format('d/m/Y H:i').' foi cancelado.',
        );
    }
}
