<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDespesaRequest;
use App\Http\Requests\UpdateDespesaRequest;
use App\Models\Despesa;
use App\Models\Salao;
use Illuminate\Http\Request;

class DespesaController extends Controller
{
    private function salao(): Salao
    {
        $salao = auth()->user()->salao ?? Salao::principal();
        abort_if($salao === null, 404, 'Nenhum salão configurado.');

        return $salao;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Despesa::class);

        $salao = $this->salao();
        $status = $request->input('status', 'todas');

        $despesas = Despesa::where('salao_id', $salao->id)
            ->when($status === 'pendentes', fn ($q) => $q->pendentes())
            ->when($status === 'pagas', fn ($q) => $q->whereNotNull('pago_em'))
            ->when($status === 'vencidas', fn ($q) => $q->pendentes()->whereDate('vencimento', '<', today()))
            ->when($request->filled('categoria'), fn ($q) => $q->where('categoria', $request->categoria))
            ->orderByRaw('CASE WHEN pago_em IS NULL THEN 0 ELSE 1 END')
            ->orderBy('vencimento')
            ->paginate(20)
            ->withQueryString();

        $resumo = [
            'pendentes' => (float) Despesa::where('salao_id', $salao->id)->pendentes()->sum('valor'),
            'pagas_mes' => (float) Despesa::where('salao_id', $salao->id)
                ->whereNotNull('pago_em')
                ->whereBetween('pago_em', [now()->copy()->startOfMonth(), now()->copy()->endOfMonth()])
                ->sum('valor'),
            'vencidas'  => (int) Despesa::where('salao_id', $salao->id)
                ->pendentes()
                ->whereDate('vencimento', '<', today())
                ->count(),
        ];

        $categorias = Despesa::CATEGORIAS;

        return view('dono.financeiro.despesas', compact(
            'salao', 'despesas', 'resumo', 'categorias', 'status',
        ));
    }

    public function store(StoreDespesaRequest $request)
    {
        $this->authorize('create', Despesa::class);

        $salao = $this->salao();
        $data = $request->validated();
        $data['salao_id'] = $salao->id;
        $data['user_id'] = auth()->id();
        $data['recorrente'] = $request->boolean('recorrente');

        if ($request->boolean('pago')) {
            $data['pago_em'] = now();
        }

        unset($data['pago']);

        Despesa::create($data);

        return redirect()
            ->route('dono.financeiro.despesas.index')
            ->with('success', 'Despesa cadastrada.');
    }

    public function update(UpdateDespesaRequest $request, Despesa $despesa)
    {
        $this->authorize('update', $despesa);
        abort_unless($despesa->salao_id === $this->salao()->id, 403);

        $data = $request->validated();
        $data['recorrente'] = $request->boolean('recorrente');

        $despesa->update($data);

        return redirect()
            ->route('dono.financeiro.despesas.index')
            ->with('success', 'Despesa atualizada.');
    }

    public function marcarPaga(Despesa $despesa)
    {
        $this->authorize('update', $despesa);
        abort_unless($despesa->salao_id === $this->salao()->id, 403);

        if ($despesa->estaPaga()) {
            return redirect()
                ->route('dono.financeiro.despesas.index')
                ->with('success', 'Despesa já estava marcada como paga.');
        }

        $despesa->update(['pago_em' => now()]);

        return redirect()
            ->route('dono.financeiro.despesas.index')
            ->with('success', 'Despesa marcada como paga.');
    }

    public function destroy(Despesa $despesa)
    {
        $this->authorize('delete', $despesa);
        abort_unless($despesa->salao_id === $this->salao()->id, 403);

        $despesa->delete();

        return redirect()
            ->route('dono.financeiro.despesas.index')
            ->with('success', 'Despesa excluída.');
    }
}
