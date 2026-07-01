@extends('layouts.app')

@section('title', 'Clientes')
@section('page-title', 'Clientes do salão')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0"><i class="fas fa-users text-pink me-2"></i>Clientes</h5>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="search" name="search" class="form-control form-control-sm"
                       value="{{ request('search') }}" placeholder="Buscar por nome, e-mail, telefone...">
                <button class="btn btn-outline-pink btn-sm"><i class="fas fa-search"></i></button>
            </form>
            <a href="{{ route('dono.clientes.create') }}" class="btn btn-pink btn-sm">
                <i class="fas fa-plus me-1"></i>Novo cliente
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Contato</th>
                        <th>Visitas</th>
                        <th>Total gasto</th>
                        <th>Pontos</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $c)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                                         style="width:38px;height:38px;background:var(--gradient-pink)">
                                        {{ strtoupper(substr($c->nome, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $c->nome }}</div>
                                        @if($c->aniversario_hoje)
                                            <small class="text-pink"><i class="fas fa-cake-candles"></i> Aniversariante hoje!</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($c->telefone)
                                    <div class="small"><i class="fas fa-phone text-muted me-1"></i>{{ $c->telefone }}</div>
                                @endif
                                @if($c->email)
                                    <small class="text-muted"><i class="fas fa-envelope me-1"></i>{{ $c->email }}</small>
                                @endif
                            </td>
                            <td><span class="badge bg-info">{{ $c->total_visitas }}</span></td>
                            <td class="fw-bold text-pink">R$ {{ number_format($c->total_gasto, 2, ',', '.') }}</td>
                            <td><span class="badge bg-warning">{{ $c->pontos_fidelidade }} ⭐</span></td>
                            <td>
                                <span class="badge {{ $c->ativo ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $c->ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('dono.clientes.show', $c) }}" class="btn btn-sm btn-ghost" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('dono.clientes.edit', $c) }}" class="btn btn-sm btn-ghost" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="fas fa-user-friends"></i></div>
                                <h6 class="fw-bold">Nenhum cliente {{ request('search') ? 'encontrado' : 'cadastrado' }}</h6>
                                @if(!request('search'))
                                    <p>Comece cadastrando seu primeiro cliente.</p>
                                    <a href="{{ route('dono.clientes.create') }}" class="btn btn-pink mt-2">
                                        <i class="fas fa-plus me-1"></i>Cadastrar cliente
                                    </a>
                                @endif
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($clientes->hasPages())
        <div class="card-footer">{{ $clientes->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
