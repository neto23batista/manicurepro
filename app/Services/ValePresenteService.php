<?php

namespace App\Services;

use App\Enums\FormaPagamento;
use App\Enums\PagamentoStatus;
use App\Models\Pagamento;
use App\Models\ValePresente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Emissão e resgate de vales-presente (gift cards).
 */
class ValePresenteService
{
    public function gerarCodigo(): string
    {
        do {
            $codigo = 'VP-' . strtoupper(Str::random(8));
        } while (ValePresente::where('codigo', $codigo)->exists());

        return $codigo;
    }

    public function criar(int $salaoId, array $dados): ValePresente
    {
        $valor = (float) $dados['valor'];

        return DB::transaction(function () use ($salaoId, $dados, $valor) {
            $vale = ValePresente::create([
                'salao_id'          => $salaoId,
                'codigo'            => $this->gerarCodigo(),
                'valor'             => $valor,
                'saldo'             => $valor,
                'comprador_nome'    => $dados['comprador_nome'] ?? null,
                'comprador_contato' => $dados['comprador_contato'] ?? null,
                'beneficiario_nome' => $dados['beneficiario_nome'] ?? null,
                'mensagem'          => $dados['mensagem'] ?? null,
                'validade'          => $dados['validade'] ?? null,
                'status'            => ValePresente::STATUS_ATIVO,
            ]);

            // A VENDA do vale é a entrada de caixa (na forma escolhida).
            // O resgate futuro (forma=voucher) não conta como dinheiro novo.
            Pagamento::create([
                'salao_id'    => $salaoId,
                'forma'       => $dados['forma'] ?? FormaPagamento::Dinheiro->value,
                'valor'       => $valor,
                'status'      => PagamentoStatus::Confirmado->value,
                'referencia'  => 'vale:' . $vale->codigo,
                'observacoes' => 'Venda de vale-presente ' . $vale->codigo,
            ]);

            return $vale;
        });
    }

    /**
     * Debita até $valor do saldo do vale. Retorna o valor efetivamente debitado.
     */
    public function debitar(ValePresente $vale, float $valor): float
    {
        return DB::transaction(function () use ($vale, $valor) {
            // Lock pessimista: serializa resgates concorrentes do mesmo vale
            // (sem isso, dois caixas poderiam debitar o mesmo saldo em paralelo).
            $travado = ValePresente::whereKey($vale->getKey())->lockForUpdate()->firstOrFail();

            if (!$travado->estaDisponivel()) {
                throw new \RuntimeException('Vale-presente indisponível (cancelado, expirado ou sem saldo).');
            }

            $debito = min((float) $travado->saldo, $valor);
            $novoSaldo = round((float) $travado->saldo - $debito, 2);

            $travado->saldo = $novoSaldo;
            if ($novoSaldo <= 0) {
                $travado->status = ValePresente::STATUS_USADO;
            }
            $travado->save();

            // Sincroniza a instância do chamador com o estado persistido.
            $vale->setRawAttributes($travado->getAttributes(), true);

            return $debito;
        });
    }
}
