<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgendamentoResource;
use App\Models\Agendamento;
use App\Models\Manicure;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendamentoController extends Controller
{
    public function __construct(private AgendaService $agendaService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Agendamento::with(['manicure', 'servicos', 'salao', 'cliente']);

        if ($user->isCliente()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('cliente_id', $user->cliente?->id);
            });
        } elseif ($user->isManicure()) {
            $query->where('manicure_id', $user->manicure?->id);
        } elseif (!$user->isSuperAdmin()) {
            $query->where('salao_id', $user->salao_id);
        }

        $agendamentos = $query->orderByDesc('data_hora_inicio')->paginate(20);

        return AgendamentoResource::collection($agendamentos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'salao_id' => 'required|exists:saloes,id',
            'manicure_id' => 'required|exists:manicures,id',
            'servico_ids' => 'required|array|min:1',
            'servico_ids.*' => 'exists:servicos,id',
            'data_hora_inicio' => 'required|date|after:now',
        ]);

        try {
            $user = $request->user();
            $agendamento = $this->agendaService->criarAgendamento([
                'salao_id' => $request->salao_id,
                'manicure_id' => $request->manicure_id,
                'servico_ids' => $request->servico_ids,
                'data_hora_inicio' => $request->data_hora_inicio,
                'cliente_id' => $user->cliente?->id,
                'user_id' => $user->id,
                'nome_cliente' => $user->name,
                'observacoes' => $request->observacoes,
                'origem' => 'app',
            ]);

            return new AgendamentoResource($agendamento->load(['manicure', 'servicos', 'salao']));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Agendamento $agendamento)
    {
        return new AgendamentoResource($agendamento->load(['manicure', 'servicos', 'salao', 'cliente']));
    }

    public function slots(Request $request)
    {
        $request->validate([
            'manicure_id' => 'required|exists:manicures,id',
            'data' => 'required|date',
            'duracao' => 'required|integer|min:5',
        ]);

        $manicure = Manicure::with(['salao.configuracao', 'disponibilidades', 'folgas'])->findOrFail($request->manicure_id);
        $slots = $this->agendaService->getSlotsDisponiveis($manicure, Carbon::parse($request->data), (int) $request->duracao);

        return response()->json(['slots' => $slots]);
    }

    public function cancelar(Request $request, Agendamento $agendamento)
    {
        if (!$agendamento->podeSerCancelado()) {
            return response()->json(['message' => 'Agendamento não pode ser cancelado.'], 422);
        }

        $agendamento->update(['status' => 'cancelado']);

        return response()->json(['message' => 'Agendamento cancelado com sucesso.']);
    }
}
