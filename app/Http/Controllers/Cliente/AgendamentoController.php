<?php

namespace App\Http\Controllers\Cliente;

use App\Enums\AgendamentoStatus;
use App\Http\Controllers\Concerns\AuthorizesSalao;
use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\Salao;
use App\Services\AgendaService;
use Illuminate\Http\Request;

class AgendamentoController extends Controller
{
    use AuthorizesSalao;

    public function __construct(private AgendaService $agendaService) {}

    public function index()
    {
        $cliente = auth()->user()->cliente;

        $agendamentos = $cliente
            ? $cliente->agendamentos()
                ->with(['manicure', 'servicos', 'salao', 'avaliacao'])
                ->orderByDesc('data_hora_inicio')
                ->paginate(10)
            : collect();

        return view('cliente.agendamentos.index', compact('agendamentos'));
    }

    public function create(Request $request)
    {
        // Single-tenant: o agendamento é sempre no salão único.
        $salao = Salao::principal();
        $manicures = $salao ? $salao->manicures : collect();
        $servicos = $salao
            ? $salao->servicos()->where('disponivel_online', true)->get()
            : collect();

        return view('cliente.agendamentos.create', compact('salao', 'manicures', 'servicos'));
    }

    public function store(Request $request)
    {
        // O salão é definido pelo servidor (instalação de salão único).
        $request->merge(['salao_id' => Salao::principalId()]);

        $request->validate([
            'salao_id' => 'required|exists:saloes,id',
            'manicure_id' => 'required|exists:manicures,id',
            'servico_ids' => 'required|array|min:1',
            'servico_ids.*' => 'exists:servicos,id',
            'data_hora_inicio' => 'required|date|after:now',
            'observacoes' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $cliente = $user->cliente;

        // Cria registro Cliente se ainda não existir (associado ao salão escolhido)
        if (!$cliente) {
            $cliente = \App\Models\Cliente::create([
                'user_id' => $user->id,
                'salao_id' => $request->salao_id,
                'nome' => $user->name,
                'email' => $user->email,
                'telefone' => $user->phone,
            ]);
        }

        try {
            $agendamento = $this->agendaService->criarAgendamento([
                'salao_id' => $request->salao_id,
                'manicure_id' => $request->manicure_id,
                'servico_ids' => $request->servico_ids,
                'data_hora_inicio' => $request->data_hora_inicio,
                'cliente_id' => $cliente->id,
                'user_id' => $user->id,
                'nome_cliente' => $user->name,
                'telefone_cliente' => $user->phone,
                'observacoes' => $request->observacoes,
                'origem' => 'web',
                'status' => AgendamentoStatus::Aguardando->value,
            ]);
            // Notificações: enviadas pelo listener NotificarAgendamentoCriado.

            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->with('success', 'Agendamento realizado com sucesso! 💅');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Agendamento $agendamento)
    {
        $this->authorizeClienteAccess($agendamento);
        $agendamento->load(['manicure', 'servicos', 'salao', 'avaliacao', 'pagamentos']);
        return view('cliente.agendamentos.show', compact('agendamento'));
    }

    /**
     * Exporta o agendamento como arquivo iCalendar (.ics) para a agenda do cliente.
     */
    public function ical(Agendamento $agendamento, \App\Services\ICalService $ical)
    {
        $this->authorizeClienteAccess($agendamento);

        $conteudo = $ical->paraAgendamento($agendamento);

        return response($conteudo, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $ical->nomeArquivo($agendamento) . '"',
        ]);
    }

    public function reagendarForm(Agendamento $agendamento)
    {
        $this->authorizeClienteAccess($agendamento);

        if (!$agendamento->podeSerReagendado()) {
            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->withErrors(['error' => 'Este agendamento não pode ser remarcado.']);
        }

        $agendamento->load(['manicure', 'servicos', 'salao']);

        return view('cliente.agendamentos.reagendar', compact('agendamento'));
    }

    public function reagendar(Request $request, Agendamento $agendamento)
    {
        $this->authorizeClienteAccess($agendamento);

        if (!$agendamento->podeSerReagendado()) {
            return back()->withErrors(['error' => 'Este agendamento não pode ser remarcado.']);
        }

        $request->validate([
            'data_hora_inicio' => 'required|date|after:now',
        ]);

        try {
            $this->agendaService->reagendar($agendamento, \Carbon\Carbon::parse($request->data_hora_inicio));

            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->with('success', 'Agendamento remarcado com sucesso! 💅');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancelar(Request $request, Agendamento $agendamento)
    {
        $this->authorizeClienteAccess($agendamento);

        if (!$agendamento->podeSerCancelado()) {
            return back()->withErrors(['error' => 'Este agendamento não pode ser cancelado.']);
        }

        $agendamento->update(['status' => AgendamentoStatus::Cancelado->value]);

        \App\Events\AgendamentoCanceladoEvent::dispatch(
            $agendamento,
            'Cancelado a pedido do cliente.',
            'cliente'
        );

        unset($request);

        return redirect()->route('cliente.agendamentos.index')
            ->with('success', 'Agendamento cancelado com sucesso.');
    }

    public function sinal(Agendamento $agendamento)
    {
        $this->authorizeClienteAccess($agendamento);
        $mp = app(\App\Services\MercadoPagoService::class);

        if (!$mp->sinalHabilitado()) {
            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->withErrors(['error' => 'Pagamento de sinal não está disponível no momento.']);
        }

        if ($agendamento->sinalPago()) {
            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->with('success', 'O sinal deste agendamento já está pago. 💚');
        }

        $pix = $agendamento->mp_payment_id
            ? $mp->consultarPix($agendamento)
            : $mp->criarPixSinal($agendamento);

        if (($pix['status'] ?? null) === 'pago') {
            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->with('success', 'Pagamento confirmado! 💚');
        }

        $agendamento->load(['manicure', 'salao']);

        return view('cliente.agendamentos.sinal', compact('agendamento', 'pix'));
    }

    public function sinalStatus(Agendamento $agendamento)
    {
        $this->authorizeClienteAccess($agendamento);
        $mp = app(\App\Services\MercadoPagoService::class);

        $pix = $mp->consultarPix($agendamento);
        $pago = ($pix['status'] ?? null) === 'pago';

        return response()->json([
            'status'   => $pix['status'],
            'pago'     => $pago,
            'redirect' => $pago ? route('cliente.agendamentos.show', $agendamento) : null,
        ]);
    }

    public function avaliar(Request $request, Agendamento $agendamento)
    {
        $this->authorizeClienteAccess($agendamento);

        if ($agendamento->status !== AgendamentoStatus::Concluido->value) {
            return back()->withErrors(['error' => 'Só é possível avaliar agendamentos concluídos.']);
        }

        if ($agendamento->avaliacao) {
            return back()->withErrors(['error' => 'Você já avaliou este atendimento.']);
        }

        $request->validate([
            'nota' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500',
        ]);

        \App\Models\Avaliacao::create([
            'agendamento_id' => $agendamento->id,
            'cliente_id' => auth()->user()->cliente?->id,
            'manicure_id' => $agendamento->manicure_id,
            'salao_id' => $agendamento->salao_id,
            'nota' => $request->nota,
            'comentario' => $request->comentario,
        ]);

        return back()->with('success', 'Obrigada pela avaliação! 🌸');
    }

}
