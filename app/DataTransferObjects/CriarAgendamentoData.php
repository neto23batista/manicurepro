<?php

namespace App\DataTransferObjects;

use App\Enums\AgendamentoStatus;

/**
 * DTO tipado para criação de agendamento — substitui o `array $dados` shapeless
 * que circulava entre controllers e AgendaService.
 */
final readonly class CriarAgendamentoData
{
    /**
     * @param  array<int>  $servicoIds
     * @param  array<int, int>  $servicoVariacoes  mapa servico_id => variacao_id
     */
    public function __construct(
        public int $salaoId,
        public int $manicureId,
        public array $servicoIds,
        public string $dataHoraInicio,
        public ?int $clienteId = null,
        public ?int $userId = null,
        public ?string $nomeCliente = null,
        public ?string $telefoneCliente = null,
        public ?string $observacoes = null,
        public string $origem = 'web',
        public AgendamentoStatus $status = AgendamentoStatus::Aguardando,
        public ?int $cupomId = null,
        public bool $encaixe = false,
        public array $servicoVariacoes = [],
    ) {}

    public static function fromArray(array $d): self
    {
        $status = $d['status'] ?? null;
        $variacoes = [];
        if (! empty($d['servico_variacoes']) && is_array($d['servico_variacoes'])) {
            foreach ($d['servico_variacoes'] as $servicoId => $variacaoId) {
                if ($variacaoId === null || $variacaoId === '' || $variacaoId === 0 || $variacaoId === '0') {
                    continue;
                }
                $variacoes[(int) $servicoId] = (int) $variacaoId;
            }
        }

        return new self(
            salaoId:         (int) $d['salao_id'],
            manicureId:      (int) $d['manicure_id'],
            servicoIds:      array_map('intval', $d['servico_ids']),
            dataHoraInicio:  (string) $d['data_hora_inicio'],
            clienteId:       isset($d['cliente_id']) ? (int) $d['cliente_id'] : null,
            userId:          isset($d['user_id']) ? (int) $d['user_id'] : null,
            nomeCliente:     $d['nome_cliente'] ?? null,
            telefoneCliente: $d['telefone_cliente'] ?? null,
            observacoes:     $d['observacoes'] ?? null,
            origem:          $d['origem'] ?? 'web',
            status:          $status instanceof AgendamentoStatus
                                ? $status
                                : (AgendamentoStatus::tryFrom((string) ($status ?? '')) ?? AgendamentoStatus::Aguardando),
            cupomId:         isset($d['cupom_id']) ? (int) $d['cupom_id'] : null,
            encaixe:         (bool) ($d['encaixe'] ?? false),
            servicoVariacoes: $variacoes,
        );
    }

    public function toArray(): array
    {
        return [
            'salao_id'           => $this->salaoId,
            'manicure_id'        => $this->manicureId,
            'servico_ids'        => $this->servicoIds,
            'data_hora_inicio'   => $this->dataHoraInicio,
            'cliente_id'         => $this->clienteId,
            'user_id'            => $this->userId,
            'nome_cliente'       => $this->nomeCliente,
            'telefone_cliente'   => $this->telefoneCliente,
            'observacoes'        => $this->observacoes,
            'origem'             => $this->origem,
            'status'             => $this->status,
            'cupom_id'           => $this->cupomId,
            'encaixe'            => $this->encaixe,
            'servico_variacoes'  => $this->servicoVariacoes,
        ];
    }
}
