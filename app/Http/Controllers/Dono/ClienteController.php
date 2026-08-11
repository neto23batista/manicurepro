<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Services\ClienteSegmentacao;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function __construct(private ClienteSegmentacao $crm) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Cliente::class);

        $salao = auth()->user()->salao;
        $segmento = $request->string('segmento')->toString();

        $query = $salao->clientes()
            ->when($request->search, function ($q) use ($request) {
                $s = '%'.$request->search.'%';
                $q->where(function ($q2) use ($s) {
                    $q2->where('nome', 'like', $s)
                        ->orWhere('email', 'like', $s)
                        ->orWhere('telefone', 'like', $s)
                        ->orWhere('cpf', 'like', $s);
                });
            });

        if ($this->crm->isSegmentoValido($segmento)) {
            $this->crm->aplicarFiltro($query, $segmento);
        }

        $this->crm->withUltimaVisita($query);

        $clientes = $query
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        $segmentos = ClienteSegmentacao::SEGMENTOS;
        $segmentoAtual = $this->crm->isSegmentoValido($segmento) ? $segmento : null;

        return view('dono.clientes.index', compact('clientes', 'segmentos', 'segmentoAtual'));
    }

    public function create()
    {
        $this->authorize('create', Cliente::class);

        return view('dono.clientes.create');
    }

    public function store(StoreClienteRequest $request)
    {
        $this->authorize('create', Cliente::class);

        $data = $request->validated();
        $data['salao_id'] = auth()->user()->salao_id;
        $data['ativo'] = true;

        Cliente::create($data);

        return redirect()
            ->route('dono.clientes.index')
            ->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function show(Cliente $cliente)
    {
        $this->authorize('view', $cliente);
        $cliente->load([
            'agendamentos'   => fn ($q) => $q->latest('data_hora_inicio')->take(20)->with(['servicos', 'manicure']),
            'fichaHistorico' => fn ($q) => $q->with('user')->limit(10),
        ]);

        $metricas = $this->crm->metricas($cliente);

        return view('dono.clientes.show', compact('cliente', 'metricas'));
    }

    public function edit(Cliente $cliente)
    {
        $this->authorize('update', $cliente);

        return view('dono.clientes.edit', compact('cliente'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        $this->authorize('update', $cliente);

        $data = $request->validated();
        $data['ativo'] = $request->boolean('ativo', true);
        $cliente->update($data);

        return redirect()
            ->route('dono.clientes.index')
            ->with('success', 'Cliente atualizado!');
    }

    public function destroy(Cliente $cliente)
    {
        $this->authorize('delete', $cliente);
        $cliente->update(['ativo' => false]);

        return back()->with('success', 'Cliente desativado.');
    }

    /**
     * Gera cupom de reativação (reusa Cupom) para cliente no segmento inativo.
     */
    public function reativar(Cliente $cliente)
    {
        $this->authorize('update', $cliente);

        $cupom = $this->crm->gerarCupomReativacao($cliente);

        return back()->with(
            'success',
            'Cupom de reativação: '.$cupom->codigo
            .' (válido até '.$cupom->validade?->format('d/m/Y').').'
        );
    }
}
