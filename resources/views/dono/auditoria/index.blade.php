@extends('layouts.app')

@section('title', 'Auditoria')
@section('page-title', 'Auditoria')

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Ação</label>
                <input type="text" name="action" value="{{ request('action') }}" class="form-control" placeholder="ex.: caixa.fechado">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Usuário</label>
                <select name="user_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach($usuarios as $u)
                        <option value="{{ $u->id }}" @selected((string) request('user_id') === (string) $u->id)>
                            {{ $u->name }} ({{ $u->role }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">De</label>
                <input type="date" name="de" value="{{ request('de') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Até</label>
                <input type="date" name="ate" value="{{ request('ate') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-pink w-100"><i class="fas fa-filter me-1"></i>Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fas fa-clipboard-list text-pink me-2"></i>Logs de auditoria</h5>
        <span class="text-muted small">Somente leitura</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Quando</th>
                        <th>Usuário</th>
                        <th>Ação</th>
                        <th>Recurso</th>
                        <th>IP</th>
                        <th>Meta</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap small">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->user?->name ?? '—' }}</td>
                            <td><code>{{ $log->action }}</code></td>
                            <td class="small text-muted">
                                @if($log->auditable_type)
                                    {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="small">{{ $log->ip ?? '—' }}</td>
                            <td class="small">
                                @if($log->meta)
                                    <details>
                                        <summary class="text-muted">ver</summary>
                                        <pre class="mb-0 small" style="max-width:280px;white-space:pre-wrap">{{ json_encode($log->meta, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre>
                                    </details>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">Nenhum evento de auditoria encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
        <div class="card-footer">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
