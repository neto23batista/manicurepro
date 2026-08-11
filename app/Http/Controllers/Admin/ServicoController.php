<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServicoRequest;
use App\Http\Requests\UpdateServicoRequest;
use App\Models\CategoriaServico;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\ServicoVariacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServicoController extends Controller
{
    public function index(Request $request)
    {
        $salaoId = $request->salao_id ?? auth()->user()->salao_id;

        $servicos = Servico::with(['salao', 'categoria'])
            ->when($salaoId, fn ($q) => $q->where('salao_id', $salaoId))
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
        $data = $request->validatedNormalized();

        if ($request->hasFile('imagem')) {
            $data['imagem'] = $request->file('imagem')->store('servicos', 'public');
        }

        $servico = Servico::create($data);
        $this->syncVariacoes($servico, $request->input('variacoes', []));

        return redirect()
            ->route('admin.servicos.index')
            ->with('success', 'Serviço cadastrado com sucesso!');
    }

    public function edit(Servico $servico)
    {
        $servico->load('variacoes');

        return view('admin.servicos.edit', [
            'servico'    => $servico,
            'saloes'     => Salao::where('ativo', true)->orderBy('nome')->get(),
            'categorias' => CategoriaServico::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function update(UpdateServicoRequest $request, Servico $servico)
    {
        $data = $request->validatedNormalized();

        if ($request->boolean('remover_imagem') && $servico->imagem) {
            $this->deleteIfExists($servico->imagem);
            $data['imagem'] = null;
        }

        if ($request->hasFile('imagem')) {
            $this->deleteIfExists($servico->imagem);
            $data['imagem'] = $request->file('imagem')->store('servicos', 'public');
        }

        $servico->update($data);
        $this->syncVariacoes($servico, $request->input('variacoes', []));

        return redirect()
            ->route('admin.servicos.index')
            ->with('success', 'Serviço atualizado com sucesso!');
    }

    public function destroy(Servico $servico)
    {
        $servico->update(['ativo' => false]);

        return back()->with('success', 'Serviço desativado.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $variacoes
     */
    private function syncVariacoes(Servico $servico, array $variacoes): void
    {
        $keepIds = [];

        foreach (array_values($variacoes) as $i => $row) {
            if (! is_array($row) || blank($row['nome'] ?? null)) {
                continue;
            }

            $payload = [
                'nome'    => (string) $row['nome'],
                'preco'   => (float) $row['preco'],
                'duracao' => (int) $row['duracao'],
                'ordem'   => (int) ($row['ordem'] ?? $i),
                'ativo'   => filter_var($row['ativo'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];

            if (! empty($row['id'])) {
                $variacao = ServicoVariacao::where('servico_id', $servico->id)
                    ->whereKey($row['id'])
                    ->first();
                if ($variacao) {
                    $variacao->update($payload);
                    $keepIds[] = $variacao->id;
                    continue;
                }
            }

            $created = $servico->variacoes()->create($payload);
            $keepIds[] = $created->id;
        }

        $servico->variacoes()->whereNotIn('id', $keepIds)->delete();
    }

    private function deleteIfExists(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
