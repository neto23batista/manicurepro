<?php

namespace App\Services;

use App\Contracts\NotaFiscalProvider;
use App\Enums\NotaFiscalStatus;
use App\Models\Agendamento;
use App\Models\Comanda;
use App\Models\NotaFiscal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Orquestra rascunhos fiscais via NotaFiscalProvider.
 *
 * NÃO emite na SEFAZ. O provedor (stub ou HTTP) só produz rascunhos.
 */
class NotaFiscalService
{
    public function __construct(private NotaFiscalProvider $provider) {}

    /**
     * Gera um rascunho a partir de agendamento e/ou comanda.
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

        $cliente = ($agendamento !== null ? $agendamento->nome_cliente_exibido : null)
            ?? ($comanda !== null && $comanda->cliente !== null ? $comanda->cliente->nome : null);

        $resultado = $this->provider->criarRascunho([
            'salao_id'       => $salaoId,
            'agendamento_id' => $agendamento?->id,
            'comanda_id'     => $comanda?->id,
            'total'          => round($total, 2),
            'cliente'        => $cliente,
        ]);

        $payload = $resultado['payload'];
        if (! empty($resultado['provider_id'])) {
            $payload['provider_id'] = $resultado['provider_id'];
        }
        // Garantia: nunca persistir sefaz=true a partir deste serviço.
        $payload['sefaz'] = false;

        return NotaFiscal::create([
            'salao_id'       => $salaoId,
            'agendamento_id' => $agendamento?->id,
            'comanda_id'     => $comanda?->id,
            'status'         => NotaFiscalStatus::tryFrom((string) $resultado['status']) ?? NotaFiscalStatus::Rascunho,
            'numero'         => $resultado['numero'],
            'chave'          => $resultado['chave'],
            'payload'        => $payload,
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
