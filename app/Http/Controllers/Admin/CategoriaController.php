<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use App\Models\CategoriaServico;
use App\Models\Salao;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $categorias = CategoriaServico::with('salao')
            ->withCount('servicos')
            ->when($request->salao_id, fn($q) => $q->where('salao_id', $request->salao_id))
            ->orderBy('salao_id')
            ->orderBy('ordem')
            ->orderBy('nome')
            ->paginate(20);

        return view('admin.categorias.index', [
            'categorias' => $categorias,
            'saloes'     => Salao::where('ativo', true)->orderBy('nome')->get(),
            'salaoId'    => $request->salao_id,
        ]);
    }

    public function store(StoreCategoriaRequest $request)
    {
        $data = $request->validated();
        $data['ativo'] = true;
        $data['ordem'] ??= 0;

        CategoriaServico::create($data);

        return back()->with('success', 'Categoria criada!');
    }

    public function update(UpdateCategoriaRequest $request, CategoriaServico $categoria)
    {
        $data = $request->validated();
        $data['ativo'] = $request->boolean('ativo', true);
        $categoria->update($data);

        return back()->with('success', 'Categoria atualizada!');
    }

    public function destroy(CategoriaServico $categoria)
    {
        if ($categoria->servicos()->exists()) {
            return back()->withErrors(['error' => 'Existe(m) serviço(s) vinculado(s). Desative em vez de excluir.']);
        }
        $categoria->delete();
        return back()->with('success', 'Categoria excluída.');
    }
}
