<?php

namespace App\Services;

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ClientePacote;
use App\Models\Pacote;
use Illuminate\Support\Facades\DB;

/**
 * Pacotes/combos vendáveis: atribuição ao cliente e consumo de sessão
 * ao finalizar atendimento.
 */
class PacoteService
{
    public function atribuir(Pacote $pacote, Cliente $cliente): ClientePacote
    {
        if ((int) $pacote->salao_id !== (int) $cliente->salao_id) {
            throw new \InvalidArgumentException('Pacote e cliente devem pertencer ao mesmo salão.');
        }

        if (! $pacote->ativo) {
            throw new \InvalidArgumentException('Pacote inativo.');
        }

        $expiresAt = $pacote->validade_dias
            ? now()->addDays($pacote->validade_dias)
            : null;

        return ClientePacote::create([
            'cliente_id'        => $cliente->id,
            'pacote_id'         => $pacote->id,
            'sessoes_restantes' => $pacote->sessoes,
            'expires_at'        => $expiresAt,
        ]);
    }

    /**
     * Consome 1 sessão do pacote ativo mais antigo do cliente (FIFO).
     * Retorna o ClientePacote debitado ou null se não houver pacote disponível.
     */
    public function consumirSessao(Agendamento $agendamento): ?ClientePacote
    {
        $clienteId = $agendamento->cliente_id;
        if (! $clienteId) {
            return null;
        }

        return DB::transaction(function () use ($clienteId) {
            /** @var ClientePacote|null $clientePacote */
            $clientePacote = ClientePacote::query()
                ->disponiveis()
                ->where('cliente_id', $clienteId)
                ->orderBy('expires_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $clientePacote) {
                return null;
            }

            $clientePacote->decrement('sessoes_restantes');
            $clientePacote->refresh();

            return $clientePacote;
        });
    }
}
