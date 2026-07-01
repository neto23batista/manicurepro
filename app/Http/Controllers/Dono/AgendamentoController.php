<?php

namespace App\Http\Controllers\Dono;

use App\Enums\AgendamentoStatus;
use App\Events\AgendamentoCanceladoEvent;
use App\Http\Controllers\Concerns\AuthorizesSalao;
use App\Http\Controllers\Controller;
use App\Models\Agendamento;
use App\Models\ComandaItem;
use App\Models\Produto;
use App\Models\ValePresente;
use App\Services\AgendaService;
use App\Services\ComandaService;
use App\Services\ValePresenteService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendamentoController extends Controller
{
    use AuthorizesSalao;

    public function __construct(private AgendaService $agendaService) {}

    public function index(Request $request)
    {
        $salao = auth()->user()->salao;
        $status = $request->status;
        $data = $request->data ? Carbon::parse($request->data) : null;
        $manicureId = $request->manicure_id;

        $agendamentos = $salao->agendamentos()
            ->with(['manicure', 'servicos', 'cliente'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($data, fn($q) => $q->whereDate('data_hora_inicio', $data))
            ->when($manicureId, fn($q) => $q->where('manicure_id', $manicureId))
            ->orderByDesc('data_hora_inicio')
            ->paginate(20);

        $manicures = $salao->manicures;

        return view('dono.agendamentos.index', compact('agendamentos', 'manicures', 'status', 'manicureId'));
    }

    public function create()
    {
        $salao = auth()->user()->salao;
        $manicures = $salao->manicures;
        $servicos = $salao->servicos;
        $clientes = $salao->clientes;
        return view('dono.agendamentos.create', compact('salao', 'manicures', 'servicos', 'clientes'));
    }

    public function store(Request $request)
    {
        $salao = auth()->user()->salao;

        $request->validate([
            'manicure_id' => 'required|exists:manicures,id',
            'servico_ids' => 'required|array|min:1',
            'servico_ids.*' => 'exists:servicos,id',
            'data_hora_inicio' => 'required|date|after:now',
            'cliente_id' => 'nullable|exists:clientes,id',
            'nome_cliente' => 'nullable|string|max:255',
            'observacoes' => 'nullable|string',
            'recorrencia' => 'nullable|in:nenhuma,semanal,quinzenal,mensal',
            'ocorrencias' => 'nullable|integer|min:1|max:12',
        ]);

        $dados = [
            'salao_id' => $salao->id,
            'manicure_id' => $request->manicure_id,
            'servico_ids' => $request->servico_ids,
            'data_hora_inicio' => $request->data_hora_inicio,
            'cliente_id' => $request->cliente_id,
            'nome_cliente' => $request->nome_cliente,
            'telefone_cliente' => $request->telefone_cliente,
            'observacoes' => $request->observacoes,
            'origem' => 'balcao',
            'status' => 'confirmado',
            'user_id' => auth()->id(),
        ];

        $recorrencia = $request->input('recorrencia', 'nenhuma');
        $ocorrencias = (int) $request->input('ocorrencias', 1);

        try {
            if ($recorrencia !== 'nenhuma' && $ocorrencias > 1) {
                $res = $this->agendaService->criarRecorrente($dados, $recorrencia, $ocorrencias);
                $primeiro = $res['criados'][0] ?? null;

                if (!$primeiro) {
                    return back()->withInput()
                        ->withErrors(['error' => 'Nenhum horário disponível para a recorrência escolhida.']);
                }

                $msg = count($res['criados']) . ' agendamento(s) criado(s).';
                if (!empty($res['pulados'])) {
                    $msg .= ' Pulados por conflito: ' . implode(', ', $res['pulados']) . '.';
                }

                return redirect()->route('dono.agendamentos.show', $primeiro)->with('success', $msg);
            }

            $agendamento = $this->agendaService->criarAgendamento($dados);

            return redirect()->route('dono.agendamentos.show', $agendamento)
                ->with('success', 'Agendamento criado com sucesso!');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Agendamento $agendamento)
    {
        $this->authorizeSalaoAccess($agendamento);
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
        $this->authorizeSalaoAccess($agendamento);

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
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function removerItem(Agendamento $agendamento, ComandaItem $item, ComandaService $comandaService)
    {
        $this->authorizeSalaoAccess($agendamento);
        abort_unless($item->comanda && $item->comanda->agendamento_id === $agendamento->id, 403);

        $comandaService->removerItem($item, auth()->id());

        return back()->with('success', 'Item removido da comanda.');
    }

    /**
     * Resgata um vale-presente na comanda do atendimento.
     */
    public function aplicarVale(Request $request, Agendamento $agendamento, ComandaService $comandaService, ValePresenteService $vales)
    {
        $this->authorizeSalaoAccess($agendamento);

        if (in_array($agendamento->status, ['concluido', 'cancelado', 'nao_compareceu'], true)) {
            return back()->withErrors(['error' => 'Não é possível aplicar vale neste atendimento.']);
        }

        $request->validate(['codigo' => 'required|string|max:20']);

        $vale = ValePresente::where('salao_id', $agendamento->salao_id)
            ->where('codigo', strtoupper(trim($request->codigo)))
            ->first();

        if (!$vale || !$vale->estaDisponivel()) {
            return back()->withErrors(['error' => 'Vale-presente inválido, sem saldo ou expirado.']);
        }

        try {
            $pagamento = $comandaService->aplicarVale($agendamento, $vale, $vales);
            return back()->with('success', 'Vale aplicado: R$ ' . number_format($pagamento->valor, 2, ',', '.') . '.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, Agendamento $agendamento)
    {
        $this->authorizeSalaoAccess($agendamento);
        $request->validate(['status' => 'required|in:aguardando,confirmado,em_andamento,concluido,cancelado,nao_compareceu']);

        $concluido = AgendamentoStatus::Concluido->value;

        if ($request->status === $concluido && $agendamento->status !== $concluido) {
            $this->agendaService->finalizarAtendimento($agendamento, [
                'forma' => $request->forma_pagamento ?? \App\Enums\FormaPagamento::Dinheiro->value,
            ]);
        } else {
            $agendamento->update(['status' => $request->status]);
        }

        return back()->with('success', 'Status atualizado!');
    }

    public function reagendarForm(Agendamento $agendamento)
    {
        $this->authorizeSalaoAccess($agendamento);

        if (!$agendamento->podeSerReagendado()) {
            return redirect()->route('dono.agendamentos.show', $agendamento)
                ->withErrors(['error' => 'Este agendamento não pode ser remarcado.']);
        }

        $agendamento->load(['manicure', 'servicos', 'salao', 'cliente']);

        return view('dono.agendamentos.reagendar', compact('agendamento'));
    }

    public function reagendar(Request $request, Agendamento $agendamento)
    {
        $this->authorizeSalaoAccess($agendamento);

        if (!$agendamento->podeSerReagendado()) {
            return back()->withErrors(['error' => 'Este agendamento não pode ser remarcado.']);
        }

        $request->validate([
            'data_hora_inicio' => 'required|date|after:now',
        ]);

        try {
            $this->agendaService->reagendar($agendamento, Carbon::parse($request->data_hora_inicio));

            return redirect()->route('dono.agendamentos.show', $agendamento)
                ->with('success', 'Agendamento remarcado!');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(Agendamento $agendamento)
    {
        $this->authorizeSalaoAccess($agendamento);
        $agendamento->update([
            'status' => AgendamentoStatus::Cancelado->value,
            'observacoes_internas' => 'Cancelado pelo salão',
        ]);

        AgendamentoCanceladoEvent::dispatch(
            $agendamento,
            'Cancelado pelo salão',
            'salao'
        );

        return back()->with('success', 'Agendamento cancelado.');
    }
}
