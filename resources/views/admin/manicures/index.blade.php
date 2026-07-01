@extends('layouts.app')

@section('title', 'Manicures')
@section('page-title', 'Gerenciar Manicures')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fas fa-hand-sparkles text-pink me-2"></i> Todas as Manicures</h5>
        <a href="{{ route('admin.manicures.create') }}" class="btn btn-pink">
            <i class="fas fa-plus me-1"></i> Nova Manicure
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Profissional</th>
                        <th>Salão</th>
                        <th>Comissão</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($manicures as $m)
                        <tr>
                            <td>{{ $m->id }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $m->foto_url }}" width="36" height="36" class="rounded-circle">
                                    <div>
                                        <div class="fw-semibold">{{ $m->nome }}</div>
                                        <small class="text-muted">{{ $m->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $m->salao?->nome }}</td>
                            <td>{{ $m->comissao }}%</td>
                            <td>
                                <span class="badge {{ $m->ativo ? 'bg-success' : 'bg-danger' }}">
                                    {{ $m->ativo ? 'Ativa' : 'Inativa' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.manicures.edit', $m) }}" class="btn btn-sm btn-ghost">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">Nenhuma manicure cadastrada</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($manicures->hasPages())
        <div class="card-footer">{{ $manicures->links() }}</div>
    @endif
</div>
@endsection
