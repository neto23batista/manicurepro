@extends('layouts.app')

@section('title', 'Programa de fidelidade')
@section('page-title', 'Fidelidade')

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm bg-pink-gradient text-white">
            <div class="card-body p-4 text-center">
                <i class="fas fa-gem fs-1 mb-2"></i>
                <div class="display-5 fw-bold">{{ $cliente?->pontos_fidelidade ?? 0 }}</div>
                <div class="opacity-75">pontos disponíveis</div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-2">Resgatar desconto</h6>
                <p class="text-muted small mb-3">
                    A cada <strong>{{ $pontosPorBloco }} pontos</strong> você troca por um cupom de
                    <strong>R$ {{ number_format($valorPorBloco, 2, ',', '.') }}</strong> de desconto.
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger alert-permanent">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('cliente.fidelidade.resgatar') }}">
                    @csrf
                    <input type="hidden" name="blocos" value="1">
                    <button type="submit" class="btn btn-pink w-100" @disabled(!$podeResgatar)>
                        <i class="fas fa-gift me-2"></i>
                        Resgatar {{ $pontosPorBloco }} pontos
                    </button>
                </form>
                @unless($podeResgatar)
                    <p class="text-muted small text-center mt-2 mb-0">
                        Você ainda não tem pontos suficientes para resgatar.
                    </p>
                @endunless
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Extrato de pontos</h6></div>
            <div class="card-body">
                @forelse($historico as $h)
                    <div class="d-flex align-items-center justify-content-between list-item p-3 rounded-3">
                        <div>
                            <div class="fw-semibold">{{ $h->descricao ?? ucfirst($h->tipo) }}</div>
                            <small class="text-muted">{{ $h->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <span class="fw-bold {{ $h->pontos < 0 ? 'text-danger' : 'text-green' }}">
                            {{ $h->pontos > 0 ? '+' : '' }}{{ $h->pontos }}
                        </span>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-gem"></i></div>
                        <p>Seus pontos aparecerão aqui após os atendimentos.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
