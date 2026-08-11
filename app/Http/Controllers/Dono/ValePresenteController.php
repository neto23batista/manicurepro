<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Models\Salao;
use App\Models\ValePresente;
use App\Services\ValePresenteService;
use Illuminate\Http\Request;

class ValePresenteController extends Controller
{
    public function __construct(private ValePresenteService $vales) {}

    private function salaoId(): int
    {
        // Admin não tem salão vinculado — cai no salão único (single-tenant).
        return (int) (auth()->user()->salao_id ?? Salao::principalId());
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', ValePresente::class);

        $salaoId = $this->salaoId();

        $vales = ValePresente::where('salao_id', $salaoId)
            ->when($request->filled('busca'), function ($q) use ($request) {
                $busca = $request->busca;
                $q->where(fn ($s) => $s->where('codigo', 'like', "%{$busca}%")
                    ->orWhere('comprador_nome', 'like', "%{$busca}%")
                    ->orWhere('beneficiario_nome', 'like', "%{$busca}%"));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Uma query agregada; "em circulação" segue a semântica de scopeDisponiveis
        // (ativo + saldo > 0 + não expirado) — vale vencido não infla o card.
        $hoje = today()->toDateString();
        $agregado = ValePresente::where('salao_id', $salaoId)
            ->selectRaw('COALESCE(SUM(valor), 0) as emitido')
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN status = ? AND saldo > 0 AND (validade IS NULL OR validade >= ?) THEN saldo ELSE 0 END), 0) as saldo",
                [ValePresente::STATUS_ATIVO, $hoje]
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN status = ? AND saldo > 0 AND (validade IS NULL OR validade >= ?) THEN 1 ELSE 0 END), 0) as ativos",
                [ValePresente::STATUS_ATIVO, $hoje]
            )
            ->first();

        $resumo = [
            'emitido' => (float) $agregado->emitido,
            'saldo'   => (float) $agregado->saldo,
            'ativos'  => (int) $agregado->ativos,
        ];

        return view('dono.vales.index', compact('vales', 'resumo'));
    }

    public function store(\App\Http\Requests\StoreValePresenteRequest $request)
    {
        $this->authorize('create', ValePresente::class);

        $vale = $this->vales->criar($this->salaoId(), $request->validated());

        return redirect()->route('dono.vales.show', $vale)
            ->with('success', "Vale-presente {$vale->codigo} emitido!");
    }

    public function show(ValePresente $vale)
    {
        $this->authorize('view', $vale);

        return view('dono.vales.show', compact('vale'));
    }

    public function cancelar(ValePresente $vale)
    {
        $this->authorize('delete', $vale);
        $vale->update(['status' => ValePresente::STATUS_CANCELADO]);

        return redirect()->route('dono.vales.index')
            ->with('success', "Vale-presente {$vale->codigo} cancelado.");
    }
}
