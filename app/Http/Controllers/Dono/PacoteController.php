<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePacoteRequest;
use App\Http\Requests\UpdatePacoteRequest;
use App\Models\Cliente;
use App\Models\Pacote;
use App\Models\Salao;
use App\Services\PacoteService;
use Illuminate\Http\Request;

class PacoteController extends Controller
{
    public function __construct(private PacoteService $pacotes) {}

    private function salaoId(): int
    {
        return (int) (auth()->user()->salao_id ?? Salao::principalId());
    }

    public function index()
    {
        $this->authorize('viewAny', Pacote::class);

        $pacotes = Pacote::where('salao_id', $this->salaoId())
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('dono.pacotes.index', compact('pacotes'));
    }

    public function create()
    {
        $this->authorize('create', Pacote::class);

        return view('dono.pacotes.create');
    }

    public function store(StorePacoteRequest $request)
    {
        $this->authorize('create', Pacote::class);

        $data = $request->validated();
        $data['salao_id'] = $this->salaoId();
        $data['ativo'] = $request->boolean('ativo', true);
        $data['validade_dias'] = $data['validade_dias'] ?? null;

        Pacote::create($data);

        return redirect()
            ->route('dono.pacotes.index')
            ->with('success', 'Pacote criado com sucesso!');
    }

    public function edit(Pacote $pacote)
    {
        $this->authorize('update', $pacote);

        return view('dono.pacotes.edit', compact('pacote'));
    }

    public function update(UpdatePacoteRequest $request, Pacote $pacote)
    {
        $this->authorize('update', $pacote);

        $data = $request->validated();
        $data['ativo'] = $request->boolean('ativo');
        $data['validade_dias'] = $data['validade_dias'] ?? null;

        $pacote->update($data);

        return redirect()
            ->route('dono.pacotes.index')
            ->with('success', 'Pacote atualizado!');
    }

    public function destroy(Pacote $pacote)
    {
        $this->authorize('delete', $pacote);
        $pacote->delete();

        return back()->with('success', 'Pacote excluído.');
    }

    public function atribuir(Request $request, Pacote $pacote)
    {
        $this->authorize('atribuir', $pacote);

        $data = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
        ]);

        $cliente = Cliente::findOrFail($data['cliente_id']);
        abort_unless((int) $cliente->salao_id === $this->salaoId(), 403);

        $this->pacotes->atribuir($pacote, $cliente);

        return back()->with('success', "Pacote \"{$pacote->nome}\" atribuído a {$cliente->nome}.");
    }
}
