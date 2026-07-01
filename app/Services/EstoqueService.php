<?php

namespace App\Services;

use App\Models\EstoqueMovimentacao;
use App\Models\Produto;
use Illuminate\Support\Facades\DB;

/**
 * Movimentação de estoque: registra o histórico e mantém `produtos.estoque_atual`
 * consistente, de forma atômica.
 *
 * Tipos:
 *  - entrada: soma a quantidade ao estoque (compra, reposição).
 *  - saida:   subtrai do estoque (venda, perda, uso interno).
 *  - ajuste:  define o estoque para o valor informado (correção de inventário).
 */
class EstoqueService
{
    public function movimentar(
        Produto $produto,
        string $tipo,
        float $quantidade,
        ?int $userId = null,
        ?string $motivo = null,
        ?float $precoUnitario = null,
        ?string $referencia = null
    ): EstoqueMovimentacao {
        return DB::transaction(function () use ($produto, $tipo, $quantidade, $userId, $motivo, $precoUnitario, $referencia) {
            $atual = (float) $produto->estoque_atual;

            $novo = match ($tipo) {
                'entrada' => $atual + $quantidade,
                'saida'   => $atual - $quantidade,
                'ajuste'  => $quantidade,
                default   => $atual,
            };

            $produto->update(['estoque_atual' => max(0, $novo)]);

            return EstoqueMovimentacao::create([
                'produto_id'     => $produto->id,
                'salao_id'       => $produto->salao_id,
                'user_id'        => $userId,
                'tipo'           => $tipo,
                'quantidade'     => $quantidade,
                'preco_unitario' => $precoUnitario,
                'motivo'         => $motivo,
                'referencia'     => $referencia,
            ]);
        });
    }
}
