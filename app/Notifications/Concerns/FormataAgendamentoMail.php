<?php

namespace App\Notifications\Concerns;

use App\Models\Agendamento;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Helpers compartilhados pelas notifications de agendamento
 * (Confirmado, Cancelado, Lembrete) — evita duplicação no toMail().
 */
trait FormataAgendamentoMail
{
    protected function baseMail(string $subject, object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($subject . ' - ' . $this->agendamento->salao->nome)
            ->greeting('Olá, ' . $notifiable->name . '!');
    }

    protected function appendAgendamentoLines(MailMessage $mail, bool $incluirEndereco = false): MailMessage
    {
        $ag = $this->agendamento;

        $mail
            ->line('**Data:** ' . $ag->data_hora_inicio->format('d/m/Y'))
            ->line('**Horário:** ' . $ag->data_hora_inicio->format('H:i'))
            ->line('**Profissional:** ' . $ag->manicure->nome)
            ->line('**Serviços:** ' . $ag->servicos->pluck('nome')->implode(', '));

        if ($incluirEndereco) {
            $mail->line('**Endereço:** ' . $ag->salao->endereco_completo);
        }

        return $mail;
    }

    protected function payload(string $titulo, string $mensagem): array
    {
        return [
            'agendamento_id' => $this->agendamento->id,
            'titulo'         => $titulo,
            'mensagem'       => $mensagem,
        ];
    }
}
