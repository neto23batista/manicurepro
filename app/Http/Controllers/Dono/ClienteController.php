<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Concerns\AuthorizesSalao;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    use AuthorizesSalao;

    public function index(Request $request)
    {
        $salao = auth()->user()->salao;

        $clientes = $salao->clientes()
            ->when($request->search, function ($q) use ($request) {
                $s = '%' . $request->search . '%';
                $q->where(function ($q2) use ($s) {
                    $q2->where('nome', 'like', $s)
                       ->orWhere('email', 'like', $s)
                       ->orWhere('telefone', 'like', $s)
                       ->orWhere('cpf', 'like', $s);
                });
            })
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return view('dono.clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('dono.clientes.create');
    }

    public function store(StoreClienteRequest $request)
    {
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
        $this->authorizeSalaoOwnership($cliente);
        $cliente->load(['agendamentos' => fn($q) => $q->latest('data_hora_inicio')->take(20)->with(['servicos', 'manicure'])]);
        return view('dono.clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente)
    {
        $this->authorizeSalaoOwnership($cliente);
        return view('dono.clientes.edit', compact('cliente'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        $this->authorizeSalaoOwnership($cliente);

        $data = $request->validated();
        $data['ativo'] = $request->boolean('ativo', true);
        $cliente->update($data);

        return redirect()
            ->route('dono.clientes.index')
            ->with('success', 'Cliente atualizado!');
    }

    public function destroy(Cliente $cliente)
    {
        $this->authorizeSalaoOwnership($cliente);
        $cliente->update(['ativo' => false]);
        return back()->with('success', 'Cliente desativado.');
    }
}
