<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Services\FidelidadeService;
use Illuminate\Http\Request;

class FidelidadeController extends Controller
{
    public function __construct(private FidelidadeService $fidelidade) {}

    public function index()
    {
        $cliente = auth()->user()->cliente;

        $historico = $cliente
            ? $cliente->pontosFidelidade()->latest()->limit(20)->get()
            : collect();

        $podeResgatar = $cliente ? $this->fidelidade->podeResgatar($cliente) : false;
        $pontosPorBloco = $cliente ? $this->fidelidade->pontosPorBloco($cliente) : 100;
        $valorPorBloco = $cliente ? $this->fidelidade->valorPorBloco($cliente) : 10;

        return view('cliente.fidelidade.index', compact(
            'cliente', 'historico', 'podeResgatar', 'pontosPorBloco', 'valorPorBloco'
        ));
    }

    public function resgatar(Request $request)
    {
        $cliente = auth()->user()->cliente;

        if (!$cliente) {
            return back()->withErrors(['error' => 'Cadastro de cliente não encontrado.']);
        }

        try {
            $cupom = $this->fidelidade->resgatar($cliente, (int) $request->input('blocos', 1));

            return back()->with('success',
                "Resgate concluído! Use o cupom {$cupom->codigo} no seu próximo agendamento. 🎉");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
