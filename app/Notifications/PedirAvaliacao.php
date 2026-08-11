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

class PedirAvaliacao extends Notification implements ShouldQueue
{
    use EnviaPorWhatsApp, FormataAgendamentoMail, Queueable;

    public function __construct(public Agendamento $agendamento) {}

    public function via(object $notifiable): array
    {
        $canais = ['mail'];

        if (! $notifiable instanceof AnonymousNotifiable) {
            $canais[] = 'database';
        }

        return $this->comWhatsApp($canais);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $nome = $this->nomeNotifiable($notifiable);
        $marca = $this->brandName();

        return WhatsAppMessage::create(
            "Oi, {$nome}! Obrigada pela visita na {$marca}. "
            .'Pode avaliar o atendimento? Sua opinião nos ajuda muito!',
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->baseMail('Como foi o atendimento?', $notifiable)
            ->line('Seu atendimento na **'.$this->brandName().'** foi concluído. Queremos saber o que achou!');

        $this->appendAgendamentoLines($mail);

        return $mail
            ->action('Avaliar atendimento', $this->urlAvaliacao())
            ->line('Leva menos de um minuto. Obrigada!');
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload(
            'Avalie seu atendimento',
            'Conte como foi o atendimento de '.$this->agendamento->data_hora_inicio->format('d/m/Y').'.',
        );
    }

    private function urlAvaliacao(): string
    {
        if ($this->agendamento->user_id) {
            return route('cliente.agendamentos.show', $this->agendamento);
        }

        return $this->urlAgendarSalao();
    }
}
