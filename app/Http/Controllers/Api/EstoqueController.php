<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\Salao;
use App\Services\EstoqueService;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstoqueController extends Controller
{
    public function __construct(private EstoqueService $estoque) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isDono() && ! $user->isSuperAdmin()) {
            return ApiError::make('Acesso negado.', 403, 'forbidden');
        }

        $this->authorize('viewAny', Produto::class);

        $salao = $user->salao ?? Salao::principal();
        if ($salao === null) {
            return ApiError::make('Nenhum salão configurado.', 404, 'salao_nao_encontrado');
        }

        $periodo = max(1, min(365, (int) $request->input('periodo_dias', 30)));
        $relatorio = $this->estoque->relatorio($salao->id, $periodo);

        $mapItem = function (array $item): array {
            /** @var Produto $produto */
            $produto = $item['produto'];

            return [
                'produto_id'     => $produto->id,
                'nome'           => $produto->nome,
                'estoque_atual'  => $item['estoque_atual'],
                'preco_custo'    => $item['preco_custo'],
                'preco_venda'    => $item['preco_venda'],
                'margem_pct'     => $item['margem_pct'],
                'saidas_periodo' => $item['saidas_periodo'],
                'giro'           => $item['giro'],
                'parado'         => $item['parado'],
            ];
        };

        return response()->json([
            'salao_id'  => $salao->id,
            'relatorio' => [
                'periodo_dias' => $relatorio['periodo_dias'],
                'dias_parado'  => $relatorio['dias_parado'],
                'resumo'       => $relatorio['resumo'],
                'itens'        => array_map($mapItem, $relatorio['itens']),
                'parados'      => array_map(
                    fn (array $item) => [
                        'produto_id'    => $item['produto']->id,
                        'nome'          => $item['produto']->nome,
                        'estoque_atual' => $item['estoque_atual'],
                    ],
                    $relatorio['parados'],
                ),
            ],
        ]);
    }
}
