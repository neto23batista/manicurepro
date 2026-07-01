<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdutoRequest;
use App\Http\Requests\UpdateProdutoRequest;
use App\Models\Produto;
use App\Models\Salao;
use App\Services\EstoqueService;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{
    public function __construct(private EstoqueService $estoque) {}

    private function salaoId(): int
    {
        // Admin não tem salão vinculado — cai no salão único (single-tenant).
        return (int) (auth()->user()->salao_id ?? Salao::principalId());
    }

    private function autoriza(Produto $produto): void
    {
        abort_unless($produto->salao_id === $this->salaoId(), 403);
    }

    public function index(Request $request)
    {
        $salaoId = $this->salaoId();

        $produtos = Produto::where('salao_id', $salaoId)
            ->when($request->filled('busca'), function ($q) use ($request) {
                $busca = $request->busca;
                $q->where(fn ($s) => $s->where('nome', 'like', "%{$busca}%")
                    ->orWhere('marca', 'like', "%{$busca}%")
                    ->orWhere('codigo', 'like', "%{$busca}%"));
            })
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        $baixoEstoque = Produto::where('salao_id', $salaoId)
            ->where('ativo', true)
            ->whereColumn('estoque_atual', '<=', 'estoque_minimo')
            ->count();

        return view('dono.produtos.index', compact('produtos', 'baixoEstoque'));
    }

    public function create()
    {
        return view('dono.produtos.create');
    }

    public function store(StoreProdutoRequest $request)
    {
        $data = $request->validated();
        $inicial = (float) ($data['estoque_atual'] ?? 0);

        $data['salao_id']      = $this->salaoId();
        $data['ativo']         = $request->boolean('ativo', true);
        $data['estoque_atual'] = 0; // o estoque inicial entra como movimentação

        $produto = Produto::create($data);

        if ($inicial > 0) {
            $this->estoque->movimentar(
                $produto,
                'entrada',
                $inicial,
                auth()->id(),
                'Estoque inicial',
                (float) $produto->preco_custo
            );
        }

        return redirect()->route('dono.produtos.index')
            ->with('success', 'Produto cadastrado com sucesso!');
    }

    public function edit(Produto $produto)
    {
        $this->autoriza($produto);
        $movimentacoes = $produto->movimentacoes()
            ->with('user')
            ->latest()
            ->take(15)
            ->get();

        return view('dono.produtos.edit', compact('produto', 'movimentacoes'));
    }

    public function update(UpdateProdutoRequest $request, Produto $produto)
    {
        $this->autoriza($produto);

        $data = $request->validated();
        $data['ativo'] = $request->boolean('ativo', true);

        $produto->update($data);

        return redirect()->route('dono.produtos.index')
            ->with('success', 'Produto atualizado!');
    }

    public function destroy(Produto $produto)
    {
        $this->autoriza($produto);
        $produto->update(['ativo' => false]);

        return back()->with('success', 'Produto desativado.');
    }

    /**
     * Registra uma movimentação de estoque (entrada/saída/ajuste).
     */
    public function movimentar(Request $request, Produto $produto)
    {
        $this->autoriza($produto);

        $request->validate([
            'tipo'       => 'required|in:entrada,saida,ajuste',
            'quantidade' => 'required|numeric|min:0.001|max:999999',
            'motivo'     => 'nullable|string|max:255',
        ]);

        $this->estoque->movimentar(
            $produto,
            $request->tipo,
            (float) $request->quantidade,
            auth()->id(),
            $request->motivo
        );

        return back()->with('success', 'Estoque atualizado!');
    }
}
