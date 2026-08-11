<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\AplicarInventarioRequest;
use App\Models\Produto;
use App\Models\Salao;
use App\Services\EstoqueService;

class InventarioController extends Controller
{
    public function __construct(private EstoqueService $estoque) {}

    private function salaoId(): int
    {
        return (int) (auth()->user()->salao_id ?? Salao::principalId());
    }

    public function create()
    {
        $this->authorize('viewAny', Produto::class);

        $produtos = Produto::where('salao_id', $this->salaoId())
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        return view('dono.estoque.inventario', compact('produtos'));
    }

    public function store(AplicarInventarioRequest $request)
    {
        $this->authorize('viewAny', Produto::class);

        $contagens = collect($request->validated('contagens'))
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->mapWithKeys(fn ($v, $k) => [(int) $k => (float) $v])
            ->all();

        $resultado = $this->estoque->aplicarInventario(
            $this->salaoId(),
            $contagens,
            auth()->id(),
        );

        $msg = $resultado['ajustes'] === 0
            ? 'Inventário conferido — nenhum ajuste necessário.'
            : "Inventário aplicado: {$resultado['ajustes']} ajuste(s) registrados.";

        return redirect()
            ->route('dono.estoque.inventario.create')
            ->with('success', $msg);
    }
}
