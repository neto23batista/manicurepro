<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeriadoRequest;
use App\Http\Requests\StoreFolgaRequest;
use App\Models\Feriado;
use App\Models\Folga;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class FolgaController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Folga::class);

        $salao = auth()->user()->salao;
        $folgas = $salao->folgas()
            ->where('data', '>=', today()->subDays(30))
            ->orderBy('data')
            ->paginate(20, ['*'], 'folgas_page');

        $feriados = $salao->feriados()
            ->orderBy('mes')
            ->orderBy('dia')
            ->get();

        return view('dono.folgas.index', compact('folgas', 'feriados'));
    }

    public function store(StoreFolgaRequest $request)
    {
        $this->authorize('create', Folga::class);

        $salao = auth()->user()->salao;
        $data = $request->validated();

        $dataNormalizada = Carbon::parse($data['data'])->toDateString();
        $jaExiste = Folga::where('salao_id', $salao->id)
            ->whereDate('data', $dataNormalizada)
            ->exists();

        if ($jaExiste) {
            throw ValidationException::withMessages([
                'data' => 'Já existe folga cadastrada para esta data.',
            ]);
        }

        $diaTodo = $request->boolean('dia_todo', true);

        Folga::create([
            'salao_id'    => $salao->id,
            'data'        => $data['data'],
            'motivo'      => $data['motivo'] ?? null,
            'dia_todo'    => $diaTodo,
            'hora_inicio' => $diaTodo ? null : ($data['hora_inicio'] ?? null),
            'hora_fim'    => $diaTodo ? null : ($data['hora_fim'] ?? null),
        ]);

        return redirect()->route('dono.folgas.index')
            ->with('success', 'Folga cadastrada!');
    }

    public function destroy(Folga $folga)
    {
        $this->authorize('delete', $folga);
        $folga->delete();

        return back()->with('success', 'Folga removida.');
    }

    public function storeFeriado(StoreFeriadoRequest $request)
    {
        $this->authorize('create', Feriado::class);

        $salao = auth()->user()->salao;
        $data = $request->validated();

        $jaExiste = Feriado::where('salao_id', $salao->id)
            ->where('mes', (int) $data['mes'])
            ->where('dia', (int) $data['dia'])
            ->exists();

        if ($jaExiste) {
            throw ValidationException::withMessages([
                'dia' => 'Já existe feriado cadastrado para esta data (recorrente anual).',
            ]);
        }

        $diaTodo = $request->boolean('dia_todo', true);

        Feriado::create([
            'salao_id'    => $salao->id,
            'nome'        => $data['nome'],
            'mes'         => (int) $data['mes'],
            'dia'         => (int) $data['dia'],
            'dia_todo'    => $diaTodo,
            'hora_inicio' => $diaTodo ? null : ($data['hora_inicio'] ?? null),
            'hora_fim'    => $diaTodo ? null : ($data['hora_fim'] ?? null),
            'ativo'       => true,
        ]);

        return redirect()->route('dono.folgas.index')
            ->with('success', 'Feriado recorrente cadastrado!');
    }

    public function destroyFeriado(Feriado $feriado)
    {
        $this->authorize('delete', $feriado);
        $feriado->delete();

        return back()->with('success', 'Feriado removido.');
    }
}
