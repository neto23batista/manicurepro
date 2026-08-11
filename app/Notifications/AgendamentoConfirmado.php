<?php

namespace App\Notifications;

use App\Models\Agendamento;
use App\Notifications\Concerns\EnviaPorWhatsApp;
use App\Notifications\Concerns\FormataAgendamentoMail;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgendamentoConfirmado extends Notification implements ShouldQueue
{
    use EnviaPorWhatsApp, FormataAgendamentoMail, Queueable;

    public function __construct(public Agendamento $agendamento) {}

    public function via(object $notifiable): array
    {
        $canais = ['mail'];

        // AnonymousNotifiable (guest com e-mail) não persiste database notifications
        if (! $notifiable instanceof AnonymousNotifiable) {
            $canais[] = 'database';
        }

        return $this->comWhatsApp($canais);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $ag = $this->agendamento;
        $nome = $this->nomeNotifiable($notifiable);
        $template = config('manicure.whatsapp.templates.confirmado');

        if ($template) {
            return WhatsAppMessage::create()->template($template, [
                $nome,
                $ag->data_hora_inicio->format('d/m/Y H:i'),
                $ag->manicure->nome,
            ]);
        }

        return WhatsAppMessage::create(
            "Olá, {$nome}! Seu agendamento na {$this->brandName()} foi confirmado para "
            .$ag->data_hora_inicio->format('d/m/Y \à\s H:i')." com {$ag->manicure->nome}. Até breve!",
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ag = $this->agendamento;
        $valorLiquido = number_format($ag->valor_total - $ag->valor_desconto, 2, ',', '.');
        $guest = ! $ag->user_id;

        $mail = $this->baseMail('Agendamento confirmado', $notifiable)
            ->line('Seu horário na **'.$this->brandName().'** foi confirmado com sucesso.');

        $this->appendAgendamentoLines($mail);

        return $mail
            ->line('**Valor total:** R$ '.$valorLiquido)
            ->action($guest ? 'Confirmar presença' : 'Ver meu agendamento', $this->urlVerAgendamento())
            ->line('Aguardamos você!');
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload(
            'Agendamento Confirmado',
            'Seu agendamento para '.$this->agendamento->data_hora_inicio->format('d/m/Y H:i').' foi confirmado.',
        );
    }
}
