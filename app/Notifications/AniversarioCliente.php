<?php

namespace App\Notifications;

use App\Models\Cliente;
use App\Models\Cupom;
use App\Notifications\Concerns\EnviaPorWhatsApp;
use App\Notifications\Messages\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AniversarioCliente extends Notification implements ShouldQueue
{
    use Queueable, EnviaPorWhatsApp;

    public function __construct(
        public Cliente $cliente,
        public string $salaoNome,
        public ?Cupom $cupom = null,
    ) {}

    public function via(object $notifiable): array
    {
        // Só inclui o canal de e-mail quando há destinatário roteado —
        // protege contra notifiables sem e-mail (ex.: cliente só com telefone).
        $canais = $notifiable->routeNotificationFor('mail', $this) ? ['mail'] : [];

        return $this->comWhatsApp($canais);
    }

    public function toWhatsApp(object $notifiable): WhatsAppMessage
    {
        $msg = "🎉 Feliz aniversário, {$this->cliente->nome}! Todo o time do {$this->salaoNome} deseja um dia maravilhoso. 💅";

        if ($this->cupom) {
            $msg .= " Como presente, use o cupom *{$this->cupom->codigo}* e ganhe um desconto especial no seu próximo agendamento!";
        }

        return WhatsAppMessage::create($msg);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("🎉 Feliz aniversário! — {$this->salaoNome}")
            ->greeting("Feliz aniversário, {$this->cliente->nome}!")
            ->line("Todo o time do {$this->salaoNome} deseja a você um dia incrível e cheio de brilho. 💅");

        if ($this->cupom) {
            $desconto = $this->cupom->tipo === 'percentual'
                ? rtrim(rtrim(number_format($this->cupom->valor, 2, ',', '.'), '0'), ',') . '%'
                : 'R$ ' . number_format($this->cupom->valor, 2, ',', '.');

            $mail->line("**Nosso presente para você:** cupom **{$this->cupom->codigo}** com {$desconto} de desconto.");

            if ($this->cupom->validade) {
                $mail->line('Válido até ' . $this->cupom->validade->format('d/m/Y') . '.');
            }
        }

        return $mail->line('Esperamos você para comemorar com unhas perfeitas! ✨');
    }
}
