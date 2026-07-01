<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\ListaEspera;
use App\Models\Salao;
use Illuminate\Http\Request;

class ListaEsperaController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $entradas = ListaEspera::with(['salao', 'manicure'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $saloes = Salao::where('ativo', true)->orderBy('nome')->get();

        return view('cliente.lista-espera.index', compact('entradas', 'saloes'));
    }

    public function store(Request $request)
    {
        // Single-tenant: o salão é sempre o principal, definido no servidor.
        $request->merge(['salao_id' => Salao::principalId()]);

        $request->validate([
            'salao_id'       => 'required|exists:saloes,id',
            'manicure_id'    => 'nullable|exists:manicures,id',
            'data_preferida' => 'nullable|date|after_or_equal:today',
            'periodo'        => 'nullable|in:manha,tarde,noite,qualquer',
        ]);

        $user = auth()->user();

        $existe = ListaEspera::where('user_id', $user->id)
            ->where('salao_id', $request->salao_id)
            ->where('status', 'aguardando')
            ->whereDate('data_preferida', $request->data_preferida ?: null)
            ->exists();

        if ($existe) {
            return back()->withErrors(['error' => 'Você já está na lista de espera para esse salão/data.']);
        }

        ListaEspera::create([
            'salao_id'       => $request->salao_id,
            'manicure_id'    => $request->manicure_id,
            'cliente_id'     => $user->cliente?->id,
            'user_id'        => $user->id,
            'data_preferida' => $request->data_preferida,
            'periodo'        => $request->periodo ?: 'qualquer',
            'status'         => 'aguardando',
        ]);

        return back()->with('success', 'Você entrou na lista de espera! Avisaremos quando abrir vaga. 🌸');
    }

    public function destroy(ListaEspera $lista)
    {
        abort_unless($lista->user_id === auth()->id(), 403);

        $lista->delete();

        return back()->with('success', 'Você saiu da lista de espera.');
    }
}
