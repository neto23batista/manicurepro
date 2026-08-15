<?php

namespace App\Http\Controllers;

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;

class ConfirmacaoController extends Controller
{
    /**
     * Confirma a presença do cliente a partir de um link assinado
     * enviado no lembrete (e-mail/WhatsApp). Não exige login.
     */
    public function confirmar(Agendamento $agendamento)
    {
        $statusEnum = $agendamento->statusEnum();
        $jaConfirmado = $agendamento->confirmado_em !== null;

        $podeConfirmar = $statusEnum && in_array(
            $statusEnum,
            [AgendamentoStatus::Aguardando, AgendamentoStatus::Confirmado],
            true,
        );

        if ($podeConfirmar && ! $jaConfirmado) {
            $agendamento->update([
                'confirmado_em' => now(),
                'status'        => AgendamentoStatus::Confirmado->value,
            ]);
        }

        $agendamento->load(['salao', 'manicure', 'servicos']);

        return view('public.confirmacao', [
            'agendamento'  => $agendamento,
            'ok'           => $podeConfirmar,
            'jaConfirmado' => $jaConfirmado,
        ]);
    }
}
