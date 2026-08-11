<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Concerns\HandlesDomainExceptions;
use App\Http\Controllers\Controller;
use App\Services\FidelidadeService;
use Illuminate\Http\Request;

class FidelidadeController extends Controller
{
    use HandlesDomainExceptions;

    public function __construct(private FidelidadeService $fidelidade) {}

    public function index()
    {
        $cliente = auth()->user()->cliente;

        $codigoIndicacao = null;
        $indicacaoAtiva = (bool) config('manicure.indicacao.enabled', true);

        if ($cliente && $indicacaoAtiva) {
            $codigoIndicacao = $cliente->garantirCodigoIndicacao();
        }

        $historico = $cliente
            ? $cliente->pontosFidelidade()->latest()->limit(20)->get()
            : collect();

        $podeResgatar = $cliente ? $this->fidelidade->podeResgatar($cliente) : false;
        $pontosPorBloco = $cliente ? $this->fidelidade->pontosPorBloco($cliente) : 100;
        $valorPorBloco = $cliente ? $this->fidelidade->valorPorBloco($cliente) : 10;
        $pontos = (int) ($cliente !== null ? ($cliente->pontos_fidelidade ?? 0) : 0);
        $blocosDisponiveis = $pontosPorBloco > 0 ? intdiv($pontos, $pontosPorBloco) : 0;
        $pontosParaProximo = $podeResgatar
            ? 0
            : max(0, $pontosPorBloco - $pontos);
        $progressoPct = $pontosPorBloco > 0
            ? min(100, (int) round(($pontos / $pontosPorBloco) * 100))
            : 0;
        $nivel = $cliente ? $this->fidelidade->nivelPara($cliente) : [
            'chave' => 'bronze', 'nome' => 'Bronze', 'pontos_min' => 0, 'multiplicador' => 1.0,
        ];

        return view('cliente.fidelidade.index', compact(
            'cliente',
            'historico',
            'podeResgatar',
            'pontosPorBloco',
            'valorPorBloco',
            'pontos',
            'blocosDisponiveis',
            'pontosParaProximo',
            'progressoPct',
            'codigoIndicacao',
            'indicacaoAtiva',
            'nivel',
        ));
    }

    public function resgatar(Request $request)
    {
        $cliente = auth()->user()->cliente;

        if (! $cliente) {
            return back()->withErrors(['error' => 'Cadastro de cliente não encontrado.']);
        }

        try {
            $cupom = $this->fidelidade->resgatar($cliente, (int) $request->input('blocos', 1));

            return back()->with('success',
                "Resgate concluído! Use o cupom {$cupom->codigo} no seu próximo agendamento. 🎉");
        } catch (\Throwable $e) {
            return $this->domainExceptionBack($e, 'Não foi possível concluir o resgate.');
        }
    }
}
