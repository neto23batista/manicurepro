@extends('layouts.app')

@section('title', 'Caixa & Comissões')
@section('page-title', 'Caixa & Comissões')

@section('content')
@include('dono.financeiro._nav')

{{-- Filtro de período --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('dono.financeiro.index') }}" class="row g-2 align-items-end">
            <div class="col-auto">
                <div class="btn-group" role="group" aria-label="Período rápido">
                    <a href="{{ route('dono.financeiro.index', ['periodo' => 'hoje']) }}"
                       class="btn btn-sm {{ $periodo === 'hoje' ? 'btn-pink' : 'btn-outline-secondary' }}">Hoje</a>
                    <a href="{{ route('dono.financeiro.index', ['periodo' => 'semana']) }}"
                       class="btn btn-sm {{ $periodo === 'semana' ? 'btn-pink' : 'btn-outline-secondary' }}">Esta semana</a>
                    <a href="{{ route('dono.financeiro.index', ['periodo' => 'mes']) }}"
                       class="btn btn-sm {{ $periodo === 'mes' ? 'btn-pink' : 'btn-outline-secondary' }}">Este mês</a>
                </div>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">De</label>
                <input type="date" name="data_inicio" value="{{ $inicio->toDateString() }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Até</label>
                <input type="date" name="data_fim" value="{{ $fim->toDateString() }}" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-pink"><i class="fas fa-filter me-1"></i>Filtrar</button>
            </div>
            <div class="col text-end">
                <small class="text-muted">
                    {{ $inicio->format('d/m/Y') }} — {{ $fim->format('d/m/Y') }}
                </small>
                @if(config('manicure.fiscal.enabled'))
                    <div class="mt-1">
                        <a href="{{ route('dono.notas-fiscais.index') }}" class="btn btn-sm btn-outline-secondary"
                           title="Stub local — NÃO emite SEFAZ">
                            <i class="fas fa-file-invoice me-1"></i>Notas fiscais (stub)
                        </a>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Cards-resumo --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-pink h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-uppercase opacity-75">Entradas no caixa</div>
                        <div class="fs-3 fw-bold">@money($caixa['total'])</div>
                    </div>
                    <i class="fas fa-cash-register fa-2x opacity-50"></i>
                </div>
                <div class="small opacity-75 mt-1">{{ $caixa['count'] }} pagamento(s)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-uppercase text-muted">Serviços (base comissão)</div>
                        <div class="fs-3 fw-bold">@money($totalServicos)</div>
                    </div>
                    <i class="fas fa-spa fa-2x text-pink opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-uppercase text-muted">Comissões a pagar</div>
                        <div class="fs-3 fw-bold text-danger">@money($totalAPagar)</div>
                    </div>
                    <i class="fas fa-hand-holding-dollar fa-2x text-danger opacity-50"></i>
                </div>
                @if($totalPago > 0)
                    <div class="small text-muted mt-1">Gerado: @money($totalComissoes)</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-uppercase text-muted">Já repassado</div>
                        <div class="fs-3 fw-bold text-success">@money($totalPago)</div>
                    </div>
                    <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Fluxo de caixa --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-chart-line text-pink me-2"></i>Fluxo de caixa</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="small text-uppercase text-muted">Entradas</div>
                <div class="fs-4 fw-bold text-success">@money($fluxo['entradas'])</div>
                <small class="text-muted">Pagamentos confirmados</small>
            </div>
            <div class="col-md-3">
                <div class="small text-uppercase text-muted">Saídas</div>
                <div class="fs-4 fw-bold text-danger">@money($fluxo['saidas'])</div>
                <small class="text-muted">
                    Despesas @money($fluxo['despesas_pagas'])
                    · Comissões @money($fluxo['comissoes_pagas'])
                </small>
            </div>
            <div class="col-md-3">
                <div class="small text-uppercase text-muted">Saldo do período</div>
                <div class="fs-4 fw-bold {{ $fluxo['saldo'] >= 0 ? 'text-success' : 'text-danger' }}">
                    @money($fluxo['saldo'])
                </div>
            </div>
            <div class="col-md-3">
                <div class="small text-uppercase text-muted">Despesas a vencer</div>
                <div class="fs-4 fw-bold">@money($fluxo['despesas_pendentes'])</div>
                <small class="text-muted">{{ $fluxo['despesas_pendentes_count'] }} pendente(s) no período</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Caixa por forma de pagamento --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-wallet text-pink me-2"></i>Caixa por forma de pagamento</h5>
            </div>
            <div class="card-body p-0">
                @forelse($caixa['porForma'] as $linha)
                    <div class="d-flex align-items-center justify-content-between p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>
                            <div class="fw-semibold">{{ $linha['label'] }}</div>
                            <small class="text-muted">{{ $linha['count'] }} pagamento(s)</small>
                        </div>
                        <div class="fw-bold">@money($linha['total'])</div>
                    </div>
                @empty
                    <div class="empty-state py-4">
                        <div class="empty-state-icon"><i class="fas fa-wallet"></i></div>
                        <p class="mb-0">Nenhuma entrada no período.</p>
                    </div>
                @endforelse
            </div>
            @if(($caixa['resgatesVale'] ?? 0) > 0)
                <div class="d-flex align-items-center justify-content-between p-3 border-top">
                    <div>
                        <div class="fw-semibold text-muted"><i class="fas fa-gift me-1"></i>Resgates de vale-presente</div>
                        <small class="text-muted">{{ $caixa['resgatesValeCount'] }} resgate(s) — não soma (entrada registrada na venda do vale)</small>
                    </div>
                    <div class="text-muted">@money($caixa['resgatesVale'])</div>
                </div>
            @endif
            @if($caixa['count'] > 0)
                <div class="card-footer d-flex justify-content-between fw-bold">
                    <span>Total</span>
                    <span>@money($caixa['total'])</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Comissões por profissional --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-hand-sparkles text-pink me-2"></i>Comissão por profissional</h5>
            </div>
            <div class="card-body p-0">
                @if($comissoes->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Profissional</th>
                                    <th class="text-center">Atend.</th>
                                    <th class="text-end">Serviços</th>
                                    <th class="text-center">Taxa</th>
                                    <th class="text-end">Comissão</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comissoes as $c)
                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $c['nome'] }}
                                            @if(!empty($c['usa_regra_servico']))
                                                <span class="badge text-bg-light border ms-1" title="Usa regra por serviço">serviço</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $c['atendimentos'] }}</td>
                                        <td class="text-end">@money($c['base'])</td>
                                        <td class="text-center">
                                            @if($c['taxa'] > 0)
                                                {{ rtrim(rtrim(number_format($c['taxa'], 2, ',', '.'), '0'), ',') }}%
                                                @if(!empty($c['usa_regra_servico']))
                                                    <div class="small text-muted">efetiva</div>
                                                @endif
                                            @else
                                                <span class="text-muted" title="Defina a comissão no cadastro do profissional">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">
                                            @money($c['comissao'])
                                            @if(($c['ajuste'] ?? 0) != 0)
                                                <div class="small {{ $c['ajuste'] > 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $c['ajuste'] > 0 ? '+' : '' }}@money($c['ajuste'])
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($c['pago'])
                                                <span class="badge text-bg-success">Pago</span>
                                                @if($c['pagamento_id'])
                                                    <form method="POST"
                                                          action="{{ route('dono.financeiro.comissoes.destroy', $c['pagamento_id']) }}"
                                                          class="d-inline"
                                                          data-confirm="Desfazer repasse?"
                                                          data-confirm-message="A comissão voltará a constar como a pagar."
                                                          data-confirm-ok="Desfazer">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-link btn-sm text-muted p-0 ms-1" title="Desfazer">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @elseif($c['manicure_id'] && $c['comissao'] > 0)
                                                <form method="POST" action="{{ route('dono.financeiro.comissoes.store') }}" class="d-inline"
                                                      data-confirm="Marcar como pago?"
                                                      data-confirm-message="Registrar repasse de {{ $c['nome'] }} no período selecionado."
                                                      data-confirm-type="success"
                                                      data-confirm-ok="Marcar pago">
                                                    @csrf
                                                    <input type="hidden" name="manicure_id" value="{{ $c['manicure_id'] }}">
                                                    <input type="hidden" name="data_inicio" value="{{ $inicio->toDateString() }}">
                                                    <input type="hidden" name="data_fim" value="{{ $fim->toDateString() }}">
                                                    <input type="hidden" name="periodo" value="{{ $periodo }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-check me-1"></i>Marcar pago
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2">Total</td>
                                    <td class="text-end">@money($totalServicos)</td>
                                    <td></td>
                                    <td class="text-end">@money($totalComissoes)</td>
                                    <td class="text-end text-danger">@money($totalAPagar)</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="text-muted small p-3 mb-0">
                        <i class="fas fa-circle-info me-1"></i>
                        % padrão vem do cadastro da manicure. Serviço com % ou valor fixo próprio sobrepõe essa taxa.
                        Ajustes manuais (+/−) entram no total a pagar.
                    </p>
                @else
                    <div class="empty-state py-4">
                        <div class="empty-state-icon"><i class="fas fa-hand-sparkles"></i></div>
                        <p class="mb-0">Nenhum atendimento concluído no período.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Ajustes manuais de comissão --}}
