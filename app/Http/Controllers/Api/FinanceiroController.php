<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Salao;
use App\Services\FinanceiroService;
use App\Support\ApiError;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceiroController extends Controller
{
    public function __construct(private FinanceiroService $financeiro) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isDono() && ! $user->isSuperAdmin()) {
            return ApiError::make('Acesso negado.', 403, 'forbidden');
        }

        $salao = $user->salao ?? Salao::principal();
        if ($salao === null) {
            return ApiError::make('Nenhum salão configurado.', 404, 'salao_nao_encontrado');
        }

        $inicio = Carbon::parse($request->input('de', now()->startOfMonth()->toDateString()))->startOfDay();
        $fim = Carbon::parse($request->input('ate', now()->toDateString()))->endOfDay();

        return response()->json([
            'salao_id' => $salao->id,
            'periodo'  => [
                'de'  => $inicio->toDateString(),
                'ate' => $fim->toDateString(),
            ],
            'caixa'     => $this->financeiro->caixa($salao->id, $inicio, $fim),
            'comissoes' => $this->financeiro->comissoes($salao->id, $inicio, $fim)->values(),
            'fluxo'     => $this->financeiro->fluxoCaixa($salao->id, $inicio, $fim),
        ]);
    }
}
