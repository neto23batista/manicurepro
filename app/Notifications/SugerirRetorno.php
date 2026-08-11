<?php

namespace App\Notifications;

use App\Models\Cliente;
use App\Models\Salao;
use App\Notifications\Concerns\EnviaPorWhatsApp;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SugerirRetorno extends Notification implements ShouldQueue
{
    use EnviaPorWhatsApp, Queueable;

    public function __construct(
        public Cliente $cliente,
        public string $salaoNome,
        public int $cadenciaDias = 28,
    ) {}

    public function via(object $notifiable): array
    {
        $canais = $notifiable->routeNotificationFor('mail', $this)
            || (! $notifiable instanceof AnonymousNotifiable && filled(data_get($notifiable, 'email')))
            ? ['mail']
            : [];

        if (! $notifiable instanceof AnonymousNotifiable) {
            $canais[] = 'database';
        }

        return $this->comWhatsApp($canais);
    }

    private function brandName(): string
    {
        return (string) ($this->salaoNome ?: config('app.name', 'Fernanda Silva Nails'));
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $marca = $this->brandName();

        return WhatsAppMessage::create(
            "Oi, {$this->cliente->nome}! Já faz cerca de {$this->cadenciaDias} dias da sua última visita na {$marca}. "
            .'Hora de renovar as unhas? Agende quando quiser!',
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $marca = $this->brandName();

        $mail = (new MailMessage)
            ->subject("Hora de renovar as unhas — {$marca}")
            ->greeting("Oi, {$this->cliente->nome}!")
            ->line("Já faz cerca de **{$this->cadenciaDias} dias** da sua última visita na **{$marca}**.")
            ->line('Que tal agendar um horário e manter o brilho em dia?');

        $slug = Salao::principal()?->slug;
        if ($slug) {
            $mail->action('Agendar horário', route('public.agendar', $slug));
        }

        return $mail->salutation("Com carinho,\n{$marca}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo'   => 'Hora de renovar as unhas',
            'mensagem' => "Já faz cerca de {$this->cadenciaDias} dias da sua última visita. Agende um horário!",
        ];
    }
}
