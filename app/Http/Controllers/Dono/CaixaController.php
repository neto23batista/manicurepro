<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\AbrirCaixaRequest;
use App\Http\Requests\FecharCaixaRequest;
use App\Http\Requests\MovimentarCaixaRequest;
use App\Models\Caixa;
use App\Models\CaixaMovimentacao;
use App\Models\Salao;
use App\Services\CaixaService;

class CaixaController extends Controller
{
    public function __construct(private CaixaService $caixas) {}

    private function salao(): Salao
    {
        $salao = auth()->user()->salao ?? Salao::principal();
        abort_if($salao === null, 404, 'Nenhum salão configurado.');

        return $salao;
    }

    public function index()
    {
        $this->authorize('viewAny', Caixa::class);

        $salao = $this->salao();
        $aberto = $this->caixas->aberto($salao->id);

        if ($aberto) {
            $aberto->load(['movimentacoes.user', 'abertoPor']);
        }

        $historico = $this->caixas->historico($salao->id);
        $saldoCalculado = $aberto ? $this->caixas->saldoCalculado($aberto) : null;

        return view('dono.financeiro.caixa', compact(
            'salao', 'aberto', 'historico', 'saldoCalculado',
        ));
    }

    public function abrir(AbrirCaixaRequest $request)
    {
        $this->authorize('create', Caixa::class);

        $salao = $this->salao();

        $this->caixas->abrir(
            $salao->id,
            (float) $request->saldo_inicial,
            auth()->id(),
            $request->observacao,
        );

        return redirect()
            ->route('dono.financeiro.caixa.index')
            ->with('success', 'Caixa aberto com sucesso.');
    }

    public function movimentar(MovimentarCaixaRequest $request, Caixa $caixa)
    {
        $this->authorize('update', $caixa);
        abort_unless($caixa->salao_id === $this->salao()->id, 403);

        $this->caixas->movimentar(
            $caixa,
            $request->tipo,
            (float) $request->valor,
            $request->descricao,
            auth()->id(),
        );

        $labels = CaixaMovimentacao::TIPOS_LABELS;

        return redirect()
            ->route('dono.financeiro.caixa.index')
            ->with('success', ($labels[$request->tipo] ?? 'Movimentação').' registrada.');
    }

    public function fechar(FecharCaixaRequest $request, Caixa $caixa)
    {
        $this->authorize('update', $caixa);
        abort_unless($caixa->salao_id === $this->salao()->id, 403);

        $fechado = $this->caixas->fechar(
            $caixa,
            (float) $request->saldo_final_informado,
            auth()->id(),
            $request->observacao,
        );

        $msg = 'Caixa fechado.';
        if ((float) $fechado->diferenca !== 0.0) {
            $diff = number_format(abs((float) $fechado->diferenca), 2, ',', '.');
            $sinal = (float) $fechado->diferenca > 0 ? 'sobra' : 'falta';
            $msg .= " Diferença: {$sinal} de R$ {$diff}.";
        }

        return redirect()
            ->route('dono.financeiro.caixa.index')
            ->with('success', $msg);
    }

    public function show(Caixa $caixa)
    {
        $this->authorize('view', $caixa);
        abort_unless($caixa->salao_id === $this->salao()->id, 403);

        $caixa->load(['movimentacoes.user', 'abertoPor', 'fechadoPor']);
        $saldoCalculado = $this->caixas->saldoCalculado($caixa);

        return view('dono.financeiro.caixa-show', compact('caixa', 'saldoCalculado'));
    }
}
