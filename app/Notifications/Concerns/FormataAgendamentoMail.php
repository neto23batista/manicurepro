<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

/**
 * Helpers compartilhados pelas notifications de agendamento
 * (Confirmado, Cancelado, Lembrete, Reagendado) — evita duplicação no toMail().
 */
trait FormataAgendamentoMail
{
    protected function brandName(): string
    {
        return (string) config('app.name', 'Fernanda Silva Nails');
    }

    protected function nomeNotifiable(object $notifiable): string
    {
        $cliente = $this->agendamento->cliente;
        $name = data_get($notifiable, 'name');

        return $name
            ?: $this->agendamento->nome_cliente
            ?: ($cliente !== null ? $cliente->nome : null)
            ?: 'olá';
    }

    protected function baseMail(string $subject, object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($subject.' — '.$this->brandName())
            ->greeting('Olá, '.$this->nomeNotifiable($notifiable).'!')
            ->salutation("Com carinho,\n".$this->brandName());
    }

    protected function appendAgendamentoLines(MailMessage $mail, bool $incluirEndereco = false): MailMessage
    {
        $ag = $this->agendamento;

        $mail
            ->line('**Data:** '.$ag->data_hora_inicio->format('d/m/Y'))
            ->line('**Horário:** '.$ag->data_hora_inicio->format('H:i'))
            ->line('**Profissional:** '.$ag->manicure->nome)
            ->line('**Serviços:** '.$ag->servicos->pluck('nome')->implode(', '));

        if ($incluirEndereco) {
            $mail->line('**Endereço:** '.$ag->salao->endereco_completo);
        }

        return $mail;
    }

    /**
     * Link assinado de confirmação de presença (válido até 1 dia após o horário).
     */
    public function linkConfirmacao(): string
    {
        return URL::temporarySignedRoute(
            'agendamento.confirmar',
            $this->agendamento->data_hora_inicio->copy()->addDay(),
            ['agendamento' => $this->agendamento->id],
        );
    }

    protected function urlAgendarSalao(): string
    {
        return route('public.agendar', $this->agendamento->salao->slug);
    }

    protected function urlVerAgendamento(): string
    {
        // Guest (sem user) recebe o link assinado de confirmação já existente
        if (! $this->agendamento->user_id) {
            return $this->linkConfirmacao();
        }

        return route('cliente.agendamentos.show', $this->agendamento);
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
