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

        $salao = auth()->user()->salao;
        $clientes = $salao->clientes()->orderBy('nome')->limit(500)->get(['id', 'nome']);
        $servicos = $salao->servicos()->where('ativo', true)->orderBy('nome')->get(['id', 'nome']);

        return view('dono.cupons.create', compact('clientes', 'servicos'));
    }

    public function store(StoreCupomRequest $request)
    {
        $this->authorize('create', Cupom::class);

        $data = $request->validated();
        $data['codigo'] = strtoupper($data['codigo']);
        $data['salao_id'] = auth()->user()->salao_id;
        $data['uso_atual'] = 0;
        $data['ativo'] = $request->boolean('ativo', true);
        $data['primeira_compra'] = $request->boolean('primeira_compra');
        $data['anti_stacking_fidelidade'] = $request->boolean('anti_stacking_fidelidade');
        $data['origem'] = 'manual';
        $data['cliente_id'] = $data['cliente_id'] ?? null;
        $data['servico_id'] = $data['servico_id'] ?? null;
        $data['minimo_pedido'] = $data['minimo_pedido'] ?? 0;

        Cupom::create($data);

        return redirect()
            ->route('dono.cupons.index')
            ->with('success', 'Cupom criado com sucesso!');
    }

    public function edit(Cupom $cupom)
    {
        $this->authorize('update', $cupom);

        $salao = auth()->user()->salao;
        $clientes = $salao->clientes()->orderBy('nome')->limit(500)->get(['id', 'nome']);
        $servicos = $salao->servicos()->where('ativo', true)->orderBy('nome')->get(['id', 'nome']);

        return view('dono.cupons.edit', compact('cupom', 'clientes', 'servicos'));
    }

    public function update(UpdateCupomRequest $request, Cupom $cupom)
    {
        $this->authorize('update', $cupom);

        $data = $request->validated();
        $data['codigo'] = strtoupper($data['codigo']);
        $data['ativo'] = $request->boolean('ativo');
        $data['primeira_compra'] = $request->boolean('primeira_compra');
        $data['anti_stacking_fidelidade'] = $request->boolean('anti_stacking_fidelidade');
        $data['cliente_id'] = $data['cliente_id'] ?? null;
        $data['servico_id'] = $data['servico_id'] ?? null;
        $data['minimo_pedido'] = $data['minimo_pedido'] ?? 0;

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
