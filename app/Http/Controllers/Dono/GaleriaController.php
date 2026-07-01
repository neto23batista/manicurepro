<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGaleriaFotoRequest;
use App\Models\GaleriaFoto;
use App\Models\Salao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GaleriaController extends Controller
{
    private function salaoId(): int
    {
        // Admin não tem salão vinculado — cai no salão único (single-tenant).
        return (int) (auth()->user()->salao_id ?? Salao::principalId());
    }

    private function autoriza(GaleriaFoto $foto): void
    {
        abort_unless($foto->salao_id === $this->salaoId(), 403);
    }

    public function index()
    {
        $salaoId = $this->salaoId();

        $fotos = GaleriaFoto::where('salao_id', $salaoId)
            ->with('manicure')
            ->orderBy('ordem')
            ->orderByDesc('id')
            ->paginate(24);

        $manicures = (auth()->user()->salao ?? Salao::principal())?->manicures ?? collect();

        return view('dono.galeria.index', compact('fotos', 'manicures'));
    }

    public function store(StoreGaleriaFotoRequest $request)
    {
        $salaoId  = $this->salaoId();
        $publicar = $request->boolean('publicar', true);
        $titulo   = $request->input('titulo');
        $manicureId = $request->filled('manicure_id') ? (int) $request->manicure_id : null;

        $ordemBase = (int) GaleriaFoto::where('salao_id', $salaoId)->max('ordem');
        $total = 0;

        foreach ($request->file('fotos', []) as $arquivo) {
            $caminho = $arquivo->store('galeria/' . $salaoId, 'public');

            GaleriaFoto::create([
                'salao_id'    => $salaoId,
                'manicure_id' => $manicureId,
                'caminho'     => $caminho,
                'titulo'      => $titulo,
                'ordem'       => ++$ordemBase,
                'publicar'    => $publicar,
            ]);

            $total++;
        }

        return redirect()->route('dono.galeria.index')
            ->with('success', $total . ' foto(s) adicionada(s) à galeria!');
    }

    public function update(Request $request, GaleriaFoto $foto)
    {
        $this->autoriza($foto);

        $request->validate([
            'titulo'      => ['nullable', 'string', 'max:120'],
            'descricao'   => ['nullable', 'string', 'max:500'],
            'manicure_id' => ['nullable', 'integer', 'exists:manicures,id'],
            'publicar'    => ['sometimes', 'boolean'],
            'destaque'    => ['sometimes', 'boolean'],
        ]);

        $foto->update([
            'titulo'      => $request->input('titulo'),
            'descricao'   => $request->input('descricao'),
            'manicure_id' => $request->filled('manicure_id') ? (int) $request->manicure_id : null,
            'publicar'    => $request->boolean('publicar'),
            'destaque'    => $request->boolean('destaque'),
        ]);

        return back()->with('success', 'Foto atualizada!');
    }

    /**
     * Alterna a publicação da foto (botão rápido na grade).
     */
    public function togglePublicar(GaleriaFoto $foto)
    {
        $this->autoriza($foto);
        $foto->update(['publicar' => !$foto->publicar]);

        return back()->with('success', $foto->publicar ? 'Foto publicada.' : 'Foto ocultada.');
    }

    public function destroy(GaleriaFoto $foto)
    {
        $this->autoriza($foto);

        if ($foto->caminho && Storage::disk('public')->exists($foto->caminho)) {
            Storage::disk('public')->delete($foto->caminho);
        }

        $foto->delete();

        return back()->with('success', 'Foto removida da galeria.');
    }
}
