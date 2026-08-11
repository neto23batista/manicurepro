<?php

namespace App\Services;

use App\Enums\NotaFiscalStatus;
use App\Models\Agendamento;
use App\Models\Comanda;
use App\Models\NotaFiscal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * STUB fiscal / NF-e — NÃO emite na SEFAZ.
 *
 * Cria apenas rascunhos locais com payload marcado como stub.
 * Não há comunicação com autorizador, certificado digital ou webservice.
 */
class NotaFiscalService
{
    /**
     * Gera um rascunho local a partir de agendamento e/ou comanda.
     * Nunca autoriza nem transmite para a SEFAZ.
     */
    public function emitRascunho(int $salaoId, ?Agendamento $agendamento = null, ?Comanda $comanda = null): NotaFiscal
    {
        if ($agendamento === null && $comanda === null) {
            throw new \InvalidArgumentException('Informe agendamento ou comanda para o rascunho fiscal.');
        }

        if ($agendamento !== null && (int) $agendamento->salao_id !== $salaoId) {
            throw new \InvalidArgumentException('Agendamento não pertence ao salão.');
        }

        if ($comanda !== null && (int) $comanda->salao_id !== $salaoId) {
            throw new \InvalidArgumentException('Comanda não pertence ao salão.');
        }

        if ($agendamento === null && $comanda->agendamento_id) {
            $agendamento = $comanda->agendamento;
        }

        if ($comanda === null) {
            $comanda = $agendamento->comanda;
        }

        $total = $comanda
            ? (float) $comanda->total
            : (float) $agendamento->valor_total - (float) $agendamento->valor_desconto;

        return NotaFiscal::create([
            'salao_id'       => $salaoId,
            'agendamento_id' => $agendamento?->id,
            'comanda_id'     => $comanda?->id,
            'status'         => NotaFiscalStatus::Rascunho,
            'numero'         => null,
            'chave'          => null,
            'payload'        => [
                'stub'    => true,
                'sefaz'   => false,
                'aviso'   => 'Rascunho local — NÃO emitir SEFAZ. Módulo stub sem integração fiscal.',
                'total'   => round($total, 2),
                'cliente' => ($agendamento !== null ? $agendamento->nome_cliente_exibido : null)
                    ?? ($comanda !== null && $comanda->cliente !== null ? $comanda->cliente->nome : null),
                'criado_em' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Lista notas fiscais (stubs) do salão, mais recentes primeiro.
     *
     * @return LengthAwarePaginator<int, NotaFiscal>|Collection<int, NotaFiscal>
     */
    public function list(int $salaoId, int $perPage = 20): LengthAwarePaginator|Collection
    {
        $query = NotaFiscal::where('salao_id', $salaoId)
            ->with(['agendamento', 'comanda'])
            ->latest();

        return $perPage > 0 ? $query->paginate($perPage) : $query->get();
    }
}
