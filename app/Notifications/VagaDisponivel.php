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
    use EnviaPorWhatsApp, Queueable;

    public function __construct(public ListaEspera $entrada, public ?Carbon $dataVaga = null) {}

    public function via(object $notifiable): array
    {
        return $this->comWhatsApp(['mail', 'database']);
    }

    private function brandName(): string
    {
        return (string) config('app.name', 'Fernanda Silva Nails');
    }

    private function quando(): string
    {
        return $this->dataVaga ? ' para '.$this->dataVaga->format('d/m/Y') : '';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $salao = $this->entrada->salao;
        $marca = $this->brandName();

        return (new MailMessage)
            ->subject('Vaga disponível — '.$marca)
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line("Abriu uma vaga na **{$marca}**{$this->quando()}.")
            ->action('Agendar agora', route('public.agendar', $salao->slug))
            ->line('As vagas são por ordem de chegada — reserve o quanto antes.')
            ->salutation("Com carinho,\n{$marca}");
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $salao = $this->entrada->salao;

        return WhatsAppMessage::create(
            "Olá, {$notifiable->name}! Abriu uma vaga na {$this->brandName()}{$this->quando()}. "
            .'Agende: '.route('public.agendar', $salao->slug),
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo'   => 'Vaga disponível',
            'mensagem' => 'Abriu uma vaga em '.$this->entrada->salao->nome.$this->quando().'. Agende agora!',
        ];
    }
}
