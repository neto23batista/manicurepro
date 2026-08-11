<?php

namespace App\Http\Controllers\Manicure;

use App\Enums\AgendamentoStatus;
use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Services\AgendaService;
use App\Services\ICalService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function __construct(private AgendaService $agendaService) {}

    public function index(Request $request)
    {
        $manicure = auth()->user()->manicure;
        if (! $manicure) {
            abort(403);
        }

        $data = $request->data ? Carbon::parse($request->data) : today();

        $agendamentos = $manicure->agendamentos()
            ->whereDate('data_hora_inicio', $data)
            ->with(['servicos', 'cliente'])
            ->orderBy('data_hora_inicio')
            ->get();

        $semana = [];
        $inicioSemana = $data->copy()->startOfWeek(Carbon::SUNDAY);
        $fimSemana = $inicioSemana->copy()->addDays(6);

        $totais = $manicure->agendamentos()
            ->where('status', '!=', AgendamentoStatus::Cancelado->value)
            ->whereBetween('data_hora_inicio', [
                $inicioSemana->copy()->startOfDay(),
                $fimSemana->copy()->endOfDay(),
            ])
            ->get(['id', 'data_hora_inicio'])
            ->groupBy(fn ($a) => $a->data_hora_inicio->toDateString())
            ->map->count();

        $folgasSemana = $manicure->folgas()
            ->whereBetween('data', [$inicioSemana->toDateString(), $fimSemana->toDateString()])
            ->get()
            ->keyBy(fn ($f) => $f->data->toDateString());

        for ($i = 0; $i < 7; $i++) {
            $dia = $inicioSemana->copy()->addDays($i);
            $chave = $dia->toDateString();
            $semana[] = [
                'data'  => $dia,
                'total' => (int) ($totais[$chave] ?? 0),
                'folga' => $folgasSemana->get($chave),
            ];
        }

        return view('manicure.agenda.index', compact('manicure', 'agendamentos', 'data', 'semana'));
    }

    /**
     * Exporta a agenda da manicure em .ics (dia via ?data= ou intervalo ?de=&ate=).
     */
    public function ical(Request $request, ICalService $ical)
    {
        $manicure = auth()->user()->manicure;
        if (! $manicure) {
            abort(403);
        }

        $request->validate([
            'data' => 'nullable|date',
            'de'   => 'nullable|date',
            'ate'  => 'nullable|date|after_or_equal:de',
        ]);

        if ($request->filled('de')) {
            $inicio = Carbon::parse($request->de)->startOfDay();
            $fim = Carbon::parse($request->input('ate', $request->de))->endOfDay();
        } else {
            $dia = Carbon::parse($request->input('data', today()->toDateString()));
            $inicio = $dia->copy()->startOfDay();
            $fim = $dia->copy()->endOfDay();
        }

        if ($inicio->diffInDays($fim) > 31) {
            return back()->withErrors(['error' => 'O período máximo para exportar é de 31 dias.']);
        }

        $agendamentos = $manicure->agendamentos()
            ->with(['servicos', 'cliente', 'salao', 'manicure'])
            ->entre($inicio, $fim)
            ->orderBy('data_hora_inicio')
            ->get();

        $conteudo = $ical->paraAgendamentos($agendamentos);

        return response($conteudo, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$ical->nomeArquivoAgenda($inicio, $fim).'"',
        ]);
    }

    public function show(Agendamento $agendamento)
    {
        $this->authorize('view', $agendamento);
        $agendamento->load([
            'servicos',
            'cliente' => fn ($q) => $q->with(['fichaHistorico' => fn ($q) => $q->limit(5)]),
            'avaliacao',
        ]);

        return view('manicure.agenda.show', compact('agendamento'));
    }

    public function updateFicha(Request $request, Agendamento $agendamento)
    {
        $this->authorize('update', $agendamento);

        $cliente = $agendamento->cliente;
        if (! $cliente) {
            return back()->withErrors(['error' => 'Este agendamento não possui cliente cadastrado.']);
        }

        $data = $request->validate([
            'notas_unhas'      => ['nullable', 'string', 'max:2000'],
            'cores_preferidas' => ['nullable', 'string', 'max:500'],
            'contraindicacoes' => ['nullable', 'string', 'max:1000'],
            'ultima_formula'   => ['nullable', 'string', 'max:2000'],
            'registrar_visita' => ['nullable', 'boolean'],
            'notas_visita'     => ['nullable', 'string', 'max:2000'],
        ]);

        $cliente->update([
            'notas_unhas'      => $data['notas_unhas'] ?? null,
            'cores_preferidas' => $data['cores_preferidas'] ?? null,
            'contraindicacoes' => $data['contraindicacoes'] ?? null,
            'ultima_formula'   => $data['ultima_formula'] ?? null,
        ]);

        if ($request->boolean('registrar_visita')) {
            $notas = trim((string) ($data['notas_visita'] ?? ''));
            $cores = trim((string) ($data['cores_preferidas'] ?? ''));
            $formula = trim((string) ($data['ultima_formula'] ?? ''));

            if ($notas !== '' || $cores !== '' || $formula !== '') {
                $cliente->fichaHistorico()->create([
                    'salao_id'       => $cliente->salao_id,
                    'agendamento_id' => $agendamento->id,
                    'user_id'        => $request->user()->id,
                    'notas'          => $notas !== '' ? $notas : null,
                    'cores'          => $cores !== '' ? $cores : null,
                    'formula'        => $formula !== '' ? $formula : null,
                ]);
            }
        }

        return back()->with('success', 'Ficha de unhas atualizada.');
    }

    public function updateStatus(Request $request, Agendamento $agendamento)
    {
        $this->authorize('update', $agendamento);

        $request->validate(['status' => 'required|in:confirmado,em_andamento,concluido,nao_compareceu']);

        if ($request->status === AgendamentoStatus::Concluido->value) {
            $request->validate(['gorjeta' => 'nullable|numeric|min:0']);
            $this->agendaService->finalizarAtendimento($agendamento, [
                'forma'   => $request->forma_pagamento ?? 'dinheiro',
                'gorjeta' => $request->input('gorjeta'),
            ]);
        } else {
            $agendamento->update(['status' => $request->status]);
        }

        $mensagens = [
            AgendamentoStatus::Confirmado->value    => 'Agendamento confirmado.',
            AgendamentoStatus::EmAndamento->value   => 'Atendimento iniciado.',
            AgendamentoStatus::Concluido->value     => 'Atendimento finalizado com sucesso.',
            AgendamentoStatus::NaoCompareceu->value => 'Marcado como não compareceu.',
        ];

        return back()->with('success', $mensagens[$request->status] ?? 'Status atualizado!');
    }
}
