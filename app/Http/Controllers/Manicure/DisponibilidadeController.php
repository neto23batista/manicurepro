<?php

namespace App\Http\Controllers\Manicure;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDisponibilidadeManicureRequest;
use App\Models\DisponibilidadeManicure;
use App\Models\HorarioFuncionamento;

class DisponibilidadeController extends Controller
{
    public function edit()
    {
        $manicure = auth()->user()->manicure;
        abort_unless($manicure !== null, 403);

        $manicure->load('disponibilidades');
        $disp = $manicure->disponibilidades->keyBy('dia_semana');
        $dias = HorarioFuncionamento::DIAS;

        return view('manicure.disponibilidade.edit', compact('manicure', 'disp', 'dias'));
    }

    public function update(UpdateDisponibilidadeManicureRequest $request)
    {
        $manicure = auth()->user()->manicure;
        abort_unless($manicure !== null, 403);

        foreach ((array) $request->input('dias', []) as $diaSemana => $dados) {
            $diaSemana = (int) $diaSemana;
            if ($diaSemana < 0 || $diaSemana > 6) {
                continue;
            }

            $ativo = filter_var($dados['ativo'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $pausaIni = $dados['pausa_inicio'] ?? null;
            $pausaFim = $dados['pausa_fim'] ?? null;

            DisponibilidadeManicure::updateOrCreate(
                ['manicure_id' => $manicure->id, 'dia_semana' => $diaSemana],
                [
                    'ativo'        => $ativo,
                    'hora_inicio'  => $dados['hora_inicio'] ?? '09:00',
                    'hora_fim'     => $dados['hora_fim'] ?? '18:00',
                    'pausa_inicio' => ($ativo && $pausaIni && $pausaFim) ? $pausaIni : null,
                    'pausa_fim'    => ($ativo && $pausaIni && $pausaFim) ? $pausaFim : null,
                ],
            );
        }

        return back()->with('success', 'Sua disponibilidade foi atualizada!');
    }
}
