<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServicoRequest;
use App\Http\Requests\UpdateServicoRequest;
use App\Models\CategoriaServico;
use App\Models\Salao;
use App\Models\Servico;
use Illuminate\Http\Request;

class ServicoController extends Controller
{
    public function index(Request $request)
    {
        $salaoId = $request->salao_id ?? auth()->user()->salao_id;

        $servicos = Servico::with(['salao', 'categoria'])
            ->when($salaoId, fn($q) => $q->where('salao_id', $salaoId))
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        $saloes = Salao::where('ativo', true)->orderBy('nome')->get();

        return view('admin.servicos.index', compact('servicos', 'saloes', 'salaoId'));
    }

    public function create()
    {
        return view('admin.servicos.create', [
            'saloes'     => Salao::where('ativo', true)->orderBy('nome')->get(),
            'categorias' => CategoriaServico::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function store(StoreServicoRequest $request)
    {
        Servico::create($request->validatedNormalized());

        return redirect()
            ->route('admin.servicos.index')
            ->with('success', 'Serviço cadastrado com sucesso!');
    }

    public function edit(Servico $servico)
    {
        return view('admin.servicos.edit', [
            'servico'    => $servico,
            'saloes'     => Salao::where('ativo', true)->orderBy('nome')->get(),
            'categorias' => CategoriaServico::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function update(UpdateServicoRequest $request, Servico $servico)
    {
        $servico->update($request->validatedNormalized());

        return redirect()
            ->route('admin.servicos.index')
            ->with('success', 'Serviço atualizado com sucesso!');
    }

    public function destroy(Servico $servico)
    {
        $servico->update(['ativo' => false]);

        return back()->with('success', 'Serviço desativado.');
    }
}
