<?php

namespace App\Notifications;

use App\Models\Agendamento;
use App\Notifications\Concerns\EnviaPorWhatsApp;
use App\Notifications\Concerns\FormataAgendamentoMail;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgendamentoConfirmado extends Notification implements ShouldQueue
{
    use Queueable, FormataAgendamentoMail, EnviaPorWhatsApp;

    public function __construct(public Agendamento $agendamento) {}

    public function via(object $notifiable): array
    {
        return $this->comWhatsApp(['mail', 'database']);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $ag = $this->agendamento;
        $template = config('manicure.whatsapp.templates.confirmado');

        if ($template) {
            return WhatsAppMessage::create()->template($template, [
                $notifiable->name,
                $ag->data_hora_inicio->format('d/m/Y H:i'),
                $ag->manicure->nome,
            ]);
        }

        return WhatsAppMessage::create(
            "Olá, {$notifiable->name}! 💅 Seu agendamento foi confirmado para "
            . $ag->data_hora_inicio->format('d/m/Y \à\s H:i') . " com {$ag->manicure->nome}. Até breve!"
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ag = $this->agendamento;
        $valorLiquido = number_format($ag->valor_total - $ag->valor_desconto, 2, ',', '.');

        $mail = $this->baseMail('Agendamento Confirmado', $notifiable)
            ->line('Seu agendamento foi confirmado com sucesso.');

        $this->appendAgendamentoLines($mail);

        return $mail
            ->line('**Valor Total:** R$ ' . $valorLiquido)
            ->action('Ver Agendamento', route('cliente.agendamentos.show', $ag))
            ->line('Aguardamos você! 💅');
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload(
            'Agendamento Confirmado',
            'Seu agendamento para ' . $this->agendamento->data_hora_inicio->format('d/m/Y H:i') . ' foi confirmado.'
        );
    }
}
