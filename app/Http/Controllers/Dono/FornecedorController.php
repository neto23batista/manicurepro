<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFornecedorRequest;
use App\Http\Requests\UpdateFornecedorRequest;
use App\Models\Fornecedor;
use App\Models\Salao;

class FornecedorController extends Controller
{
    private function salaoId(): int
    {
        return (int) (auth()->user()->salao_id ?? Salao::principalId());
    }

    public function index()
    {
        $this->authorize('viewAny', Fornecedor::class);

        $fornecedores = Fornecedor::where('salao_id', $this->salaoId())
            ->withCount('produtos')
            ->orderBy('nome')
            ->paginate(20);

        return view('dono.fornecedores.index', compact('fornecedores'));
    }

    public function create()
    {
        $this->authorize('create', Fornecedor::class);

        return view('dono.fornecedores.create');
    }

    public function store(StoreFornecedorRequest $request)
    {
        $this->authorize('create', Fornecedor::class);

        $data = $request->validated();
        $data['salao_id'] = $this->salaoId();
        $data['ativo'] = $request->boolean('ativo', true);

        Fornecedor::create($data);

        return redirect()->route('dono.fornecedores.index')
            ->with('success', 'Fornecedor cadastrado!');
    }

    public function edit(Fornecedor $fornecedor)
    {
        $this->authorize('update', $fornecedor);

        return view('dono.fornecedores.edit', compact('fornecedor'));
    }

    public function update(UpdateFornecedorRequest $request, Fornecedor $fornecedor)
    {
        $this->authorize('update', $fornecedor);

        $data = $request->validated();
        $data['ativo'] = $request->boolean('ativo', true);

        $fornecedor->update($data);

        return redirect()->route('dono.fornecedores.index')
            ->with('success', 'Fornecedor atualizado!');
    }

    public function destroy(Fornecedor $fornecedor)
    {
        $this->authorize('delete', $fornecedor);
        $fornecedor->update(['ativo' => false]);

        return back()->with('success', 'Fornecedor desativado.');
    }
}
