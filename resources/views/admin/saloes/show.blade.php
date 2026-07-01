@extends('layouts.app')

@section('title', $salao->nome)
@section('page-title', $salao->nome)

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body text-center p-4">
                <img src="{{ $salao->logo_url }}" width="90" height="90" class="rounded-circle mb-3">
                <h4 class="fw-bold">{{ $salao->nome }}</h4>
                @if($salao->cidade)
                    <p class="text-muted"><i class="fas fa-map-marker-alt text-pink me-1"></i>{{ $salao->cidade }}, {{ $salao->estado }}</p>
                @endif
                <span class="badge {{ $salao->ativo ? 'bg-success' : 'bg-danger' }} mb-3">
                    {{ $salao->ativo ? 'Ativo' : 'Inativo' }}
                </span>
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.saloes.edit', $salao) }}" class="btn btn-pink">
                        <i class="fas fa-edit me-2"></i> Editar
                    </a>
                    <a href="{{ route('public.salao', $salao->slug) }}" class="btn btn-outline-pink" target="_blank">
                        <i class="fas fa-external-link-alt me-2"></i> Ver Página Pública
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6 class="mb-0 fw-bold">Informações de Contato</h6></div>
            <div class="card-body">
                @if($salao->telefone)
                    <div class="mb-2"><i class="fas fa-phone text-pink me-2"></i>{{ $salao->telefone }}</div>
                @endif
                @if($salao->whatsapp)
                    <div class="mb-2"><i class="fab fa-whatsapp text-green me-2"></i>{{ $salao->whatsapp }}</div>
                @endif
                @if($salao->email)
                    <div class="mb-2"><i class="fas fa-envelope text-pink me-2"></i>{{ $salao->email }}</div>
                @endif
                @if($salao->instagram)
                    <div class="mb-2"><i class="fab fa-instagram text-pink me-2"></i>@{{ $salao->instagram }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-value">{{ $agendamentosHoje }}</div>
                        <div class="stat-label">Agendamentos Hoje</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-value">{{ $salao->manicures->count() }}</div>
                        <div class="stat-label">Manicures</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-value">R$ {{ number_format($faturamentoMes, 2, ',', '.') }}</div>
                        <div class="stat-label">Faturamento do Mês</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <h6 class="mb-0 fw-bold">Equipe de Manicures</h6>
                <a href="{{ route('admin.manicures.create') }}" class="btn btn-sm btn-pink">
                    <i class="fas fa-plus me-1"></i> Add Manicure
                </a>
            </div>
            <div class="card-body p-0">
                @forelse($salao->manicures as $m)
                    <div class="p-3 {{ !$loop->last ? 'border-bottom' : '' }} d-flex align-items-center">
                        <img src="{{ $m->foto_url }}" width="40" height="40" class="rounded-circle me-3">
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $m->nome }}</div>
                            <small class="text-muted">{{ $m->email }}</small>
                        </div>
                        <span class="badge {{ $m->ativo ? 'bg-success' : 'bg-secondary' }}">
                            {{ $m->ativo ? 'Ativa' : 'Inativa' }}
                        </span>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">Nenhuma manicure cadastrada</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0 fw-bold">Horários de Funcionamento</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($salao->horarios as $h)
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between p-2 border rounded {{ $h->ativo ? '' : 'bg-light text-muted' }}">
                                <span>{{ $h->dia_nome }}</span>
                                <span class="{{ $h->ativo ? 'text-pink fw-semibold' : '' }}">
                                    {{ $h->ativo ? $h->hora_abertura . ' - ' . $h->hora_fechamento : 'Fechado' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
