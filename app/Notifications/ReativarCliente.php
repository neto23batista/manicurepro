<?php

namespace App\Notifications;

use App\Models\Cliente;
use App\Models\Cupom;
use App\Models\Salao;
use App\Notifications\Concerns\EnviaPorWhatsApp;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReativarCliente extends Notification implements ShouldQueue
{
    use EnviaPorWhatsApp, Queueable;

    public function __construct(
        public Cliente $cliente,
        public string $salaoNome,
        public ?Cupom $cupom = null,
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
        $msg = "Oi, {$this->cliente->nome}! Sentimos sua falta na {$marca}. Que tal agendar um horário?";

        if ($this->cupom) {
            $msg .= " Use o cupom *{$this->cupom->codigo}* e ganhe um desconto especial.";
        }

        return WhatsAppMessage::create($msg);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $marca = $this->brandName();

        $mail = (new MailMessage)
            ->subject("Sentimos sua falta — {$marca}")
            ->greeting("Oi, {$this->cliente->nome}!")
            ->line("Faz um tempo que você não aparece na **{$marca}**. Queremos te ver de novo!");

        if ($this->cupom) {
            $desconto = $this->cupom->tipo === 'percentual'
                ? rtrim(rtrim(number_format((float) $this->cupom->valor, 2, ',', '.'), '0'), ',').'%'
                : 'R$ '.number_format((float) $this->cupom->valor, 2, ',', '.');

            $mail->line("**Cupom de reativação:** **{$this->cupom->codigo}** ({$desconto} de desconto).");

            if ($this->cupom->validade) {
                $mail->line('Válido até '.$this->cupom->validade->format('d/m/Y').'.');
            }
        }

        $slug = Salao::principal()?->slug;
        if ($slug) {
            $mail->action('Agendar horário', route('public.agendar', $slug));
        }

        return $mail
            ->line('Estamos com horário esperando por você.')
            ->salutation("Com carinho,\n{$marca}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo'   => 'Sentimos sua falta',
            'mensagem' => 'Que tal agendar um horário? Temos um carinho especial esperando por você.',
        ];
    }
}
