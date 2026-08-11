<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\MovimentarEstoqueRequest;
use App\Http\Requests\StoreProdutoRequest;
use App\Http\Requests\UpdateProdutoRequest;
use App\Models\Fornecedor;
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

    public function index(Request $request)
    {
        $this->authorize('viewAny', Produto::class);

        $salaoId = $this->salaoId();

        $produtos = Produto::where('salao_id', $salaoId)
            ->with('fornecedor:id,nome')
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
            ->estoqueBaixo()
            ->count();

        return view('dono.produtos.index', compact('produtos', 'baixoEstoque'));
    }

    public function create()
    {
        $this->authorize('create', Produto::class);

        $fornecedores = $this->fornecedoresAtivos();

        return view('dono.produtos.create', compact('fornecedores'));
    }

    public function store(StoreProdutoRequest $request)
    {
        $this->authorize('create', Produto::class);

        $data = $request->validated();
        $inicial = (float) ($data['estoque_atual'] ?? 0);

        $data['salao_id'] = $this->salaoId();
        $data['ativo'] = $request->boolean('ativo', true);
        $data['estoque_atual'] = 0; // o estoque inicial entra como movimentação

        $produto = Produto::create($data);

        if ($inicial > 0) {
            $this->estoque->movimentar(
                $produto,
                'entrada',
                $inicial,
                auth()->id(),
                'Estoque inicial',
                (float) $produto->preco_custo,
            );
        }

        return redirect()->route('dono.produtos.index')
            ->with('success', 'Produto cadastrado com sucesso!');
    }

    public function edit(Produto $produto)
    {
        $this->authorize('update', $produto);
        $movimentacoes = $produto->movimentacoes()
            ->with('user')
            ->latest()
            ->take(15)
            ->get();
        $fornecedores = $this->fornecedoresAtivos();

        return view('dono.produtos.edit', compact('produto', 'movimentacoes', 'fornecedores'));
    }

    public function update(UpdateProdutoRequest $request, Produto $produto)
    {
        $this->authorize('update', $produto);

        $data = $request->validated();
        $data['ativo'] = $request->boolean('ativo', true);

        $produto->update($data);

        return redirect()->route('dono.produtos.index')
            ->with('success', 'Produto atualizado!');
    }

    public function destroy(Produto $produto)
    {
        $this->authorize('delete', $produto);
        $produto->update(['ativo' => false]);

        return back()->with('success', 'Produto desativado.');
    }

    /**
     * Registra uma movimentação de estoque (entrada/saída/ajuste/perda/consumo/devolução).
     */
    public function movimentar(MovimentarEstoqueRequest $request, Produto $produto)
    {
        $this->authorize('update', $produto);

        $data = $request->validated();

        $this->estoque->movimentar(
            $produto,
            $data['tipo'],
            (float) $data['quantidade'],
            auth()->id(),
            $data['motivo'] ?? null,
        );

        return back()->with('success', 'Estoque atualizado!');
    }

    /** @return \Illuminate\Support\Collection<int, Fornecedor> */
    private function fornecedoresAtivos()
    {
        return Fornecedor::where('salao_id', $this->salaoId())
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome']);
    }
}
