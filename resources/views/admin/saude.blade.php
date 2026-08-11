@extends('layouts.app')

@section('title', 'Saúde do sistema')
@section('page-title', 'Saúde do sistema')

@section('content')
<div class="row g-4 mb-4">
    @foreach($checks as $key => $check)
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon {{ $check['ok'] ? 'bg-green-light' : 'bg-pink-light' }}">
                    <i class="fas {{ $check['ok'] ? 'fa-check text-green' : 'fa-triangle-exclamation text-pink' }}"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value" style="font-size:1.1rem">{{ $check['ok'] ? 'OK' : 'Atenção' }}</div>
                    <div class="stat-label">{{ $check['label'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-heart-pulse text-pink me-2"></i> Checks
                </h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($checks as $check)
                        <li class="list-group-item d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-semibold">{{ $check['label'] }}</div>
                                <div class="text-muted small">{{ $check['detail'] }}</div>
                            </div>
                            <span class="badge {{ $check['ok'] ? 'bg-success' : 'bg-danger' }}">
                                {{ $check['ok'] ? 'ok' : 'falha' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bomb text-pink me-2"></i> Failed jobs recentes
                </h5>
            </div>
            <div class="card-body p-0">
                @php $failed = $checks['failed_jobs']['items'] ?? []; @endphp
                @forelse($failed as $job)
                    <div class="list-item d-flex align-items-center p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">#{{ $job['id'] }} · {{ $job['queue'] }}</div>
                            <div class="text-muted small">{{ $job['failed_at'] ?? '—' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-muted">Nenhum failed job recente.</div>
                @endforelse
            </div>
        </div>

        <p class="text-muted small mt-3 mb-0">
            Endpoint Laravel <code>/up</code> continua disponível para probes externos.
            Backup: <code>php artisan manicure:backup</code> — ver docs/PRODUCAO.md.
        </p>
    </div>
</div>
@endsection
