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
use Illuminate\Support\Facades\URL;

class AgendamentoLembrete extends Notification implements ShouldQueue
{
    use Queueable, FormataAgendamentoMail, EnviaPorWhatsApp;

    /** @param string $tipo '24h' ou '2h' */
    public function __construct(public Agendamento $agendamento, public string $tipo = '24h') {}

    public function via(object $notifiable): array
    {
        return $this->comWhatsApp(['mail', 'database']);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $ag = $this->agendamento;
        $template = config('manicure.whatsapp.templates.lembrete');

        if ($template) {
            return WhatsAppMessage::create()->template($template, [
                $notifiable->name,
                $ag->data_hora_inicio->format('d/m/Y H:i'),
                $ag->manicure->nome,
            ]);
        }

        $quando = $this->tipo === '2h' ? 'em cerca de 2 horas' : 'amanhã';

        return WhatsAppMessage::create(
            "Olá, {$notifiable->name}! 🌸 Lembrete do seu horário {$quando}: "
            . $ag->data_hora_inicio->format('d/m/Y \à\s H:i') . " com {$ag->manicure->nome}.\n"
            . "Confirme sua presença: " . $this->linkConfirmacao()
        );
    }

    public function linkConfirmacao(): string
    {
        return URL::temporarySignedRoute(
            'agendamento.confirmar',
            $this->agendamento->data_hora_inicio->copy()->addDay(),
            ['agendamento' => $this->agendamento->id]
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $quando = $this->tipo === '2h' ? 'em cerca de 2 horas' : 'amanhã';

        $mail = $this->baseMail('Lembrete de Agendamento', $notifiable)
            ->line("Lembramos que você tem um agendamento {$quando}!");

        $this->appendAgendamentoLines($mail, incluirEndereco: true);

        return $mail
            ->action('Confirmar presença', $this->linkConfirmacao())
            ->line('Se precisar remarcar, acesse seu painel.')
            ->line('Até breve! 🌸');
    }

    public function toArray(object $notifiable): array
    {
        $quando = $this->tipo === '2h' ? 'em ~2h' : 'amanhã';

        return $this->payload(
            'Lembrete de Agendamento',
            "Lembrete: você tem agendamento {$quando} às " . $this->agendamento->data_hora_inicio->format('H:i')
        );
    }
}
