<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caixa;
use App\Models\Salao;
use App\Services\CaixaService;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaixaController extends Controller
{
    public function __construct(private CaixaService $caixaService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isDono() && ! $user->isSuperAdmin()) {
            return ApiError::make('Acesso negado.', 403, 'forbidden');
        }

        $this->authorize('viewAny', Caixa::class);

        $salao = $user->salao ?? Salao::principal();
        if ($salao === null) {
            return ApiError::make('Nenhum salão configurado.', 404, 'salao_nao_encontrado');
        }

        $aberto = $this->caixaService->aberto($salao->id);
        $historico = $this->caixaService->historico($salao->id, 15);

        return response()->json([
            'salao_id'  => $salao->id,
            'aberto'    => $aberto ? $this->serializarCaixa($aberto) : null,
            'historico' => $historico->map(fn (Caixa $c) => $this->serializarCaixa($c))->values(),
        ]);
    }

    public function show(Request $request, Caixa $caixa): JsonResponse
    {
        $this->authorize('view', $caixa);

        $user = $request->user();
        if (! $user->isDono() && ! $user->isSuperAdmin()) {
            return ApiError::make('Acesso negado.', 403, 'forbidden');
        }

        $caixa->load('movimentacoes');

        return response()->json([
            'caixa' => array_merge($this->serializarCaixa($caixa), [
                'movimentacoes' => $caixa->movimentacoes->map(fn ($m) => [
                    'id'        => $m->id,
                    'tipo'      => $m->tipo,
                    'valor'     => (float) $m->valor,
                    'descricao' => $m->descricao,
                    'criado_em' => $m->created_at?->toIso8601String(),
                ])->values(),
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarCaixa(Caixa $caixa): array
    {
        return [
            'id'                    => $caixa->id,
            'salao_id'              => $caixa->salao_id,
            'aberto'                => $caixa->estaAberto(),
            'aberto_em'             => $caixa->aberto_em->toIso8601String(),
            'fechado_em'            => $caixa->fechado_em?->toIso8601String(),
            'saldo_inicial'         => (float) ($caixa->saldo_inicial ?? 0),
            'saldo_final_informado' => $caixa->saldo_final_informado !== null
                ? (float) $caixa->saldo_final_informado
                : null,
            'saldo_calculado' => (float) $this->caixaService->saldoCalculado($caixa),
            'diferenca'       => $caixa->diferenca !== null ? (float) $caixa->diferenca : null,
            'observacao'      => $caixa->observacao,
        ];
    }
}
