<?php

namespace App\Http\Controllers;

use App\Models\Manicure;
use App\Models\Salao;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PublicController extends Controller
{
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
            $q->where('publicar', true)->orderByDesc('created_at')->take(10);
        }, 'galeria' => function ($q) {
            $q->where('publicar', true)->orderByDesc('destaque')->orderBy('ordem')->orderByDesc('id')->take(24);
        }]);

        $servicos = $salao->servicos()->where('disponivel_online', true)
            ->with('categoria')
            ->orderBy('nome')
            ->get()
            ->groupBy('categoria.nome');

        return view('public.salao', compact('salao', 'servicos'));
    }

    public function agendar(Salao $salao)
    {
        if (!$salao->configuracao?->permitir_agendamento_online) {
            return redirect()->route('public.salao', $salao->slug)
                ->with('info', 'Este salão não aceita agendamentos online no momento.');
        }

        $salao->load('manicures');

        $servicos = $salao->servicos()
            ->where('disponivel_online', true)
            ->orderBy('nome')
            ->get();

        return view('public.agendar', compact('salao', 'servicos'));
    }

    public function getSlots(Request $request)
    {
        $request->validate([
            'manicure_id' => 'required|exists:manicures,id',
            'data'        => 'required|date|after_or_equal:today',
            'duracao'     => 'required|integer|min:5',
        ]);

        $manicure = Manicure::with(['salao.configuracao', 'disponibilidades', 'folgas'])->findOrFail($request->manicure_id);
        $data = Carbon::parse($request->data);

        $slots = $this->agendaService->getSlotsDisponiveis($manicure, $data, (int) $request->duracao);

        return response()->json(['slots' => $slots]);
    }

    public function getDatasDisponiveis(Request $request)
    {
        $request->validate(['manicure_id' => 'required|exists:manicures,id']);
        $manicure = Manicure::with(['salao.configuracao', 'disponibilidades', 'folgas'])->findOrFail($request->manicure_id);
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

        $token = $request->input('token') ?: (string) \Illuminate\Support\Str::uuid();

        $hold = $this->agendaService->criarHold(
            (int) $request->manicure_id,
            Carbon::parse($request->data_hora_inicio),
            (int) $request->duracao,
            $token
        );

        if (!$hold) {
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
