@extends('layouts.app')

@section('title', 'Caixa & Comissões')
@section('page-title', 'Caixa & Comissões')

@section('content')
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
            </div>
        </form>
    </div>
</div>

{{-- Cards-resumo --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
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
    <div class="col-md-4">
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
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-uppercase text-muted">Comissões a pagar</div>
                        <div class="fs-3 fw-bold text-danger">@money($totalComissoes)</div>
                    </div>
                    <i class="fas fa-hand-holding-dollar fa-2x text-danger opacity-50"></i>
                </div>
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
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comissoes as $c)
                                    <tr>
                                        <td class="fw-semibold">{{ $c['nome'] }}</td>
                                        <td class="text-center">{{ $c['atendimentos'] }}</td>
                                        <td class="text-end">@money($c['base'])</td>
                                        <td class="text-center">
                                            @if($c['taxa'] > 0)
                                                {{ rtrim(rtrim(number_format($c['taxa'], 2, ',', '.'), '0'), ',') }}%
                                            @else
                                                <span class="text-muted" title="Defina a comissão no cadastro do profissional">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">@money($c['comissao'])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2">Total</td>
                                    <td class="text-end">@money($totalServicos)</td>
                                    <td></td>
                                    <td class="text-end">@money($totalComissoes)</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="text-muted small p-3 mb-0">
                        <i class="fas fa-circle-info me-1"></i>
                        A taxa de comissão de cada profissional é definida no cadastro de manicures. Produtos não entram na base.
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
@endsection
