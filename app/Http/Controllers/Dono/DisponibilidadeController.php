<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDisponibilidadeManicureRequest;
use App\Models\DisponibilidadeManicure;
use App\Models\HorarioFuncionamento;
use App\Models\Manicure;

class DisponibilidadeController extends Controller
{
    public function index()
    {
        $salao = auth()->user()->salao;
        abort_unless($salao !== null, 404);

        $manicures = $salao->todasManicures()
            ->where('ativo', true)
            ->with('disponibilidades')
            ->orderBy('nome')
            ->get();

        $dias = HorarioFuncionamento::DIAS;

        return view('dono.disponibilidades.index', compact('manicures', 'dias'));
    }

    public function update(UpdateDisponibilidadeManicureRequest $request, Manicure $manicure)
    {
        $salao = auth()->user()->salao;
        abort_unless($salao && $manicure->salao_id === $salao->id, 403);

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
                    'hora_inicio'  => $ativo ? ($dados['hora_inicio'] ?? '09:00') : ($dados['hora_inicio'] ?? '09:00'),
                    'hora_fim'     => $ativo ? ($dados['hora_fim'] ?? '18:00') : ($dados['hora_fim'] ?? '18:00'),
                    'pausa_inicio' => ($ativo && $pausaIni && $pausaFim) ? $pausaIni : null,
                    'pausa_fim'    => ($ativo && $pausaIni && $pausaFim) ? $pausaFim : null,
                ],
            );
        }

        return back()->with('success', 'Disponibilidade de '.$manicure->nome.' atualizada!');
    }
}
