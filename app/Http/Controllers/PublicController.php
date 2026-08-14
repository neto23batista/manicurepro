<?php

namespace App\Http\Controllers;

use App\Enums\AgendamentoStatus;
use App\Http\Controllers\Concerns\HandlesDomainExceptions;
use App\Http\Requests\StoreGuestAgendamentoRequest;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Salao;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    use HandlesDomainExceptions;

    public function __construct(private AgendaService $agendaService) {}

    /**
     * Home pública: instalação single-tenant — a home é a própria página do salão.
     */
    public function index()
    {
        $salao = Salao::principal();
        abort_if($salao === null, 404, 'Nenhum salão configurado.');

        return $this->renderSalao($salao);
    }

    public function salao(Salao $salao)
    {
        return $this->renderSalao($salao);
    }

    private function renderSalao(Salao $salao)
    {
        $salao->load(['manicures', 'servicos.categoria', 'horarios', 'avaliacoes' => function ($q) {
            $q->publicadas()->with('agendamento')->orderByDesc('created_at')->take(10);
        }, 'galeria' => function ($q) {
            $q->where('publicar', true)->orderByDesc('destaque')->orderBy('ordem')->orderByDesc('id')->take(24);
        }]);

        $servicos = $salao->servicos()->where('disponivel_online', true)
            ->with(['categoria', 'variacoesAtivas'])
            ->orderBy('nome')
            ->get()
            ->groupBy('categoria.nome');

        return view('public.salao', compact('salao', 'servicos'));
    }

    public function agendar(Salao $salao)
    {
        if (! $salao->configuracao?->permitir_agendamento_online) {
            return redirect()->route('public.salao', $salao->slug)
                ->with('info', 'Este salão não aceita agendamentos online no momento.');
        }

        $salao->load('manicures');

        $servicos = $salao->servicos()
            ->where('disponivel_online', true)
            ->with('variacoesAtivas')
            ->orderBy('nome')
            ->get();

        return view('public.agendar', compact('salao', 'servicos'));
    }

    /**
     * Agendamento guest (sem conta): nome + telefone (+ e-mail opcional).
     */
    public function storeAgendamento(StoreGuestAgendamentoRequest $request, Salao $salao)
    {
        if (! $salao->configuracao?->permitir_agendamento_online) {
            return redirect()->route('public.salao', $salao->slug)
                ->with('info', 'Este salão não aceita agendamentos online no momento.');
        }

        // Visitantes autenticados usam o fluxo do painel do cliente
        if (auth()->check()) {
            return redirect()->route('cliente.agendamentos.create');
        }

        $telefone = $request->telefoneFormatado();
        $cliente = Cliente::findOrCreateGuest(
            $salao->id,
            $request->string('nome')->toString(),
            $telefone,
            $request->filled('email') ? $request->string('email')->toString() : null,
        );

        try {
            $agendamento = $this->agendaService->criarAgendamento([
                'salao_id'         => $salao->id,
                'manicure_id'      => $request->integer('manicure_id'),
                'servico_ids'      => $request->input('servico_ids'),
                'servico_variacoes'=> $request->input('servico_variacoes', []),
                'data_hora_inicio' => $request->input('data_hora_inicio'),
                'cliente_id'       => $cliente->id,
                'nome_cliente'     => $cliente->nome,
                'telefone_cliente' => $cliente->telefone,
                'observacoes'      => $request->input('observacoes'),
                'origem'           => 'guest',
                'status'           => AgendamentoStatus::Aguardando->value,
            ]);
        } catch (\Throwable $e) {
            return $this->domainExceptionBack($e, 'Não foi possível criar o agendamento.', withInput: true);
        }

        return redirect()
            ->route('public.agendar.sucesso', $salao)
            ->with('agendamento_id', $agendamento->id);
    }

    /**
     * Página de sucesso após booking guest (dados via session flash).
     */
    public function agendamentoSucesso(Salao $salao)
    {
        $agendamentoId = session('agendamento_id');
        abort_unless($agendamentoId, 404);

        $agendamento = Agendamento::with(['manicure', 'servicos', 'salao', 'cliente'])
            ->where('salao_id', $salao->id)
            ->findOrFail($agendamentoId);

        $linkConfirmacao = URL::temporarySignedRoute(
            'agendamento.confirmar',
            $agendamento->data_hora_inicio->copy()->addDay(),
            ['agendamento' => $agendamento->id],
        );

        return view('public.agendamento-sucesso', compact('salao', 'agendamento', 'linkConfirmacao'));
    }

    public function getSlots(Request $request)
    {
        $request->validate([
            'manicure_id' => 'required|exists:manicures,id',
            'data'        => 'required|date|after_or_equal:today',
            'duracao'     => 'required|integer|min:5',
        ]);

        $manicure = Manicure::with(['salao.configuracao', 'salao.horarios', 'salao.folgas', 'disponibilidades', 'folgas'])->findOrFail($request->manicure_id);
        $data = Carbon::parse($request->data);

        $slots = $this->agendaService->getSlotsDisponiveis($manicure, $data, (int) $request->duracao);

        return response()->json(['slots' => $slots]);
    }

    public function getDatasDisponiveis(Request $request)
    {
        $request->validate(['manicure_id' => 'required|exists:manicures,id']);
        $manicure = Manicure::with(['salao.configuracao', 'salao.horarios', 'salao.folgas', 'disponibilidades', 'folgas'])->findOrFail($request->manicure_id);
        $datas = $this->agendaService->getDatasDisponiveis($manicure);

        return response()->json(['datas' => $datas]);
    }

    /**
     * Reserva temporária do slot enquanto o cliente finaliza o agendamento.
     */
    public function holdSlot(Request $request)
    {
        $request->validate([
            'manicure_id'      => 'required|exists:manicures,id',
            'data_hora_inicio' => 'required|date|after:now',
            'duracao'          => 'required|integer|min:5',
        ]);

        $token = $request->input('token') ?: (string) Str::uuid();

        $hold = $this->agendaService->criarHold(
            (int) $request->manicure_id,
            Carbon::parse($request->data_hora_inicio),
            (int) $request->duracao,
            $token,
        );

        if (! $hold) {
            return response()->json([
                'ok'  => false,
                'msg' => 'Esse horário acabou de ser reservado por outra pessoa.',
            ], 409);
        }

        return response()->json([
            'ok'        => true,
            'token'     => $token,
            'expira_em' => $hold->expires_at->toIso8601String(),
        ]);
    }
}
