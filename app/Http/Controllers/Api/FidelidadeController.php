<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FidelidadeService;
use App\Support\ApiError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FidelidadeController extends Controller
{
    public function __construct(private FidelidadeService $fidelidade) {}

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $cliente = $user->cliente;

        if (! $cliente) {
            return ApiError::make(
                'Cadastro de cliente não encontrado.',
                404,
                'cliente_nao_encontrado',
            );
        }

        $cliente->loadMissing('salao.configuracao');

        $pontosPorBloco = $this->fidelidade->pontosPorBloco($cliente);
        $valorPorBloco = $this->fidelidade->valorPorBloco($cliente);
        $pontos = (int) ($cliente->pontos_fidelidade ?? 0);
        $podeResgatar = $this->fidelidade->podeResgatar($cliente);
        $blocosDisponiveis = $pontosPorBloco > 0 ? intdiv($pontos, $pontosPorBloco) : 0;

        $historico = $cliente->pontosFidelidade()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($p) => [
                'id'        => $p->id,
                'pontos'    => (int) $p->pontos,
                'tipo'      => $p->tipo,
                'descricao' => $p->descricao,
                'criado_em' => $p->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'fidelidade' => [
                'pontos'              => $pontos,
                'pontos_por_bloco'    => $pontosPorBloco,
                'valor_por_bloco'     => $valorPorBloco,
                'pode_resgatar'       => $podeResgatar,
                'blocos_disponiveis'  => $blocosDisponiveis,
                'pontos_para_proximo' => $podeResgatar
                    ? 0
                    : max(0, $pontosPorBloco - $pontos),
                'ativo'     => (bool) ($cliente->salao?->configuracao->fidelidade_ativo ?? false),
                'historico' => $historico,
            ],
        ]);
    }
}
