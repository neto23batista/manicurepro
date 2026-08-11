<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\NotaFiscal;
use App\Models\Salao;
use App\Services\NotaFiscalService;
use Illuminate\Http\Request;

/**
 * UI do stub fiscal / NF-e — NÃO emite na SEFAZ.
 */
class NotaFiscalController extends Controller
{
    public function __construct(private NotaFiscalService $fiscais) {}

    private function salaoId(): int
    {
        return (int) (auth()->user()->salao_id ?? Salao::principalId());
    }

    private function exigeModuloAtivo(): void
    {
        abort_unless(config('manicure.fiscal.enabled'), 404);
    }

    private function autoriza(NotaFiscal $nota): void
    {
        abort_unless($nota->salao_id === $this->salaoId(), 403);
    }

    public function index()
    {
        $this->exigeModuloAtivo();

        $notas = $this->fiscais->list($this->salaoId());

        return view('dono.notas-fiscais.index', compact('notas'));
    }

    public function store(Request $request)
    {
        $this->exigeModuloAtivo();

        $dados = $request->validate([
            'agendamento_id' => ['required', 'integer', 'exists:agendamentos,id'],
        ]);

        $agendamento = Agendamento::with('comanda')->findOrFail($dados['agendamento_id']);
        abort_unless((int) $agendamento->salao_id === $this->salaoId(), 403);
        abort_unless($agendamento->status === 'concluido', 422, 'Só é possível gerar rascunho de NF-e para agendamentos concluídos.');

        $nota = $this->fiscais->emitRascunho($this->salaoId(), $agendamento, $agendamento->comanda);

        return redirect()
            ->route('dono.notas-fiscais.show', $nota)
            ->with('success', 'Rascunho de NF-e criado (stub — NÃO emitir SEFAZ).');
    }

    public function show(NotaFiscal $notaFiscal)
    {
        $this->exigeModuloAtivo();
        $this->autoriza($notaFiscal);

        $notaFiscal->load(['agendamento.cliente', 'comanda']);

        return view('dono.notas-fiscais.show', ['nota' => $notaFiscal]);
    }
}
