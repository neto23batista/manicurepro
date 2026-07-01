@extends('layouts.app')

@section('title', 'Relatórios')
@section('page-title', 'Relatórios')

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-filter text-pink me-2"></i> Filtros</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Salão</label>
                <select name="salao_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach($saloes as $s)
                        <option value="{{ $s->id }}" {{ $salaoId == $s->id ? 'selected' : '' }}>{{ $s->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Data Início</label>
                <input type="date" name="data_inicio" class="form-control" value="{{ $dataInicio->toDateString() }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Data Fim</label>
                <input type="date" name="data_fim" class="form-control" value="{{ $dataFim->toDateString() }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-pink flex-fill">
                    <i class="fas fa-search me-1"></i> Filtrar
                </button>
                <a href="{{ route('admin.relatorios.pdf', request()->all()) }}" class="btn btn-outline-danger">
                    <i class="fas fa-file-pdf"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-blue-light"><i class="fas fa-calendar text-blue"></i></div>
            <div class="stat-info">
                <div class="stat-value">{{ $resumo['total'] }}</div>
                <div class="stat-label">Total de Agendamentos</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-green-light"><i class="fas fa-check text-green"></i></div>
            <div class="stat-info">
                <div class="stat-value">{{ $resumo['concluidos'] }}</div>
                <div class="stat-label">Concluídos</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-red-light"><i class="fas fa-times text-danger"></i></div>
            <div class="stat-info">
                <div class="stat-value">{{ $resumo['cancelados'] }}</div>
                <div class="stat-label">Cancelados</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-pink-light"><i class="fas fa-money-bill text-pink"></i></div>
            <div class="stat-info">
                <div class="stat-value">R$ {{ number_format($resumo['faturamento'], 2, ',', '.') }}</div>
                <div class="stat-label">Faturamento</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-hand-sparkles text-pink me-2"></i> Desempenho por Manicure</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Manicure</th>
                        <th>Total</th>
                        <th>Concluídos</th>
                        <th>Taxa de Conclusão</th>
                        <th>Faturamento</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($porManicure as $m)
                        <tr>
                            <td class="fw-semibold">{{ $m['nome'] }}</td>
                            <td>{{ $m['total'] }}</td>
                            <td>{{ $m['concluidos'] }}</td>
                            <td>
                                @php $taxa = $m['total'] > 0 ? round($m['concluidos'] / $m['total'] * 100) : 0; @endphp
                                <div class="progress" style="height:8px;width:100px">
                                    <div class="progress-bar bg-pink" style="width:{{ $taxa }}%"></div>
                                </div>
                                <small>{{ $taxa }}%</small>
                            </td>
                            <td class="fw-bold text-pink">R$ {{ number_format($m['faturamento'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum dado no período</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
