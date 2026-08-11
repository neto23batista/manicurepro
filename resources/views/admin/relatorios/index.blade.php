@extends('layouts.app')

@section('title', 'Relatórios')
@section('page-title', 'Relatórios')

@section('content')
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="card-title mb-0"><i class="fas fa-filter text-pink me-2"></i> Filtros</h5>
        <div class="small text-muted">
            <strong>{{ $salaoNome }}</strong>
            · {{ $dataInicio->format('d/m/Y') }} – {{ $dataFim->format('d/m/Y') }}
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Salão</label>
                <select name="salao_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach($saloes as $s)
                        <option value="{{ $s->id }}" {{ (string) $salaoId === (string) $s->id ? 'selected' : '' }}>{{ $s->nome }}</option>
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
                <a href="{{ route('admin.relatorios.pdf', request()->all()) }}" class="btn btn-outline-danger" title="Exportar PDF">
                    <i class="fas fa-file-pdf"></i>
                </a>
                <a href="{{ route('admin.relatorios.csv', request()->all()) }}" class="btn btn-outline-success" title="Exportar CSV">
                    <i class="fas fa-file-csv"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon bg-blue-light"><i class="fas fa-calendar text-blue"></i></div>
            <div class="stat-info">
                <div class="stat-value">{{ $resumo['total'] }}</div>
                <div class="stat-label">Agendamentos</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon bg-green-light"><i class="fas fa-check text-green"></i></div>
            <div class="stat-info">
                <div class="stat-value">{{ $resumo['concluidos'] }}</div>
                <div class="stat-label">Concluídos</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon bg-red-light"><i class="fas fa-times text-danger"></i></div>
            <div class="stat-info">
                <div class="stat-value">{{ $resumo['cancelados'] }}</div>
                <div class="stat-label">Cancelados</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon bg-pink-light"><i class="fas fa-money-bill text-pink"></i></div>
            <div class="stat-info">
                <div class="stat-value">@money($resumo['liquido'])</div>
                <div class="stat-label">Faturamento líquido</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon bg-blue-light"><i class="fas fa-receipt text-blue"></i></div>
            <div class="stat-info">
                <div class="stat-value">@money($resumo['ticket_medio'])</div>
                <div class="stat-label">Ticket médio</div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon bg-green-light"><i class="fas fa-hand-holding-usd text-green"></i></div>
            <div class="stat-info">
                <div class="stat-value">@money($resumo['comissoes'])</div>
                <div class="stat-label">Comissões</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-hand-sparkles text-pink me-2"></i> Comissão por profissional</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Profissional</th>
                        <th class="text-center">Atend.</th>
                        <th class="text-center">Concl.</th>
                        <th class="text-center">Conclusão</th>
                        <th class="text-end">Base</th>
                        <th class="text-center">Taxa</th>
                        <th class="text-end">Comissão</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($porManicure as $m)
                        <tr>
                            <td class="fw-semibold">{{ $m['nome'] }}</td>
                            <td class="text-center">{{ $m['total'] }}</td>
                            <td class="text-center">{{ $m['concluidos'] }}</td>
                            <td class="text-center">
                                <div class="progress mx-auto" style="height:8px;width:100px">
                                    <div class="progress-bar bg-pink" style="width:{{ $m['taxa'] }}%"></div>
                                </div>
                                <small>{{ $m['taxa'] }}%</small>
                            </td>
                            <td class="text-end">@money($m['base'])</td>
                            <td class="text-center">
                                @if($m['taxa_comissao'] > 0)
                                    {{ rtrim(rtrim(number_format($m['taxa_comissao'], 2, ',', '.'), '0'), ',') }}%
                                @else
                                    <span class="text-muted" title="Defina a comissão no cadastro do profissional">—</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-pink">@money($m['comissao'])</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">Nenhum dado no período</td></tr>
                    @endforelse
                </tbody>
                @if($porManicure->isNotEmpty())
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4">Total</td>
                            <td class="text-end">@money($resumo['base_comissao'])</td>
                            <td></td>
                            <td class="text-end">@money($resumo['comissoes'])</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        <p class="text-muted small p-3 mb-0">
            <i class="fas fa-circle-info me-1"></i>
            Base de comissão = serviços líquidos (valor − desconto) dos atendimentos concluídos. Produtos não entram na base.
        </p>
    </div>
</div>
@endsection
