@extends('layouts.app')

@section('title', 'Agendamento #' . $agendamento->id)
@section('page-title', 'Agendamento #' . $agendamento->id)

@section('content')
@if($agendamento->cliente && $agendamento->cliente->eh_risco_no_show)
    <div class="alert alert-warning alert-permanent">
        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
        <div>
            <strong>Atenção:</strong> este cliente já registra
            {{ $agendamento->cliente->total_faltas }} {{ \Illuminate\Support\Str::plural('falta', $agendamento->cliente->total_faltas) }}
            (não comparecimento). Considere confirmar presença com antecedência.
            <x-badge-no-show :cliente="$agendamento->cliente" class="ms-2" />
        </div>
    </div>
@endif

@php($comanda = $agendamento->comanda)
@php($podeVender = ! in_array($agendamento->status, ['concluido', 'cancelado', 'nao_compareceu']))
@php($totalReceber = $comanda ? (float) $comanda->total : (float) $agendamento->valor_total - (float) $agendamento->valor_desconto)
@php($vouchers = $comanda ? $comanda->pagamentos->where('forma', 'voucher') : collect())
@php($jaPago = (float) $vouchers->sum('valor'))
{{-- Fonte única do saldo: Comanda::getSaldoAttribute (total − pagamentos confirmados) --}}
@php($saldoReceber = $comanda ? max(0, (float) $comanda->saldo) : $totalReceber)

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Detalhes do Agendamento</h5>
                <span class="badge bg-{{ $agendamento->status_color }} fs-6">{{ $agendamento->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Cliente</label>
                        <p class="fw-semibold mb-0">{{ $agendamento->nome_cliente_exibido }}</p>
                        @if($agendamento->telefone_cliente)
                            <small class="text-muted">{{ $agendamento->telefone_cliente }}</small>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Manicure</label>
                        <div class="d-flex align-items-center">
                            <img src="{{ $agendamento->manicure?->foto_url }}" width="36" height="36" class="rounded-circle me-2">
                            <p class="fw-semibold mb-0">{{ $agendamento->manicure?->nome }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Data e Hora</label>
                        <p class="fw-semibold mb-0">
                            <i class="fas fa-calendar text-pink me-1"></i>
                            {{ $agendamento->data_hora_inicio->format('d/m/Y H:i') }} -
                            {{ $agendamento->data_hora_fim->format('H:i') }}
                            @if($agendamento->encaixe)
                                <span class="badge bg-warning text-dark ms-1">Encaixe</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Duração</label>
                        <p class="fw-semibold mb-0">{{ $agendamento->duracao_minutos }} minutos</p>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small">Serviços</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($agendamento->servicos as $s)
                                <span class="badge bg-pink-light text-pink border border-pink">
                                    {{ $s->nome }} — R$ {{ number_format($s->pivot->preco, 2, ',', '.') }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @if($agendamento->observacoes)
                        <div class="col-12">
                            <label class="text-muted small">Observações</label>
                            <p class="mb-0">{{ $agendamento->observacoes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Produtos / Comanda --}}
        @if($podeVender || ($comanda && $comanda->valor_produtos > 0))
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-basket-shopping text-pink me-2"></i> Produtos vendidos</h5>
                </div>
                <div class="card-body">
                    @php($itensProduto = $comanda ? $comanda->itens->where('tipo', 'produto') : collect())
                    @if($itensProduto->count())
                        @foreach($itensProduto as $item)
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <span class="fw-semibold">{{ $item->descricao }}</span>
                                    <small class="text-muted ms-1">
                                        {{ rtrim(rtrim(number_format($item->quantidade, 3, ',', '.'), '0'), ',') }} ×
                                        R$ {{ number_format($item->preco_unitario, 2, ',', '.') }}
                                    </small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <strong>R$ {{ number_format($item->subtotal, 2, ',', '.') }}</strong>
                                    @if($podeVender)
                                        <form method="POST" action="{{ route('dono.agendamentos.item.remover', [$agendamento, $item]) }}"
                                              data-confirm="Remover item?" data-confirm-ok="Remover">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-ghost text-danger" title="Remover"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        <div class="d-flex justify-content-between pt-2">
                            <span class="text-muted">Subtotal produtos</span>
                            <strong>R$ {{ number_format($comanda->valor_produtos, 2, ',', '.') }}</strong>
                        </div>
                    @else
                        <p class="text-muted small mb-3">Nenhum produto vendido neste atendimento.</p>
                    @endif

                    @if($podeVender)
                        @if($produtos->count())
                            <form method="POST" action="{{ route('dono.agendamentos.produto', $agendamento) }}" class="row g-2 mt-2">
                                @csrf
                                <div class="col-7">
                                    <select name="produto_id" class="form-select form-select-sm" required>
                                        <option value="">Adicionar produto…</option>
                                        @foreach($produtos as $p)
                                            <option value="{{ $p->id }}">
                                                {{ $p->nome }} — R$ {{ number_format($p->preco_venda, 2, ',', '.') }}
                                                ({{ rtrim(rtrim(number_format($p->estoque_atual, 3, ',', '.'), '0'), ',') }} {{ $p->unidade }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-3">
                                    <input type="number" name="quantidade" class="form-control form-control-sm" value="1" step="0.001" min="0.001" required>
                                </div>
                                <div class="col-2">
                                    <button class="btn btn-pink btn-sm w-100" title="Adicionar"><i class="fas fa-plus"></i></button>
                                </div>
                            </form>
                        @else
                            <a href="{{ route('dono.produtos.create') }}" class="btn btn-outline-pink btn-sm mt-2">
                                <i class="fas fa-plus me-1"></i>Cadastrar produtos
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        @endif

        {{-- Vale-presente --}}
        @if($podeVender || $vouchers->isNotEmpty())
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-gift text-pink me-2"></i> Vale-presente</h5>
                </div>
                <div class="card-body">
                    @foreach($vouchers as $v)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <i class="fas fa-ticket text-pink me-1"></i>
                                <span class="fw-semibold">{{ $v->observacoes ?: 'Vale-presente' }}</span>
                            </div>
                            <strong class="text-success">- R$ {{ number_format($v->valor, 2, ',', '.') }}</strong>
                        </div>
                    @endforeach

                    @if($podeVender)
                        <form method="POST" action="{{ route('dono.agendamentos.vale', $agendamento) }}" class="row g-2 mt-2">
                            @csrf
                            <div class="col-8">
                                <input type="text" name="codigo" class="form-control form-control-sm text-uppercase"
                                       placeholder="Código do vale (ex: VP-XXXXXXXX)" maxlength="20" required>
                            </div>
                            <div class="col-4">
                                <button class="btn btn-outline-pink btn-sm w-100"><i class="fas fa-ticket me-1"></i>Aplicar</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        {{-- Avaliação --}}
        @if($agendamento->avaliacao)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-star text-pink me-2"></i> Avaliação</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="text-warning fs-4">{{ $agendamento->avaliacao->estrelas }}</span>
                        <span class="fw-bold">{{ $agendamento->avaliacao->nota }}/5</span>
                    </div>
                    @if($agendamento->avaliacao->comentario)
                        <p class="mb-0 fst-italic">"{{ $agendamento->avaliacao->comentario }}"</p>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        {{-- Resumo Financeiro --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-money-bill text-pink me-2"></i> Resumo</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Valor dos Serviços</span>
                    <strong>R$ {{ number_format($agendamento->valor_total, 2, ',', '.') }}</strong>
                </div>
                @if($comanda && $comanda->valor_produtos > 0)
                    <div class="d-flex justify-content-between mb-2">
                        <span>Produtos</span>
                        <strong>R$ {{ number_format($comanda->valor_produtos, 2, ',', '.') }}</strong>
                    </div>
                @endif
                @if($agendamento->valor_desconto > 0)
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Desconto</span>
                        <strong>- R$ {{ number_format($agendamento->valor_desconto, 2, ',', '.') }}</strong>
                    </div>
                @endif
                @if($jaPago > 0)
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Vale-presente</span>
                        <strong>- R$ {{ number_format($jaPago, 2, ',', '.') }}</strong>
                    </div>
                @endif
                <hr>
                @if($podeVender && $jaPago > 0)
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold fs-5">A receber</span>
                        <strong class="text-pink fs-5">R$ {{ number_format($saldoReceber, 2, ',', '.') }}</strong>
                    </div>
                @else
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold fs-5">Total</span>
                        <strong class="text-pink fs-5">R$ {{ number_format($totalReceber, 2, ',', '.') }}</strong>
                    </div>
                @endif

                @php($mpTipo = $agendamento->mp_cobranca_tipo ?: 'sinal')
                @php($mpStatus = $mpTipo === 'total' ? $agendamento->mp_total_status : $agendamento->sinal_status)
                @php($mpValor = $mpTipo === 'total' ? $agendamento->mp_total_valor : $agendamento->sinal_valor)
                @php($podeEstornarPix = $agendamento->mp_payment_id && in_array($mpStatus, ['pago', 'pendente'], true) && (auth()->user()?->isDono() || auth()->user()?->isSuperAdmin()))
                @if($agendamento->mp_payment_id && $mpStatus)
                    <hr>
                    <div class="small text-muted mb-1">Pix online (Mercado Pago)</div>
                    <div class="d-flex justify-content-between mb-1">
                        <span>{{ $mpTipo === 'total' ? 'Cobrança total' : 'Sinal' }}</span>
                        <span class="badge bg-{{ $mpStatus === 'pago' ? 'success' : ($mpStatus === 'pendente' ? 'warning text-dark' : 'secondary') }}">
                            {{ $mpStatus }}
                        </span>
                    </div>
                    @if($mpValor)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Valor</span>
                            <strong>R$ {{ number_format((float) $mpValor, 2, ',', '.') }}</strong>
                        </div>
                    @endif
                    @if($podeEstornarPix)
                        <form method="POST" action="{{ route('dono.agendamentos.estorno-pix', $agendamento) }}"
                              class="mt-2"
                              data-confirm="{{ $mpStatus === 'pago' ? 'Estornar Pix?' : 'Cancelar cobrança Pix?' }}"
                              data-confirm-message="{{ $mpStatus === 'pago' ? 'O valor será devolvido ao cliente no Mercado Pago. O agendamento NÃO será cancelado.' : 'A cobrança pendente será cancelada na MP. O agendamento NÃO será cancelado.' }}"
                              data-confirm-type="warning"
                              data-confirm-ok="{{ $mpStatus === 'pago' ? 'Estornar' : 'Cancelar cobrança' }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small mb-1" for="motivoEstorno">Motivo (opcional)</label>
                                <input type="text" name="motivo" id="motivoEstorno" class="form-control form-control-sm"
                                       maxlength="255" placeholder="Ex.: cliente desistiu do sinal">
                            </div>
                            <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                                <i class="fas fa-rotate-left me-1" aria-hidden="true"></i>
                                {{ $mpStatus === 'pago' ? 'Estornar Pix' : 'Cancelar cobrança Pix' }}
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>

        {{-- Ações --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Ações</h5>
            </div>
            <div class="card-body d-grid gap-2">
                @if($agendamento->status === 'aguardando')
                    <form method="POST" action="{{ route('dono.agendamentos.status', $agendamento) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="confirmado">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check me-2"></i> Confirmar
                        </button>
                    </form>
                @endif

                @if(in_array($agendamento->status, ['aguardando', 'confirmado']))
                    <form method="POST" action="{{ route('dono.agendamentos.status', $agendamento) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="em_andamento">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-play me-2"></i> Iniciar Atendimento
                        </button>
                    </form>
                @endif

                @if($agendamento->status === 'em_andamento')
                    <button type="button" class="btn btn-pink w-100" data-bs-toggle="modal" data-bs-target="#modalConcluir">
                        <i class="fas fa-flag-checkered me-2"></i> Finalizar Atendimento
                    </button>
                @endif

                @if($agendamento->podeSerReagendado())
                    <a href="{{ route('dono.agendamentos.reagendar.form', $agendamento) }}" class="btn btn-outline-pink w-100">
                        <i class="fas fa-clock-rotate-left me-2"></i> Remarcar
                    </a>
                @endif

                @if(in_array($agendamento->status, ['aguardando', 'confirmado']))
                    <form method="POST" action="{{ route('dono.agendamentos.status', $agendamento) }}" data-confirm="Marcar como falta?" data-confirm-message="Confirmar que o cliente não compareceu?" data-confirm-type="warning" data-confirm-ok="Marcar falta">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="nao_compareceu">
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-user-xmark me-2"></i> Marcar falta
                        </button>
                    </form>
                @endif

                @if($agendamento->podeSerCancelado())
                    <form method="POST" action="{{ route('dono.agendamentos.destroy', $agendamento) }}" data-confirm="Cancelar agendamento?" data-confirm-ok="Cancelar agendamento">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fas fa-times me-2"></i> Cancelar Agendamento
                        </button>
                    </form>
                @endif

                @if($agendamento->status === 'concluido' && config('manicure.fiscal.enabled'))
                    <form method="POST" action="{{ route('dono.notas-fiscais.store') }}">
                        @csrf
                        <input type="hidden" name="agendamento_id" value="{{ $agendamento->id }}">
                        <button type="submit" class="btn btn-outline-secondary w-100"
                                title="Stub local — NÃO emite SEFAZ">
                            <i class="fas fa-file-invoice me-2"></i> Gerar rascunho NF-e (stub)
                        </button>
                    </form>
                    <a href="{{ route('dono.notas-fiscais.index') }}" class="btn btn-ghost btn-sm text-muted">
                        Ver rascunhos fiscais
                    </a>
                @endif

                <a href="{{ route('dono.agendamentos.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Voltar
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Modal Finalizar --}}
<div class="modal fade" id="modalConcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Finalizar Atendimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('dono.agendamentos.status', $agendamento) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="concluido">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Forma de Pagamento</label>
                        <select name="forma_pagamento" class="form-select" required>
                            @foreach(\App\Models\Pagamento::FORMAS_LABELS as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="gorjeta">Gorjeta (opcional)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" name="gorjeta" id="gorjeta" class="form-control"
                                   min="0" step="0.01" value="{{ old('gorjeta', 0) }}" placeholder="0,00">
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <strong>Total a receber:</strong>
                        R$ {{ number_format($saldoReceber, 2, ',', '.') }}
                        @if($jaPago > 0)
                            <div class="small text-muted mt-1">Vale-presente já aplicado: R$ {{ number_format($jaPago, 2, ',', '.') }}</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-pink">
                        <i class="fas fa-check me-2"></i> Confirmar Finalização
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
