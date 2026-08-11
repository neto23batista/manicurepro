<?php

namespace App\Services;

use App\Enums\FormaPagamento;
use App\Enums\PagamentoStatus;
use App\Models\Pagamento;
use App\Models\ValePresente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Emissão e resgate de vales-presente (gift cards).
 *
 * Resgates são atômicos (lock pessimista) e suportam uso parcial do saldo.
 */
class ValePresenteService
{
    public function gerarCodigo(): string
    {
        do {
            $codigo = 'VP-'.strtoupper(Str::random(8));
        } while (ValePresente::where('codigo', $codigo)->exists());

        return $codigo;
    }

    public function criar(int $salaoId, array $dados): ValePresente
    {
        $valor = round((float) $dados['valor'], 2);

        if ($valor < 1) {
            throw new \InvalidArgumentException('Valor do vale deve ser pelo menos R$ 1,00.');
        }

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
                'referencia'  => 'vale:'.$vale->codigo,
                'observacoes' => 'Venda de vale-presente '.$vale->codigo,
            ]);

            return $vale;
        });
    }

    /**
     * Debita até $valor do saldo do vale (resgate parcial permitido).
     * Retorna o valor efetivamente debitado.
     *
     * @throws ValidationException vale cancelado, expirado, usado ou sem saldo
     */
    public function debitar(ValePresente $vale, float $valor): float
    {
        $valor = round($valor, 2);

        if ($valor <= 0) {
            throw ValidationException::withMessages([
                'error' => 'Valor de débito do vale deve ser positivo.',
            ]);
        }

        return DB::transaction(function () use ($vale, $valor) {
            // Lock pessimista: serializa resgates concorrentes do mesmo vale
            // (sem isso, dois caixas poderiam debitar o mesmo saldo em paralelo).
            $travado = ValePresente::whereKey($vale->getKey())->lockForUpdate()->firstOrFail();

            $this->assertPodeDebitar($travado);

            // Uso parcial: nunca debita além do saldo restante.
            $debito = round(min((float) $travado->saldo, $valor), 2);

            if ($debito <= 0) {
                throw ValidationException::withMessages([
                    'error' => 'Vale-presente sem saldo.',
                ]);
            }

            $novoSaldo = round((float) $travado->saldo - $debito, 2);

            $travado->saldo = $novoSaldo;
            if ($novoSaldo <= 0) {
                $travado->saldo = 0;
                $travado->status = ValePresente::STATUS_USADO;
            }
            $travado->save();

            // Sincroniza a instância do chamador com o estado persistido.
            $vale->setRawAttributes($travado->getAttributes(), true);

            return $debito;
        });
    }

    /**
     * Regras de disponibilidade avaliadas sob lock (anti double-redeem).
     */
    private function assertPodeDebitar(ValePresente $vale): void
    {
        if ($vale->status === ValePresente::STATUS_CANCELADO) {
            throw ValidationException::withMessages([
                'error' => 'Vale-presente cancelado.',
            ]);
        }

        if ($vale->status === ValePresente::STATUS_USADO || (float) $vale->saldo <= 0) {
            throw ValidationException::withMessages([
                'error' => 'Vale-presente já utilizado ou sem saldo.',
            ]);
        }

        if ($vale->expirado) {
            throw ValidationException::withMessages([
                'error' => 'Vale-presente expirado.',
            ]);
        }

        if ($vale->status !== ValePresente::STATUS_ATIVO) {
            throw ValidationException::withMessages([
                'error' => 'Vale-presente indisponível.',
            ]);
        }
    }
}
