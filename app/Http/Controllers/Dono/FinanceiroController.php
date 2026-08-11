<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComissaoPagamentoRequest;
use App\Models\ComissaoPagamento;
use App\Models\Salao;
use App\Services\FinanceiroService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceiroController extends Controller
{
    public function __construct(private FinanceiroService $financeiro) {}

    public function index(Request $request)
    {
        // Admin não tem salão vinculado — cai no salão único (single-tenant).
        $salao = auth()->user()->salao ?? Salao::principal();
        abort_if($salao === null, 404, 'Nenhum salão configurado.');

        [$inicio, $fim, $periodo] = $this->resolverPeriodo($request);

        $caixa = $this->financeiro->caixa($salao->id, $inicio, $fim);
        $comissoes = $this->financeiro->comissoes($salao->id, $inicio, $fim);
        $repasses = $this->financeiro->pagamentosDoPeriodo($salao->id, $inicio, $fim);
        $historico = $this->financeiro->historicoPagamentos($salao->id);

        $totalComissoes = (float) $comissoes->sum('comissao');
        $totalServicos = (float) $comissoes->sum('base');
        $totalAPagar = (float) $comissoes->sum('a_pagar');
        $totalPago = (float) $comissoes->sum(fn ($c) => $c['pago'] ? (float) $c['valor_pago'] : 0);

        return view('dono.financeiro.index', compact(
            'salao', 'inicio', 'fim', 'periodo',
            'caixa', 'comissoes', 'repasses', 'historico',
            'totalComissoes', 'totalServicos', 'totalAPagar', 'totalPago',
        ));
    }

    public function storePagamento(StoreComissaoPagamentoRequest $request)
    {
        $salao = auth()->user()->salao ?? Salao::principal();
        abort_if($salao === null, 404, 'Nenhum salão configurado.');

        $inicio = Carbon::parse($request->data_inicio)->startOfDay();
        $fim = Carbon::parse($request->data_fim)->endOfDay();

        $this->financeiro->marcarPago(
            $salao->id,
            (int) $request->manicure_id,
            $inicio,
            $fim,
            auth()->id(),
            $request->observacao,
        );

        return redirect()
            ->route('dono.financeiro.index', $this->queryPeriodo($request, $inicio, $fim))
            ->with('success', 'Comissão marcada como paga.');
    }

    public function destroyPagamento(ComissaoPagamento $pagamento)
    {
        $salao = auth()->user()->salao ?? Salao::principal();
        abort_if($salao === null, 404, 'Nenhum salão configurado.');

        $inicio = $pagamento->periodo_inicio->copy()->startOfDay();
        $fim = $pagamento->periodo_fim->copy()->endOfDay();

        $this->financeiro->desfazerPagamento($pagamento, $salao->id);

        return redirect()
            ->route('dono.financeiro.index', [
                'data_inicio' => $inicio->toDateString(),
                'data_fim'    => $fim->toDateString(),
            ])
            ->with('success', 'Repasse desfeito. Comissão voltou a constar como a pagar.');
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
            $fim = $request->filled('data_fim') ? Carbon::parse($request->data_fim) : now();

            return [$inicio->startOfDay(), $fim->endOfDay(), 'custom'];
        }

        return match ($periodo) {
            'semana' => [now()->startOfWeek(), now()->endOfWeek(), 'semana'],
            'mes'    => [now()->startOfMonth(), now()->endOfMonth(), 'mes'],
            default  => [now()->startOfDay(), now()->endOfDay(), 'hoje'],
        };
    }

    /**
     * Preserva o filtro de período após POST de repasse.
     *
     * @return array<string, string>
     */
    private function queryPeriodo(Request $request, Carbon $inicio, Carbon $fim): array
    {
        $periodo = $request->input('periodo');

        if (in_array($periodo, ['hoje', 'semana', 'mes'], true)) {
            return ['periodo' => $periodo];
        }

        return [
            'data_inicio' => $inicio->toDateString(),
            'data_fim'    => $fim->toDateString(),
        ];
    }
}
