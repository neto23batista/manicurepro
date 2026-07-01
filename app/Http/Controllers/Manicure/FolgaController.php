<?php

namespace App\Http\Controllers\Manicure;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFolgaManicureRequest;
use App\Models\FolgaManicure;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class FolgaController extends Controller
{
    public function index()
    {
        $manicure = auth()->user()->manicure;
        if (!$manicure) abort(403);

        $folgas = $manicure->folgas()
            ->where('data', '>=', today()->subDays(30))
            ->orderBy('data')
            ->paginate(20);

        return view('manicure.folgas.index', compact('folgas'));
    }

    public function store(StoreFolgaManicureRequest $request)
    {
        $manicure = auth()->user()->manicure;
        $data = $request->validated();

        $dataNormalizada = Carbon::parse($data['data'])->toDateString();
        $jaExiste = FolgaManicure::where('manicure_id', $manicure->id)
            ->whereDate('data', $dataNormalizada)
            ->exists();

        if ($jaExiste) {
            throw ValidationException::withMessages([
                'data' => 'Você já tem folga cadastrada para esta data.',
            ]);
        }

        $diaTodo = $request->boolean('dia_todo', true);

        FolgaManicure::create([
            'manicure_id' => $manicure->id,
            'data'        => $data['data'],
            'motivo'      => $data['motivo'] ?? null,
            'dia_todo'    => $diaTodo,
            'hora_inicio' => $diaTodo ? null : ($data['hora_inicio'] ?? null),
            'hora_fim'    => $diaTodo ? null : ($data['hora_fim'] ?? null),
        ]);

        return redirect()->route('manicure.folgas.index')
            ->with('success', 'Folga cadastrada!');
    }

    public function destroy(FolgaManicure $folga)
    {
        if ($folga->manicure_id !== auth()->user()->manicure?->id) abort(403);
        $folga->delete();
        return back()->with('success', 'Folga removida.');
    }
}
