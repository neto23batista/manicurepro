<?php

namespace App\Services;

use App\Models\EstoqueMovimentacao;
use App\Models\Produto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Movimentação de estoque: registra o histórico e mantém `produtos.estoque_atual`
 * consistente, de forma atômica.
 *
 * Tipos:
 *  - entrada / devolucao: soma a quantidade ao estoque
 *  - saida / perda / consumo_interno: subtrai do estoque
 *  - ajuste: define o estoque para o valor informado (inventário)
 */
class EstoqueService
{
    public const TIPOS_ENTRADA = ['entrada', 'devolucao'];

    public const TIPOS_SAIDA = ['saida', 'perda', 'consumo_interno'];

    public const TIPOS_MOTIVO_OBRIGATORIO = ['perda', 'consumo_interno', 'devolucao'];

    public const TIPOS = [
        'entrada', 'saida', 'ajuste', 'perda', 'consumo_interno', 'devolucao',
    ];

    public function movimentar(
        Produto $produto,
        string $tipo,
        float $quantidade,
        ?int $userId = null,
        ?string $motivo = null,
        ?float $precoUnitario = null,
        ?string $referencia = null
    ): EstoqueMovimentacao {
        if (! in_array($tipo, self::TIPOS, true)) {
            throw new InvalidArgumentException("Tipo de movimentação inválido: {$tipo}");
        }

        if (in_array($tipo, self::TIPOS_MOTIVO_OBRIGATORIO, true) && blank($motivo)) {
            throw new InvalidArgumentException("Motivo é obrigatório para o tipo \"{$tipo}\".");
        }

        return DB::transaction(function () use ($produto, $tipo, $quantidade, $userId, $motivo, $precoUnitario, $referencia) {
            $produto = Produto::query()->lockForUpdate()->findOrFail($produto->id);
            $atual = round((float) $produto->estoque_atual, 3);
            $quantidade = round((float) $quantidade, 3);

            if (in_array($tipo, self::TIPOS_SAIDA, true) && $quantidade > $atual) {
                throw ValidationException::withMessages([
                    'quantidade' => 'Estoque insuficiente. Disponível: '.rtrim(rtrim(number_format($atual, 3, '.', ''), '0'), '.').'.',
                ]);
            }

            $novo = match (true) {
                in_array($tipo, self::TIPOS_ENTRADA, true) => $atual + $quantidade,
                in_array($tipo, self::TIPOS_SAIDA, true)   => $atual - $quantidade,
                $tipo === 'ajuste'                         => $quantidade,
                default                                    => $atual,
            };

            if ($tipo === 'ajuste' && $novo < 0) {
                throw ValidationException::withMessages([
                    'quantidade' => 'O estoque ajustado não pode ser negativo.',
                ]);
            }

            $produto->update(['estoque_atual' => $novo]);

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

    /**
     * Aplica contagem de inventário: gera ajustes só onde a contagem difere do estoque.
     *
     * @param  array<int, float|int|string>  $contagens  produto_id => quantidade contada
     * @return array{ajustes: int, movimentacoes: Collection<int, EstoqueMovimentacao>}
     */
    public function aplicarInventario(int $salaoId, array $contagens, ?int $userId = null): array
    {
        return DB::transaction(function () use ($salaoId, $contagens, $userId) {
            $movimentacoes = collect();
            $referencia = 'inventario:'.now()->format('YmdHis');

            $produtos = Produto::query()
                ->where('salao_id', $salaoId)
                ->whereIn('id', array_keys($contagens))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($contagens as $produtoId => $contado) {
                $produto = $produtos->get((int) $produtoId);
                if (! $produto) {
                    continue;
                }

                $contado = round((float) $contado, 3);
                $atual = round((float) $produto->estoque_atual, 3);

                if (abs($contado - $atual) < 0.0005) {
                    continue;
                }

                $mov = $this->movimentar(
                    $produto,
                    'ajuste',
                    $contado,
                    $userId,
                    'Inventário — contagem física',
                    null,
                    $referencia,
                );
                $movimentacoes->push($mov);
            }

            AuditLogger::log('estoque.inventario', null, [
                'salao_id'        => $salaoId,
                'ajustes'         => $movimentacoes->count(),
                'referencia'      => $referencia,
                'produto_ids'     => $movimentacoes->pluck('produto_id')->values()->all(),
            ]);

            return [
                'ajustes'        => $movimentacoes->count(),
                'movimentacoes'  => $movimentacoes,
                'referencia'     => $referencia,
            ];
        });
    }

    /**
     * Relatório simples: margem, giro no período e produtos parados.
     *
     * @return array{
     *   periodo_dias: int,
     *   dias_parado: int,
     *   itens: Collection<int, object>
     * }
     */
    public function relatorio(int $salaoId, int $periodoDias = 30, ?int $diasParado = null): array
    {
        $diasParado ??= (int) config('manicure.estoque.dias_parado', 60);
        $inicio = now()->subDays($periodoDias)->startOfDay();
        $limiteParado = now()->subDays($diasParado);

        $produtos = Produto::query()
            ->where('salao_id', $salaoId)
            ->where('ativo', true)
            ->with('fornecedor:id,nome')
            ->orderBy('nome')
            ->get();

        $saidas = EstoqueMovimentacao::query()
            ->selectRaw('produto_id, SUM(quantidade) as total')
            ->where('salao_id', $salaoId)
            ->whereIn('tipo', self::TIPOS_SAIDA)
            ->where('created_at', '>=', $inicio)
            ->groupBy('produto_id')
            ->pluck('total', 'produto_id');

        $ultimaMov = EstoqueMovimentacao::query()
            ->selectRaw('produto_id, MAX(created_at) as ultima')
            ->where('salao_id', $salaoId)
            ->groupBy('produto_id')
            ->pluck('ultima', 'produto_id');

        $itens = $produtos->map(function (Produto $p) use ($saidas, $ultimaMov, $limiteParado) {
            $custo = (float) $p->preco_custo;
            $venda = (float) $p->preco_venda;
            $estoque = (float) $p->estoque_atual;
            $qtdSaida = (float) ($saidas[$p->id] ?? 0);
            $ultima = $ultimaMov[$p->id] ?? null;

            $margemPct = $venda > 0
                ? round((($venda - $custo) / $venda) * 100, 1)
                : null;

            $giro = $estoque > 0
                ? round($qtdSaida / $estoque, 2)
                : ($qtdSaida > 0 ? round($qtdSaida, 2) : 0.0);

            $parado = $ultima === null
                || \Carbon\Carbon::parse($ultima)->lt($limiteParado);

            return (object) [
                'produto'        => $p,
                'preco_custo'    => $custo,
                'preco_venda'    => $venda,
                'estoque_atual'  => $estoque,
                'margem_pct'     => $margemPct,
                'margem_valor'   => round($venda - $custo, 2),
                'saidas_periodo' => $qtdSaida,
                'giro'           => $giro,
                'parado'         => $parado,
                'ultima_mov'     => $ultima,
            ];
        });

        return [
            'periodo_dias' => $periodoDias,
            'dias_parado'  => $diasParado,
            'itens'        => $itens,
            'parados'      => $itens->where('parado', true)->values(),
            'resumo'       => [
                'produtos'       => $itens->count(),
                'parados'        => $itens->where('parado', true)->count(),
                'baixo_estoque'  => $itens->filter(fn ($i) => $i->produto->estoque_baixo)->count(),
                'margem_media'   => $itens->whereNotNull('margem_pct')->avg('margem_pct'),
            ],
        ];
    }
}
