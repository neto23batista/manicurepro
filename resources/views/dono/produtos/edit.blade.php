@extends('layouts.app')

@section('title', 'Editar produto')
@section('page-title', 'Editar produto')

@section('content')
<div class="row g-4 justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-box text-pink me-2"></i>{{ $produto->nome }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dono.produtos.update', $produto) }}">
                    @csrf @method('PUT')
                    @include('dono.produtos._form', ['produto' => $produto])
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-pink"><i class="fas fa-save me-1"></i>Salvar alterações</button>
                        <a href="{{ route('dono.produtos.index') }}" class="btn btn-outline-secondary">Voltar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Estoque atual</h6>
                <span class="badge {{ $produto->estoque_baixo ? 'bg-danger' : 'bg-success' }}">
                    {{ rtrim(rtrim(number_format($produto->estoque_atual, 3, ',', '.'), '0'), ',') }} {{ $produto->unidade }}
                </span>
            </div>
            <div class="card-body">
                <h6 class="text-muted small text-uppercase mb-3">Últimas movimentações</h6>
                @forelse($movimentacoes as $m)
                    <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>
                            <span class="badge {{ in_array($m->tipo, ['entrada', 'devolucao'], true) ? 'bg-success' : (in_array($m->tipo, ['saida', 'perda', 'consumo_interno'], true) ? 'bg-danger' : 'bg-secondary') }}">
                                {{ str_replace('_', ' ', ucfirst($m->tipo)) }}
                            </span>
                            <small class="text-muted ms-1">{{ $m->motivo ?: '—' }}</small>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">{{ rtrim(rtrim(number_format($m->quantidade, 3, ',', '.'), '0'), ',') }}</div>
                            <small class="text-muted">{{ $m->created_at->format('d/m H:i') }}</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Nenhuma movimentação ainda.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
