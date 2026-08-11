<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCupomRequest;
use App\Http\Requests\UpdateCupomRequest;
use App\Models\Cupom;

class CupomController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Cupom::class);

        $salao = auth()->user()->salao;
        $cupons = $salao->cupons()->orderByDesc('created_at')->paginate(20);

        return view('dono.cupons.index', compact('cupons'));
    }

    public function create()
    {
        $this->authorize('create', Cupom::class);

        return view('dono.cupons.create');
    }

    public function store(StoreCupomRequest $request)
    {
        $this->authorize('create', Cupom::class);

        $data = $request->validated();
        $data['codigo'] = strtoupper($data['codigo']);
        $data['salao_id'] = auth()->user()->salao_id;
        $data['uso_atual'] = 0;
        $data['ativo'] = $request->boolean('ativo', true);

        Cupom::create($data);

        return redirect()
            ->route('dono.cupons.index')
            ->with('success', 'Cupom criado com sucesso!');
    }

    public function edit(Cupom $cupom)
    {
        $this->authorize('update', $cupom);

        return view('dono.cupons.edit', compact('cupom'));
    }

    public function update(UpdateCupomRequest $request, Cupom $cupom)
    {
        $this->authorize('update', $cupom);

        $data = $request->validated();
        $data['codigo'] = strtoupper($data['codigo']);
        $data['ativo'] = $request->boolean('ativo');

        $cupom->update($data);

        return redirect()
            ->route('dono.cupons.index')
            ->with('success', 'Cupom atualizado!');
    }

    public function destroy(Cupom $cupom)
    {
        $this->authorize('delete', $cupom);
        $cupom->delete();

        return back()->with('success', 'Cupom excluído.');
    }
}
