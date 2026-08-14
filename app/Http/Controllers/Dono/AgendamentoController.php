<?php

namespace App\Http\Controllers\Dono;

use App\Enums\AgendamentoStatus;
use App\Enums\FormaPagamento;
use App\Events\AgendamentoCanceladoEvent;
use App\Http\Controllers\Concerns\AuthorizesSalao;
use App\Http\Controllers\Concerns\HandlesDomainExceptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgendamentoRequest;
use App\Models\Agendamento;
use App\Models\ComandaItem;
use App\Models\Produto;
use App\Models\ValePresente;
use App\Services\AgendaService;
use App\Services\AuditLogger;
use App\Services\ComandaService;
use App\Services\ICalService;
use App\Services\MercadoPagoService;
use App\Services\ValePresenteService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendamentoController extends Controller
{
    use AuthorizesSalao;
    use HandlesDomainExceptions;

    public function __construct(private AgendaService $agendaService) {}

    public function index(Request $request)
    {
        $salao = auth()->user()->salao;
        $status = $request->status;
        $data = $request->data ? Carbon::parse($request->data) : null;
        $manicureId = $request->manicure_id;

        $agendamentos = $salao->agendamentos()
            ->with(['manicure', 'servicos', 'cliente'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($data, fn ($q) => $q->whereDate('data_hora_inicio', $data))
            ->when($manicureId, fn ($q) => $q->where('manicure_id', $manicureId))
            ->orderByDesc('data_hora_inicio')
            ->paginate(20);

        $manicures = $salao->manicures;

        return view('dono.agendamentos.index', compact('agendamentos', 'manicures', 'status', 'manicureId'));
    }

    /**
     * Visão semanal do salão (grade simples por manicure × dia).
     */
    public function semana(Request $request)
    {
        $salao = auth()->user()->salao;
        $ref = $request->data ? Carbon::parse($request->data) : today();
        $inicio = $ref->copy()->startOfWeek(Carbon::SUNDAY);
        $fim = $inicio->copy()->addDays(6);

        $manicureId = $request->manicure_id;
        $manicures = $salao->manicures()
            ->when($manicureId, fn ($q) => $q->where('id', $manicureId))
            ->orderBy('nome')
            ->get();

        $agendamentos = $salao->agendamentos()
            ->with(['manicure', 'servicos', 'cliente'])
            ->ativos()
            ->entre($inicio->copy()->startOfDay(), $fim->copy()->endOfDay())
            ->when($manicureId, fn ($q) => $q->where('manicure_id', $manicureId))
            ->orderBy('data_hora_inicio')
            ->get()
            ->groupBy(fn ($a) => $a->manicure_id.'|'.$a->data_hora_inicio->toDateString());

        $dias = collect(range(0, 6))->map(fn ($i) => $inicio->copy()->addDays($i));

        return view('dono.agendamentos.semana', compact(
            'salao', 'manicures', 'dias', 'agendamentos', 'inicio', 'fim', 'manicureId'
        ));
    }

    /**
     * Exporta a agenda do salão em .ics (um dia via ?data= ou intervalo ?de=&ate=).
     */
    public function ical(Request $request, ICalService $ical)
    {
        $request->validate([
            'data'        => 'nullable|date',
            'de'          => 'nullable|date',
            'ate'         => 'nullable|date|after_or_equal:de',
            'manicure_id' => 'nullable|exists:manicures,id',
            'status'      => 'nullable|string',
        ]);

        [$inicio, $fim] = $this->periodoIcal($request);

        if ($inicio->diffInDays($fim) > 31) {
            return back()->withErrors(['error' => 'O período máximo para exportar é de 31 dias.']);
        }

        $salao = auth()->user()->salao;

        $agendamentos = $salao->agendamentos()
            ->with(['manicure', 'servicos', 'cliente', 'salao'])
            ->entre($inicio, $fim)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->manicure_id, fn ($q) => $q->where('manicure_id', $request->manicure_id))
            ->orderBy('data_hora_inicio')
            ->get();

        $conteudo = $ical->paraAgendamentos($agendamentos);

        return response($conteudo, 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$ical->nomeArquivoAgenda($inicio, $fim).'"',
        ]);
    }

    public function create()
    {
        $salao = auth()->user()->salao;
        $manicures = $salao->manicures;
        $servicos = $salao->servicos()->with('variacoesAtivas')->get();
        $clientes = $salao->clientes;

        return view('dono.agendamentos.create', compact('salao', 'manicures', 'servicos', 'clientes'));
    }

    public function store(StoreAgendamentoRequest $request)
    {
        $salao = auth()->user()->salao;
        $validated = $request->validated();

        $dados = [
            'salao_id'         => $salao->id,
            'manicure_id'      => $validated['manicure_id'],
            'servico_ids'      => $validated['servico_ids'],
            'servico_variacoes'=> $validated['servico_variacoes'] ?? [],
            'data_hora_inicio' => $validated['data_hora_inicio'],
            'cliente_id'       => $validated['cliente_id'] ?? null,
            'nome_cliente'     => $validated['nome_cliente'] ?? null,
            'telefone_cliente' => $validated['telefone_cliente'] ?? null,
            'observacoes'      => $validated['observacoes'] ?? null,
            'origem'           => 'balcao',
            'status'           => 'confirmado',
            'user_id'          => auth()->id(),
            'encaixe'          => (bool) ($validated['encaixe'] ?? false),
        ];

        // Encaixe: só dono/atendente; força horário informado (conflito duro no service).
        if ($dados['encaixe'] && empty($validated['data_hora_inicio'])) {
            return back()->withInput()->withErrors(['data_hora_inicio' => 'Informe data e hora do encaixe.']);
        }

        $recorrencia = $validated['recorrencia'] ?? 'nenhuma';
        $ocorrencias = (int) ($validated['ocorrencias'] ?? 1);

        try {
            if ($recorrencia !== 'nenhuma' && $ocorrencias > 1) {
                $res = $this->agendaService->criarRecorrente($dados, $recorrencia, $ocorrencias);
                $primeiro = $res['criados'][0] ?? null;

                if (! $primeiro) {
                    return back()->withInput()
                        ->withErrors(['error' => 'Nenhum horário disponível para a recorrência escolhida.']);
                }

                $msg = count($res['criados']).' agendamento(s) criado(s).';
                if (! empty($res['pulados'])) {
                    $msg .= ' Pulados por conflito: '.implode(', ', $res['pulados']).'.';
                }

                return redirect()->route('dono.agendamentos.show', $primeiro)->with('success', $msg);
            }

            $agendamento = $this->agendaService->criarAgendamento($dados);

            return redirect()->route('dono.agendamentos.show', $agendamento)
                ->with('success', 'Agendamento criado com sucesso!');
        } catch (\Throwable $e) {
            return $this->domainExceptionBack($e, 'Não foi possível criar o agendamento.', withInput: true);
        }
    }

    public function show(Agendamento $agendamento)
    {
        $this->authorize('view', $agendamento);
        $agendamento->load(['manicure', 'servicos', 'cliente', 'avaliacao', 'pagamentos', 'comanda.itens', 'comanda.pagamentos']);

        $produtos = Produto::where('salao_id', $agendamento->salao_id)
            ->where('ativo', true)
            ->where('estoque_atual', '>', 0)
            ->orderBy('nome')
            ->get();

        return view('dono.agendamentos.show', compact('agendamento', 'produtos'));
    }

    /**
     * Vende um produto no atendimento (adiciona à comanda e dá baixa no estoque).
     */
    public function venderProduto(Request $request, Agendamento $agendamento, ComandaService $comandaService)
    {
        $this->authorize('update', $agendamento);

        if (in_array($agendamento->status, ['concluido', 'cancelado', 'nao_compareceu'], true)) {
            return back()->withErrors(['error' => 'Não é possível vender produtos neste atendimento.']);
        }

        $request->validate([
            'produto_id' => 'required|exists:produtos,id',
            'quantidade' => 'required|numeric|min:0.001|max:999999',
        ]);

        $produto = Produto::where('id', $request->produto_id)
            ->where('salao_id', $agendamento->salao_id)
            ->firstOrFail();

        try {
            $comandaService->adicionarProduto($agendamento, $produto, (float) $request->quantidade, auth()->id());

            return back()->with('success', 'Produto adicionado à comanda.');
        } catch (\Throwable $e) {
            return $this->domainExceptionBack($e, 'Não foi possível adicionar o produto à comanda.');
        }
    }

    public function removerItem(Agendamento $agendamento, ComandaItem $item, ComandaService $comandaService)
    {
        $this->authorize('update', $agendamento);
        abort_unless($item->comanda && $item->comanda->agendamento_id === $agendamento->id, 403);

        $comandaService->removerItem($item, auth()->id());

        return back()->with('success', 'Item removido da comanda.');
    }

    /**
     * Resgata um vale-presente na comanda do atendimento.
     */
    public function aplicarVale(Request $request, Agendamento $agendamento, ComandaService $comandaService, ValePresenteService $vales)
    {
        $this->authorize('update', $agendamento);

        if (in_array($agendamento->status, ['concluido', 'cancelado', 'nao_compareceu'], true)) {
            return back()->withErrors(['error' => 'Não é possível aplicar vale neste atendimento.']);
        }

        $request->validate(['codigo' => 'required|string|max:20']);

        $vale = ValePresente::where('salao_id', $agendamento->salao_id)
            ->where('codigo', strtoupper(trim($request->codigo)))
            ->first();

        if (! $vale || ! $vale->estaDisponivel()) {
            return back()->withErrors(['error' => 'Vale-presente inválido, sem saldo ou expirado.']);
        }

        try {
            $pagamento = $comandaService->aplicarVale($agendamento, $vale, $vales);

            return back()->with('success', 'Vale aplicado: R$ '.number_format((float) $pagamento->valor, 2, ',', '.').'.');
        } catch (\Throwable $e) {
            return $this->domainExceptionBack($e, 'Não foi possível aplicar o vale-presente.');
        }
    }

    public function updateStatus(Request $request, Agendamento $agendamento)
    {
        $this->authorize('update', $agendamento);
        $request->validate(['status' => 'required|in:aguardando,confirmado,em_andamento,concluido,cancelado,nao_compareceu']);

        $concluido = AgendamentoStatus::Concluido->value;

        if ($request->status === $concluido && $agendamento->status !== $concluido) {
            $request->validate(['gorjeta' => 'nullable|numeric|min:0']);
            $this->agendaService->finalizarAtendimento($agendamento, [
                'forma'   => $request->forma_pagamento ?? FormaPagamento::Dinheiro->value,
                'gorjeta' => $request->input('gorjeta'),
            ]);
        } else {
            $agendamento->update(['status' => $request->status]);
        }

        return back()->with('success', 'Status atualizado!');
    }

    public function reagendarForm(Agendamento $agendamento)
    {
        $this->authorize('update', $agendamento);

        if (! $agendamento->podeSerReagendado()) {
            return redirect()->route('dono.agendamentos.show', $agendamento)
                ->withErrors(['error' => 'Este agendamento não pode ser remarcado.']);
        }

        $agendamento->load(['manicure', 'servicos', 'salao', 'cliente']);

        return view('dono.agendamentos.reagendar', compact('agendamento'));
    }

    public function reagendar(Request $request, Agendamento $agendamento)
    {
        $this->authorize('update', $agendamento);

        if (! $agendamento->podeSerReagendado()) {
            return back()->withErrors(['error' => 'Este agendamento não pode ser remarcado.']);
        }

        $request->validate([
            'data_hora_inicio' => 'required|date|after:now',
        ]);

        try {
            $this->agendaService->reagendar($agendamento, Carbon::parse($request->data_hora_inicio));

            return redirect()->route('dono.agendamentos.show', $agendamento)
                ->with('success', 'Agendamento remarcado!');
        } catch (\Throwable $e) {
            return $this->domainExceptionBack($e, 'Não foi possível remarcar o agendamento.', withInput: true);
        }
    }

    public function destroy(Agendamento $agendamento)
    {
        $this->authorize('update', $agendamento);
        $agendamento->update([
            'status'               => AgendamentoStatus::Cancelado->value,
            'observacoes_internas' => 'Cancelado pelo salão',
        ]);

        AgendamentoCanceladoEvent::dispatch(
            $agendamento,
            'Cancelado pelo salão',
            'salao',
        );

        return back()->with('success', 'Agendamento cancelado.');
    }

    /**
     * Cancela cobrança pendente ou estorna Pix aprovado no Mercado Pago,
     * sem cancelar o agendamento. Somente dono/admin (middleware financeiro).
     */
    public function estornarPix(Request $request, Agendamento $agendamento, MercadoPagoService $mp)
    {
        $this->authorize('update', $agendamento);

        $request->validate([
            'motivo' => 'nullable|string|max:255',
        ]);

        if (! $agendamento->mp_payment_id) {
            return back()->withErrors(['error' => 'Este agendamento não possui cobrança Pix online.']);
        }

        if (! $mp->habilitado()) {
            return back()->withErrors(['error' => 'Mercado Pago está desabilitado.']);
        }

        $tipo = $agendamento->mp_cobranca_tipo ?: 'sinal';
        $statusLocal = $tipo === 'total'
            ? $agendamento->mp_total_status
            : $agendamento->sinal_status;

        if (! in_array($statusLocal, ['pago', 'pendente'], true)) {
            return back()->withErrors(['error' => 'Não há cobrança Pix ativa para cancelar ou estornar.']);
        }

        $paymentIdAntes = $agendamento->mp_payment_id;
        $resultado = $mp->cancelarOuEstornar($agendamento->fresh());

        AuditLogger::log('pagamento.estornado', $agendamento, [
            'payment_id' => $paymentIdAntes,
            'tipo'       => $tipo,
            'acao'       => $resultado['acao'],
            'ok'         => $resultado['ok'],
            'status'     => $resultado['status'],
            'motivo'     => $request->input('motivo'),
        ]);

        if (! $resultado['ok']) {
            return back()->withErrors(['error' => 'Não foi possível cancelar/estornar no Mercado Pago. Tente novamente ou use o painel da MP.']);
        }

        $msg = match ($resultado['acao']) {
            'estornado' => 'Pagamento Pix estornado com sucesso.',
            'cancelado' => 'Cobrança Pix pendente cancelada.',
            default     => 'Status da cobrança Pix sincronizado.',
        };

        return back()->with('success', $msg);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodoIcal(Request $request): array
    {
        if ($request->filled('de')) {
            $inicio = Carbon::parse($request->de)->startOfDay();
            $fim = Carbon::parse($request->input('ate', $request->de))->endOfDay();

            return [$inicio, $fim];
        }

        $dia = Carbon::parse($request->input('data', today()->toDateString()));

        return [$dia->copy()->startOfDay(), $dia->copy()->endOfDay()];
    }
}