<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="card-title mb-0"><i class="fas fa-sliders text-pink me-2"></i>Ajustes de comissão</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dono.financeiro.comissoes.ajustes.store') }}" class="row g-2 align-items-end mb-3">
                    @csrf
                    <input type="hidden" name="data_inicio" value="{{ $inicio->toDateString() }}">
                    <input type="hidden" name="data_fim" value="{{ $fim->toDateString() }}">
                    <input type="hidden" name="periodo" value="{{ $periodo }}">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Profissional</label>
                        <select name="manicure_id" class="form-select @error('manicure_id') is-invalid @enderror" required>
                            <option value="">Selecione…</option>
                            @foreach($manicures as $m)
                                <option value="{{ $m->id }}" @selected(old('manicure_id') == $m->id)>{{ $m->nome }}</option>
                            @endforeach
                        </select>
                        @error('manicure_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Valor (+/−)</label>
                        <input type="number" name="valor" step="0.01" class="form-control @error('valor') is-invalid @enderror"
                               value="{{ old('valor') }}" placeholder="Ex: 10 ou -5" required>
                        @error('valor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Motivo</label>
                        <input type="text" name="motivo" class="form-control @error('motivo') is-invalid @enderror"
                               value="{{ old('motivo') }}" maxlength="255" placeholder="Opcional">
                        @error('motivo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-pink w-100">
                            <i class="fas fa-plus me-1"></i>Registrar ajuste
                        </button>
                    </div>
                </form>

                @if($ajustes->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Profissional</th>
                                    <th class="text-end">Valor</th>
                                    <th>Motivo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ajustes as $aj)
                                    <tr>
                                        <td>{{ $aj->manicure?->nome }}</td>
                                        <td class="text-end {{ $aj->valor >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $aj->valor > 0 ? '+' : '' }}@money($aj->valor)
                                        </td>
                                        <td class="text-muted">{{ $aj->motivo ?: '—' }}</td>
                                        <td class="text-end">
                                            <form method="POST"
                                                  action="{{ route('dono.financeiro.comissoes.ajustes.destroy', $aj) }}"
                                                  class="d-inline"
                                                  data-confirm="Remover ajuste?"
                                                  data-confirm-ok="Remover">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link btn-sm text-muted p-0" title="Remover">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted small mb-0">Nenhum ajuste neste período.</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Repasses do período + histórico --}}
<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-money-bill-transfer text-pink me-2"></i>Repasses neste período</h5>
            </div>
            <div class="card-body p-0">
                @if($repasses->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Profissional</th>
                                    <th class="text-end">Valor</th>
                                    <th>Pago em</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($repasses as $r)
                                    <tr>
                                        <td class="fw-semibold">{{ $r->manicure?->nome ?? '—' }}</td>
                                        <td class="text-end">@money($r->valor)</td>
                                        <td>
                                            <span>{{ $r->pago_em->format('d/m/Y H:i') }}</span>
                                            @if($r->observacao)
                                                <div class="small text-muted">{{ $r->observacao }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <form method="POST"
                                                  action="{{ route('dono.financeiro.comissoes.destroy', $r) }}"
                                                  data-confirm="Desfazer repasse?"
                                                  data-confirm-message="A comissão voltará a constar como a pagar."
                                                  data-confirm-ok="Desfazer">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Desfazer">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state py-4">
                        <div class="empty-state-icon"><i class="fas fa-money-bill-transfer"></i></div>
                        <p class="mb-0">Nenhum repasse registrado neste período.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-clock-rotate-left text-pink me-2"></i>Histórico recente</h5>
            </div>
            <div class="card-body p-0">
                @if($historico->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Profissional</th>
                                    <th>Período</th>
                                    <th class="text-end">Valor</th>
                                    <th>Pago em</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($historico as $h)
                                    <tr>
                                        <td class="fw-semibold">{{ $h->manicure?->nome ?? '—' }}</td>
                                        <td class="small text-muted">
                                            {{ $h->periodo_inicio->format('d/m/Y') }} — {{ $h->periodo_fim->format('d/m/Y') }}
                                        </td>
                                        <td class="text-end">@money($h->valor)</td>
                                        <td class="small">{{ $h->pago_em->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state py-4">
                        <div class="empty-state-icon"><i class="fas fa-clock-rotate-left"></i></div>
                        <p class="mb-0">Nenhum repasse registrado ainda.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
