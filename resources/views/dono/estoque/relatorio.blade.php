@extends('layouts.app')

@section('title', 'Relatório de estoque')
@section('page-title', 'Relatório de estoque')

@section('content')
@php($r = $relatorio)
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form method="GET" class="d-flex align-items-center gap-2">
        <label class="form-label mb-0 small text-muted">Período (giro)</label>
        <select name="periodo" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            @foreach([7, 15, 30, 60, 90] as $d)
                <option value="{{ $d }}" @selected($periodo === $d)>{{ $d }} dias</option>
            @endforeach
        </select>
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('dono.estoque.inventario.create') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-clipboard-check me-1"></i>Inventário
        </a>
        <a href="{{ route('dono.estoque.relatorio.csv', ['periodo' => $periodo]) }}" class="btn btn-pink btn-sm">
            <i class="fas fa-file-csv me-1"></i>Exportar CSV
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Produtos ativos</div>
                <div class="fs-4 fw-bold">{{ $r['resumo']['produtos'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Parados ({{ $r['dias_parado'] }}d)</div>
                <div class="fs-4 fw-bold {{ $r['resumo']['parados'] > 0 ? 'text-warning' : '' }}">{{ $r['resumo']['parados'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Estoque baixo</div>
                <div class="fs-4 fw-bold {{ $r['resumo']['baixo_estoque'] > 0 ? 'text-danger' : '' }}">{{ $r['resumo']['baixo_estoque'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small">Margem média</div>
                <div class="fs-4 fw-bold">
                    {{ $r['resumo']['margem_media'] !== null ? number_format($r['resumo']['margem_media'], 1, ',', '.').'%' : '—' }}
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-chart-bar text-pink me-2"></i>Giro, parados e margem</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Fornecedor</th>
                        <th class="text-end">Estoque</th>
                        <th class="text-end">Margem</th>
                        <th class="text-end">Saídas {{ $periodo }}d</th>
                        <th class="text-end">Giro</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($r['itens'] as $item)
                        <tr>
                            <td class="fw-semibold">{{ $item->produto->nome }}</td>
                            <td class="text-muted">{{ $item->produto->fornecedor?->nome ?? '—' }}</td>
                            <td class="text-end">{{ rtrim(rtrim(number_format($item->estoque_atual, 3, ',', '.'), '0'), ',') }} {{ $item->produto->unidade }}</td>
                            <td class="text-end">
                                @if($item->margem_pct !== null)
                                    {{ number_format($item->margem_pct, 1, ',', '.') }}%
                                    <small class="text-muted d-block">@money($item->margem_valor)</small>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">{{ rtrim(rtrim(number_format($item->saidas_periodo, 3, ',', '.'), '0'), ',') }}</td>
                            <td class="text-end">{{ number_format($item->giro, 2, ',', '.') }}</td>
                            <td>
                                @if($item->produto->estoque_baixo)
                                    <span class="badge bg-danger">Baixo</span>
                                @elseif($item->parado)
                                    <span class="badge bg-warning text-dark">Parado</span>
                                @else
                                    <span class="badge bg-success">Ok</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-4 text-center text-muted">Nenhum produto ativo para analisar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
