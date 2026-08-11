@extends('layouts.app')

@section('title', 'Programa de fidelidade')
@section('page-title', 'Fidelidade')

@section('content')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm bg-pink-gradient text-white">
            <div class="card-body p-4 text-center">
                <i class="fas fa-gem fs-1 mb-2"></i>
                <div class="display-5 fw-bold">{{ $pontos }}</div>
                <div class="opacity-75 mb-1">pontos disponíveis</div>
                <div class="mb-3">
                    <span class="badge bg-white text-pink">Nível {{ $nivel['nome'] ?? 'Bronze' }}</span>
                    @if(($nivel['multiplicador'] ?? 1) > 1)
                        <span class="badge bg-white bg-opacity-25">×{{ number_format($nivel['multiplicador'], 2) }} pts</span>
                    @endif
                </div>

                <div class="text-start bg-white bg-opacity-10 rounded-3 p-3">
                    <div class="d-flex justify-content-between align-items-center small mb-2">
                        @if($podeResgatar)
                            <span class="fw-semibold">
                                <i class="fas fa-check-circle me-1"></i>
                                Pronto para resgatar{{ $blocosDisponiveis > 1 ? " ({$blocosDisponiveis}×)" : '' }}
                            </span>
                            <span>{{ min($pontos, $pontosPorBloco) }}/{{ $pontosPorBloco }}</span>
                        @else
                            <span class="fw-semibold">Progresso para o próximo desconto</span>
                            <span>{{ $pontos }}/{{ $pontosPorBloco }}</span>
                        @endif
                    </div>
                    <div class="progress bg-white bg-opacity-25" style="height:10px" role="progressbar"
                         aria-valuenow="{{ $progressoPct }}" aria-valuemin="0" aria-valuemax="100"
                         aria-label="Progresso de pontos para o próximo desconto">
                        <div class="progress-bar bg-white" style="width: {{ $progressoPct }}%"></div>
                    </div>
                    <p class="small mb-0 mt-2 opacity-75">
                        @if($podeResgatar)
                            Troque {{ $pontosPorBloco }} pontos por R$ {{ number_format($valorPorBloco, 2, ',', '.') }} de desconto.
                        @else
                            Faltam <strong>{{ $pontosParaProximo }}</strong>
                            {{ $pontosParaProximo === 1 ? 'ponto' : 'pontos' }}
                            para um cupom de R$ {{ number_format($valorPorBloco, 2, ',', '.') }}.
                        @endif
                    </p>
                </div>
            </div>
        </div>

        @if($indicacaoAtiva && $codigoIndicacao)
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-2">
                    <i class="fas fa-user-friends me-1 text-pink"></i>
                    Indique amigas
                </h6>
                <p class="text-muted small mb-3">
                    Compartilhe seu código. Quando a pessoa indicada concluir o primeiro atendimento, você ganha uma recompensa.
                </p>
                <div class="bg-light rounded-3 p-3 text-center">
                    <div class="text-muted small mb-1">Seu código</div>
                    <div class="fs-4 fw-bold font-monospace text-pink letter-spacing-1" id="codigo-indicacao">{{ $codigoIndicacao }}</div>
                </div>
            </div>
        </div>
        @endif

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

                <form method="POST" action="{{ route('cliente.fidelidade.resgatar') }}" class="row g-2">
                    @csrf
                    <input type="hidden" name="blocos" value="1">
                    <div class="col-12">
                        <button type="submit" class="btn btn-pink w-100 py-2" @disabled(!$podeResgatar)>
                            <i class="fas fa-gift me-2"></i>
                            Resgatar {{ $pontosPorBloco }} pontos
                        </button>
                    </div>
                </form>

                @if($podeResgatar)
                    <p class="text-muted small text-center mt-3 mb-0">
                        O cupom fica disponível por 30 dias e pode ser usado no próximo agendamento.
                    </p>
                @else
                    <div class="text-center mt-3">
                        <p class="text-muted small mb-2">
                            Você ainda não tem pontos suficientes. Pontos entram após cada atendimento concluído.
                        </p>
                        <a href="{{ route('cliente.agendamentos.create') }}" class="btn btn-outline-pink btn-sm w-100 py-2">
                            <i class="fas fa-calendar-plus me-1"></i> Agendar e juntar pontos
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Extrato de pontos</h6></div>
            <div class="card-body">
                @forelse($historico as $h)
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-sm-between gap-2 list-item p-3 rounded-3">
                        <div class="min-w-0">
                            <div class="fw-semibold text-break">{{ $h->descricao ?? ucfirst($h->tipo) }}</div>
                            <small class="text-muted">{{ $h->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <span class="fw-bold {{ $h->pontos < 0 ? 'text-danger' : 'text-green' }}">
                            {{ $h->pontos > 0 ? '+' : '' }}{{ $h->pontos }}
                        </span>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-gem"></i></div>
                        <h6 class="fw-bold">Nenhum ponto ainda</h6>
                        <p class="mb-3">Após os atendimentos, seus pontos e o histórico aparecem aqui.</p>
                        <a href="{{ route('cliente.agendamentos.create') }}" class="btn btn-pink">
                            <i class="fas fa-calendar-plus me-2"></i> Fazer um agendamento
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
