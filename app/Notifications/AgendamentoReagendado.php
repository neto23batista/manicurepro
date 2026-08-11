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
    use FormataAgendamentoMail, Queueable;

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
        $mail = $this->baseMail('Agendamento remarcado', $notifiable)
            ->line('Seu horário na **'.$this->brandName().'** foi remarcado.')
            ->line('**Antes:** '.$this->dataAnterior->format('d/m/Y H:i'));

        $this->appendAgendamentoLines($mail);

        return $mail
            ->action('Confirmar novo horário', $this->linkConfirmacao())
            ->line('O botão usa um link seguro e assinado — sem precisar fazer login.')
            ->line('[Ver detalhes do agendamento]('.$this->urlVerAgendamento().')');
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload(
            'Agendamento Remarcado',
            'Novo horário: '.$this->agendamento->data_hora_inicio->format('d/m/Y H:i')
                .' (antes: '.$this->dataAnterior->format('d/m/Y H:i').').',
        );
    }
}
