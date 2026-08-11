@extends('layouts.app')

@section('title', 'Agendamentos')
@section('page-title', 'Agendamentos')

@section('content')
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0">
            <i class="fas fa-calendar-check text-pink me-2"></i> Todos os Agendamentos
        </h5>
        <div class="d-flex gap-2">
            <a href="{{ route('dono.agendamentos.semana') }}" class="btn btn-outline-pink">
                <i class="fas fa-calendar-week me-1"></i> Semana
            </a>
            <a href="{{ route('dono.agendamentos.create') }}" class="btn btn-pink">
                <i class="fas fa-plus me-1"></i> Novo Agendamento
            </a>
        </div>
    </div>

    <div class="card-body border-bottom">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Data</label>
                <input type="date" name="data" class="form-control" value="{{ request('data') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Manicure</label>
                <select name="manicure_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach($manicures as $m)
                        <option value="{{ $m->id }}" {{ request('manicure_id') == $m->id ? 'selected' : '' }}>{{ $m->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    @foreach(\App\Models\Agendamento::STATUS_LABELS as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-pink flex-grow-1">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('dono.agendamentos.ical', request()->only(['data', 'de', 'ate', 'manicure_id', 'status'])) }}"
                       class="btn btn-outline-pink" title="Exportar .ics (dia filtrado ou hoje)">
                        <i class="fas fa-calendar-plus"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Manicure</th>
                        <th>Serviços</th>
                        <th>Data/Hora</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agendamentos as $ag)
                        <tr>
                            <td>{{ $ag->id }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span>{{ $ag->nome_cliente_exibido }}</span>
                                    <x-badge-no-show :cliente="$ag->cliente" />
                                </div>
                            </td>
                            <td>{{ $ag->manicure?->nome }}</td>
                            <td>
                                <small>{{ $ag->servicos->pluck('nome')->implode(', ') }}</small>
                            </td>
                            <td>{{ $ag->data_hora_inicio->format('d/m/Y H:i') }}</td>
                            <td>R$ {{ number_format($ag->valor_total - $ag->valor_desconto, 2, ',', '.') }}</td>
                            <td>
                                <span class="badge bg-{{ $ag->status_color }}">{{ $ag->status_label }}</span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-ghost" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a href="{{ route('dono.agendamentos.show', $ag) }}" class="dropdown-item">
                                                <i class="fas fa-eye me-2"></i> Ver Detalhes
                                            </a>
                                        </li>
                                        @if($ag->podeSerCancelado())
                                            <li>
                                                <form method="POST" action="{{ route('dono.agendamentos.destroy', $ag) }}" data-confirm="Cancelar agendamento?" data-confirm-ok="Cancelar agendamento">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-times me-2"></i> Cancelar
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-times fa-2x mb-3 d-block"></i>
                                Nenhum agendamento encontrado
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($agendamentos->hasPages())
        <div class="card-footer">
            {{ $agendamentos->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
