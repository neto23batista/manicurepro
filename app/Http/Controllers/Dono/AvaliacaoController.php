<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Models\Avaliacao;
use App\Models\Salao;

class AvaliacaoController extends Controller
{
    private function salaoId(): int
    {
        return (int) (auth()->user()->salao_id ?? Salao::principalId());
    }

    public function index()
    {
        $this->authorize('viewAny', Avaliacao::class);

        $avaliacoes = Avaliacao::query()
            ->where('salao_id', $this->salaoId())
            ->with(['cliente', 'manicure', 'agendamento'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('dono.avaliacoes.index', compact('avaliacoes'));
    }

    public function togglePublicar(Avaliacao $avaliacao)
    {
        $this->authorize('update', $avaliacao);

        $avaliacao->update(['publicar' => ! $avaliacao->publicar]);

        return back()->with(
            'success',
            $avaliacao->publicar ? 'Avaliação publicada no site.' : 'Avaliação ocultada do site.',
        );
    }
}
