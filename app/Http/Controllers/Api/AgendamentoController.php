<?php

namespace App\Http\Controllers\Api;

use App\Enums\AgendamentoStatus;
use App\Events\AgendamentoCanceladoEvent;
use App\Http\Controllers\Concerns\HandlesDomainExceptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgendamentoRequest;
use App\Http\Resources\AgendamentoResource;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Manicure;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendamentoController extends Controller
{
    use HandlesDomainExceptions;

    public function __construct(private AgendaService $agendaService) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status'      => ['sometimes', 'nullable', 'string', 'in:'.implode(',', array_column(AgendamentoStatus::cases(), 'value'))],
            'manicure_id' => ['sometimes', 'nullable', 'integer', 'exists:manicures,id'],
            'de'          => ['sometimes', 'nullable', 'date'],
            'ate'         => ['sometimes', 'nullable', 'date', 'after_or_equal:de'],
            'per_page'    => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page'        => ['sometimes', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        $query = Agendamento::with(['manicure', 'servicos', 'salao', 'cliente']);

        if ($user->isCliente()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('cliente_id', $user->cliente?->id);
            });
        } elseif ($user->isManicure()) {
            $query->where('manicure_id', $user->manicure?->id);
        } elseif (! $user->isSuperAdmin()) {
            $query->where('salao_id', $user->salao_id);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['manicure_id'])) {
            $query->where('manicure_id', (int) $validated['manicure_id']);
        }

        if (! empty($validated['de'])) {
            $query->where('data_hora_inicio', '>=', Carbon::parse($validated['de'])->startOfDay());
        }

        if (! empty($validated['ate'])) {
            $query->where('data_hora_inicio', '<=', Carbon::parse($validated['ate'])->endOfDay());
        }

        $perPage = (int) ($validated['per_page'] ?? 20);

        $agendamentos = $query->orderByDesc('data_hora_inicio')->paginate($perPage);

        return AgendamentoResource::collection($agendamentos);
    }

    public function store(StoreAgendamentoRequest $request)
    {
        $validated = $request->validated();

        try {
            $user = $request->user();
            $agendamento = $this->agendaService->criarAgendamento([
                'salao_id'         => $validated['salao_id'],
                'manicure_id'      => $validated['manicure_id'],
                'servico_ids'      => $validated['servico_ids'],
                'servico_variacoes'=> $validated['servico_variacoes'] ?? [],
                'data_hora_inicio' => $validated['data_hora_inicio'],
                'cliente_id'       => $user->cliente?->id,
                'user_id'          => $user->id,
                'nome_cliente'     => $user->name,
                'observacoes'      => $validated['observacoes'] ?? null,
                'origem'           => 'app',
            ]);

            return new AgendamentoResource($agendamento->load(['manicure', 'servicos', 'salao']));
        } catch (\Throwable $e) {
            return $this->domainExceptionJson($e, 'Não foi possível criar o agendamento.');
        }
    }

    public function show(Agendamento $agendamento)
    {
        $this->authorize('view', $agendamento);

        return new AgendamentoResource($agendamento->load(['manicure', 'servicos', 'salao', 'cliente']));
    }

    public function slots(Request $request)
    {
        $request->validate([
            'manicure_id' => 'required|exists:manicures,id',
            'data'        => 'required|date',
            'duracao'     => 'required|integer|min:5',
        ]);

        $manicure = Manicure::with(['salao.configuracao', 'salao.horarios', 'disponibilidades', 'folgas'])->findOrFail($request->manicure_id);
        $slots = $this->agendaService->getSlotsDisponiveis($manicure, Carbon::parse($request->data), (int) $request->duracao);

        return response()->json(['slots' => $slots]);
    }

    public function cancelar(Request $request, Agendamento $agendamento)
    {
        $this->authorize('cancel', $agendamento);

        if (! $agendamento->podeSerCancelado()) {
            return \App\Support\ApiError::make('Agendamento não pode ser cancelado.', 422, 'cannot_cancel');
        }

        $agendamento->update(['status' => AgendamentoStatus::Cancelado->value]);

        AgendamentoCanceladoEvent::dispatch(
            $agendamento,
            'Cancelado via API.',
            $request->user()->role ?? 'api',
        );

        return (new AgendamentoResource(
            $agendamento->fresh()->load(['manicure', 'servicos', 'salao']),
        ))->additional([
            'message' => 'Agendamento cancelado com sucesso.',
        ]);
    }

    public function avaliar(Request $request, Agendamento $agendamento)
    {
        $this->authorize('view', $agendamento);

        if ($agendamento->status !== AgendamentoStatus::Concluido->value) {
            return \App\Support\ApiError::make('Só é possível avaliar agendamentos concluídos.', 422, 'cannot_review');
        }

        if ($agendamento->avaliacao) {
            return \App\Support\ApiError::make('Você já avaliou este atendimento.', 422, 'already_reviewed');
        }

        $request->validate([
            'nota'       => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500',
        ]);

        $avaliacao = Avaliacao::create([
            'agendamento_id' => $agendamento->id,
            'cliente_id'     => $request->user()->cliente?->id,
            'manicure_id'    => $agendamento->manicure_id,
            'salao_id'       => $agendamento->salao_id,
            'nota'           => $request->nota,
            'comentario'     => $request->comentario,
        ]);

        return response()->json([
            'message'   => 'Avaliação registrada com sucesso.',
            'avaliacao' => [
                'id'         => $avaliacao->id,
                'nota'       => $avaliacao->nota,
                'comentario' => $avaliacao->comentario,
            ],
        ], 201);
    }
}
