<?php

namespace App\Http\Controllers\Cliente;

use App\Enums\AgendamentoStatus;
use App\Events\AgendamentoCanceladoEvent;
use App\Http\Controllers\Concerns\HandlesDomainExceptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgendamentoRequest;
use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\Salao;
use App\Services\AgendaService;
use App\Services\ICalService;
use App\Services\MercadoPagoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendamentoController extends Controller
{
    use HandlesDomainExceptions;

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
            ? $salao->servicos()->where('disponivel_online', true)->with('variacoesAtivas')->get()
            : collect();

        return view('cliente.agendamentos.create', compact('salao', 'manicures', 'servicos'));
    }

    public function store(StoreAgendamentoRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();
        $cliente = $user->cliente;

        // Cria registro Cliente se ainda não existir (associado ao salão escolhido)
        if (! $cliente) {
            $cliente = Cliente::create([
                'user_id'  => $user->id,
                'salao_id' => $validated['salao_id'],
                'nome'     => $user->name,
                'email'    => $user->email,
                'telefone' => $user->phone,
            ]);
        }

        try {
            $agendamento = $this->agendaService->criarAgendamento([
                'salao_id'          => $validated['salao_id'],
                'manicure_id'       => $validated['manicure_id'],
                'servico_ids'       => $validated['servico_ids'],
                'servico_variacoes' => $validated['servico_variacoes'] ?? [],
                'data_hora_inicio'  => $validated['data_hora_inicio'],
                'cliente_id'        => $cliente->id,
                'user_id'           => $user->id,
                'nome_cliente'      => $user->name,
                'telefone_cliente'  => $user->phone,
                'observacoes'       => $validated['observacoes'] ?? null,
                'origem'            => 'web',
                'status'            => AgendamentoStatus::Aguardando->value,
            ]);
            // Notificações: enviadas pelo listener NotificarAgendamentoCriado.

            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->with('success', 'Agendamento realizado com sucesso! 💅');
        } catch (\Throwable $e) {
            return $this->domainExceptionBack($e, 'Não foi possível criar o agendamento.', withInput: true);
        }
    }

    public function show(Agendamento $agendamento, ICalService $ical)
    {
        $this->authorize('view', $agendamento);
        $agendamento->load(['manicure', 'servicos', 'salao', 'avaliacao', 'pagamentos']);
        $googleCalendarUrl = $ical->linkGoogleCalendar($agendamento);

        return view('cliente.agendamentos.show', compact('agendamento', 'googleCalendarUrl'));
    }

    /**
     * Exporta o agendamento como arquivo iCalendar (.ics) para a agenda do cliente.
     */
    public function ical(Agendamento $agendamento, ICalService $ical)
    {
        $this->authorize('view', $agendamento);

        $conteudo = $ical->paraAgendamento($agendamento);

        return response($conteudo, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$ical->nomeArquivo($agendamento).'"',
        ]);
    }

    public function reagendarForm(Agendamento $agendamento)
    {
        $this->authorize('view', $agendamento);

        if (! $agendamento->podeSerReagendado()) {
            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->withErrors(['error' => 'Este agendamento não pode ser remarcado.']);
        }

        $agendamento->load(['manicure', 'servicos', 'salao']);

        return view('cliente.agendamentos.reagendar', compact('agendamento'));
    }

    public function reagendar(Request $request, Agendamento $agendamento)
    {
        $this->authorize('view', $agendamento);

        if (! $agendamento->podeSerReagendado()) {
            return back()->withErrors(['error' => 'Este agendamento não pode ser remarcado.']);
        }

        $request->validate([
            'data_hora_inicio' => 'required|date|after:now',
        ]);

        try {
            $this->agendaService->reagendar($agendamento, Carbon::parse($request->data_hora_inicio));

            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->with('success', 'Agendamento remarcado com sucesso! 💅');
        } catch (\Throwable $e) {
            return $this->domainExceptionBack($e, 'Não foi possível remarcar o agendamento.', withInput: true);
        }
    }

    public function cancelar(Request $request, Agendamento $agendamento)
    {
        $this->authorize('cancel', $agendamento);

        if (! $agendamento->podeSerCancelado()) {
            return back()->withErrors(['error' => 'Este agendamento não pode ser cancelado.']);
        }

        $agendamento->update(['status' => AgendamentoStatus::Cancelado->value]);

        AgendamentoCanceladoEvent::dispatch(
            $agendamento,
            'Cancelado a pedido do cliente.',
            'cliente',
        );

        unset($request);

        return redirect()->route('cliente.agendamentos.index')
            ->with('success', 'Agendamento cancelado com sucesso.');
    }

    public function sinal(Agendamento $agendamento)
    {
        $this->authorize('view', $agendamento);
        $mp = app(MercadoPagoService::class);

        if (! $mp->sinalHabilitado()) {
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
        $this->authorize('view', $agendamento);
        $mp = app(MercadoPagoService::class);

        $pix = $mp->consultarPix($agendamento);
        $pago = ($pix['status'] ?? null) === 'pago';

        return response()->json([
            'status'   => $pix['status'],
            'pago'     => $pago,
            'redirect' => $pago ? route('cliente.agendamentos.show', $agendamento) : null,
        ]);
    }

    public function pagamento(Agendamento $agendamento)
    {
        $this->authorize('view', $agendamento);
        $mp = app(MercadoPagoService::class);

        if (! $mp->totalHabilitado()) {
            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->withErrors(['error' => 'Pagamento Pix do valor total não está disponível no momento.']);
        }

        if ($agendamento->pagamentoTotalPago()) {
            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->with('success', 'O pagamento deste agendamento já está confirmado. 💚');
        }

        if (! $agendamento->precisaPagamentoTotal() && ! $agendamento->mp_payment_id) {
            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->withErrors(['error' => 'Não há valor pendente para pagar neste agendamento.']);
        }

        $pix = ($agendamento->mp_cobranca_tipo === 'total' && $agendamento->mp_payment_id)
            ? $mp->consultarPix($agendamento)
            : $mp->criarPixTotal($agendamento);

        if (($pix['status'] ?? null) === 'pago') {
            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->with('success', 'Pagamento confirmado! 💚');
        }

        $agendamento->load(['manicure', 'salao']);

        return view('cliente.agendamentos.pagamento', compact('agendamento', 'pix'));
    }

    public function pagamentoStatus(Agendamento $agendamento)
    {
        $this->authorize('view', $agendamento);
        $mp = app(MercadoPagoService::class);

        $pix = $mp->consultarPix($agendamento);
        $pago = ($pix['status'] ?? null) === 'pago';

        return response()->json([
            'status'   => $pix['status'],
            'pago'     => $pago,
            'redirect' => $pago ? route('cliente.agendamentos.show', $agendamento) : null,
        ]);
    }

    public function gorjeta(Request $request, Agendamento $agendamento)
    {
        $this->authorize('view', $agendamento);
        $mp = app(MercadoPagoService::class);

        if (! $mp->gorjetaHabilitado()) {
            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->withErrors(['error' => 'Gorjeta via Pix não está disponível no momento.']);
        }

        if ($agendamento->gorjetaOnlinePaga()) {
            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->with('success', 'A gorjeta deste agendamento já foi paga. 💚');
        }

        if (! $agendamento->precisaGorjetaOnline()
            && ! ($agendamento->mp_cobranca_tipo === 'gorjeta' && $agendamento->mp_payment_id)) {
            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->withErrors(['error' => 'Gorjeta Pix só está disponível após a conclusão do atendimento.']);
        }

        $agendamento->load(['manicure', 'salao']);

        if ($agendamento->mp_cobranca_tipo === 'gorjeta' && $agendamento->mp_payment_id) {
            $pix = $mp->consultarPix($agendamento);

            if (($pix['status'] ?? null) === 'pago') {
                return redirect()->route('cliente.agendamentos.show', $agendamento)
                    ->with('success', 'Gorjeta confirmada! Obrigada 💚');
            }

            return view('cliente.agendamentos.gorjeta', compact('agendamento', 'pix'));
        }

        if ($request->isMethod('get') && ! $request->filled('valor')) {
            return view('cliente.agendamentos.gorjeta', ['agendamento' => $agendamento, 'pix' => null]);
        }

        $data = $request->validate([
            'valor' => 'required|numeric|min:1|max:9999',
        ], [
            'valor.required' => 'Informe o valor da gorjeta.',
            'valor.min'      => 'A gorjeta mínima é R$ 1,00.',
        ]);

        $pix = $mp->criarPixGorjeta($agendamento, (float) $data['valor']);

        if (($pix['status'] ?? null) === 'pago') {
            return redirect()->route('cliente.agendamentos.show', $agendamento)
                ->with('success', 'Gorjeta confirmada! Obrigada 💚');
        }

        return view('cliente.agendamentos.gorjeta', compact('agendamento', 'pix'));
    }

    public function gorjetaStatus(Agendamento $agendamento)
    {
        $this->authorize('view', $agendamento);
        $mp = app(MercadoPagoService::class);

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
        $this->authorize('review', $agendamento);

        if ($agendamento->status !== AgendamentoStatus::Concluido->value) {
            return back()->withErrors(['error' => 'Só é possível avaliar agendamentos concluídos.']);
        }

        if ($agendamento->avaliacao) {
            return back()->withErrors(['error' => 'Você já avaliou este atendimento.']);
        }

        $request->validate([
            'nota'       => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500',
        ]);

        Avaliacao::create([
            'agendamento_id' => $agendamento->id,
            'cliente_id'     => auth()->user()->cliente?->id,
            'manicure_id'    => $agendamento->manicure_id,
            'salao_id'       => $agendamento->salao_id,
            'nota'           => $request->nota,
            'comentario'     => $request->comentario,
        ]);

        return back()->with('success', 'Obrigada pela avaliação! 🌸');
    }
}
