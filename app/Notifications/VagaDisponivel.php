<?php

namespace App\Notifications;

use App\Models\ListaEspera;
use App\Notifications\Concerns\EnviaPorWhatsApp;
use App\Notifications\Messages\WhatsAppMessage;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VagaDisponivel extends Notification implements ShouldQueue
{
    use Queueable, EnviaPorWhatsApp;

    public function __construct(public ListaEspera $entrada, public ?Carbon $dataVaga = null) {}

    public function via(object $notifiable): array
    {
        return $this->comWhatsApp(['mail', 'database']);
    }

    private function quando(): string
    {
        return $this->dataVaga ? ' para ' . $this->dataVaga->format('d/m/Y') : '';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $salao = $this->entrada->salao;

        return (new MailMessage)
            ->subject('Abriu uma vaga! — ' . $salao->nome)
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Abriu uma vaga em ' . $salao->nome . $this->quando() . '.')
            ->action('Agendar agora', route('public.agendar', $salao->slug))
            ->line('As vagas são por ordem de chegada — corra! 🌸');
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $salao = $this->entrada->salao;

        return WhatsAppMessage::create(
            "Olá, {$notifiable->name}! 🌸 Abriu uma vaga em {$salao->nome}{$this->quando()}. "
            . 'Agende: ' . route('public.agendar', $salao->slug)
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo'   => 'Vaga disponível',
            'mensagem' => 'Abriu uma vaga em ' . $this->entrada->salao->nome . $this->quando() . '. Agende agora!',
        ];
    }
}
