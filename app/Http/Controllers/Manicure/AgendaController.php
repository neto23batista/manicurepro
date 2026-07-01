<?php

namespace App\Http\Controllers\Manicure;

use App\Enums\AgendamentoStatus;
use App\Http\Controllers\Concerns\AuthorizesSalao;
use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    use AuthorizesSalao;

    public function __construct(private AgendaService $agendaService) {}

    public function index(Request $request)
    {
        $manicure = auth()->user()->manicure;
        if (!$manicure) abort(403);

        $data = $request->data ? Carbon::parse($request->data) : today();

        $agendamentos = $manicure->agendamentos()
            ->whereDate('data_hora_inicio', $data)
            ->with(['servicos', 'cliente'])
            ->orderBy('data_hora_inicio')
            ->get();

        $semana = [];
        $inicioSemana = $data->copy()->startOfWeek(Carbon::SUNDAY);
        for ($i = 0; $i < 7; $i++) {
            $dia = $inicioSemana->copy()->addDays($i);
            $semana[] = [
                'data' => $dia,
                'total' => $manicure->agendamentos()
                    ->whereDate('data_hora_inicio', $dia->toDateString())
                    ->where('status', '!=', AgendamentoStatus::Cancelado->value)
                    ->count(),
            ];
        }

        return view('manicure.agenda.index', compact('manicure', 'agendamentos', 'data', 'semana'));
    }

    public function show(Agendamento $agendamento)
    {
        $this->authorizeManicureAccess($agendamento);
        $agendamento->load(['servicos', 'cliente', 'avaliacao']);
        return view('manicure.agenda.show', compact('agendamento'));
    }

    public function updateStatus(Request $request, Agendamento $agendamento)
    {
        $this->authorizeManicureAccess($agendamento);

        $request->validate(['status' => 'required|in:confirmado,em_andamento,concluido,nao_compareceu']);

        if ($request->status === AgendamentoStatus::Concluido->value) {
            $this->agendaService->finalizarAtendimento($agendamento, [
                'forma' => $request->forma_pagamento ?? 'dinheiro',
            ]);
        } else {
            $agendamento->update(['status' => $request->status]);
        }

        return back()->with('success', 'Status atualizado!');
    }
}
