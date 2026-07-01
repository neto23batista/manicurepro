<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Services\FinanceiroService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceiroController extends Controller
{
    public function __construct(private FinanceiroService $financeiro) {}

    public function index(Request $request)
    {
        // Admin não tem salão vinculado — cai no salão único (single-tenant).
        $salao = auth()->user()->salao ?? \App\Models\Salao::principal();
        abort_if($salao === null, 404, 'Nenhum salão configurado.');

        [$inicio, $fim, $periodo] = $this->resolverPeriodo($request);

        $caixa     = $this->financeiro->caixa($salao->id, $inicio, $fim);
        $comissoes = $this->financeiro->comissoes($salao->id, $inicio, $fim);

        $totalComissoes = (float) $comissoes->sum('comissao');
        $totalServicos  = (float) $comissoes->sum('base');

        return view('dono.financeiro.index', compact(
            'salao', 'inicio', 'fim', 'periodo',
            'caixa', 'comissoes', 'totalComissoes', 'totalServicos'
        ));
    }

    /**
     * Resolve o período a partir de presets (hoje/semana/mes) ou datas custom.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function resolverPeriodo(Request $request): array
    {
        $periodo = $request->input('periodo', 'hoje');

        if ($request->filled('data_inicio') || $request->filled('data_fim')) {
            $inicio = $request->filled('data_inicio') ? Carbon::parse($request->data_inicio) : now();
            $fim    = $request->filled('data_fim') ? Carbon::parse($request->data_fim) : now();
            return [$inicio->startOfDay(), $fim->endOfDay(), 'custom'];
        }

        return match ($periodo) {
            'semana' => [now()->startOfWeek(), now()->endOfWeek(), 'semana'],
            'mes'    => [now()->startOfMonth(), now()->endOfMonth(), 'mes'],
            default  => [now()->startOfDay(), now()->endOfDay(), 'hoje'],
        };
    }
}
