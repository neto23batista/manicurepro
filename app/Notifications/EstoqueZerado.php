<?php

namespace App\Notifications;

use App\Models\Produto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EstoqueZerado extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Produto $produto) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $marca = (string) config('app.name', 'Fernanda Silva Nails');
        $nome = $this->produto->nome;

        return (new MailMessage)
            ->subject("Estoque zerado — {$nome}")
            ->greeting('Olá, '.$notifiable->name.'!')
            ->line("O produto **{$nome}** ficou com estoque zerado após uma venda.")
            ->action('Ver produtos', route('dono.produtos.index'))
            ->line('Reponha o estoque para continuar vendendo este item.')
            ->salutation("Atenciosamente,\n{$marca}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo'     => 'Estoque zerado',
            'mensagem'   => 'O produto "'.$this->produto->nome.'" ficou sem estoque após uma venda.',
            'produto_id' => $this->produto->id,
        ];
    }
}
