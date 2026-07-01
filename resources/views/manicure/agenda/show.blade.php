@extends('layouts.app')

@section('title', 'Detalhes do Agendamento')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">
        <i class="fa-solid fa-calendar-check me-2 text-pink"></i>Agendamento #{{ $agendamento->id }}
    </h2>
    <a href="{{ route('manicure.agenda.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Minha Agenda
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        {{-- Status Card --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="badge fs-6 px-3 py-2"
                          style="background-color: {{ $agendamento->status_color }}">
                        <i class="fa-solid fa-circle me-1" style="font-size:8px"></i>
                        {{ $agendamento->status_label }}
                    </span>
                    <span class="text-muted small">
                        <i class="fa-solid fa-clock me-1"></i>
                        {{ $agendamento->data_hora_inicio->format('d/m/Y \à\s H:i') }}
                        —
                        {{ $agendamento->data_hora_fim->format('H:i') }}
                        ({{ $agendamento->duracao_minutos }} min)
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small fw-semibold text-uppercase">Cliente</label>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <i class="fa-solid fa-user text-pink"></i>
                            <span class="fw-semibold">{{ $agendamento->nome_cliente_exibido }}</span>
                        </div>
                        @if($agendamento->cliente?->telefone ?? $agendamento->telefone_cliente)
                        <div class="text-muted small mt-1">
                            <i class="fa-solid fa-phone me-1"></i>
                            {{ $agendamento->cliente?->telefone ?? $agendamento->telefone_cliente }}
                        </div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="text-muted small fw-semibold text-uppercase">Origem</label>
                        <div class="mt-1">
                            <span class="badge bg-light text-dark border">
                                @switch($agendamento->origem)
                                    @case('app') <i class="fa-solid fa-mobile me-1"></i>App @break
                                    @case('site') <i class="fa-solid fa-globe me-1"></i>Site @break
                                    @case('telefone') <i class="fa-solid fa-phone me-1"></i>Telefone @break
                                    @default <i class="fa-solid fa-store me-1"></i>Presencial
                                @endswitch
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Serviços --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-hand-sparkles me-2 text-pink"></i>Serviços</h6>
            </div>
            <div class="list-group list-group-flush">
                @foreach($agendamento->servicos as $servico)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold">{{ $servico->nome }}</span>
                        <span class="text-muted small ms-2">
                            <i class="fa-regular fa-clock me-1"></i>{{ $servico->duracao }} min
                        </span>
                        @if($servico->combo)
                            <span class="badge bg-warning text-dark ms-1">Combo</span>
                        @endif
                    </div>
                    <span class="fw-bold text-pink">
                        R$ {{ number_format($servico->pivot->preco ?? $servico->preco, 2, ',', '.') }}
                    </span>
                </div>
                @endforeach
            </div>
            <div class="card-footer bg-light d-flex justify-content-between fw-bold">
                <span>Total dos serviços</span>
                <span class="text-pink fs-5">R$ {{ number_format($agendamento->valor_total, 2, ',', '.') }}</span>
            </div>
        </div>

        {{-- Observações --}}
        @if($agendamento->observacoes)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-note-sticky me-2 text-pink"></i>Observações</h6>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $agendamento->observacoes }}</p>
            </div>
        </div>
        @endif

        {{-- Pagamento (se finalizado) --}}
        @if($agendamento->comanda)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-receipt me-2 text-pink"></i>Comanda #{{ $agendamento->comanda->id }}</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6 col-md-3 text-center">
                        <div class="text-muted small">Subtotal</div>
                        <div class="fw-bold">R$ {{ number_format($agendamento->comanda->valor_servicos ?? $agendamento->valor_total, 2, ',', '.') }}</div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div class="text-muted small">Desconto</div>
                        <div class="fw-bold text-danger">- R$ {{ number_format($agendamento->valor_desconto, 2, ',', '.') }}</div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div class="text-muted small">Total Pago</div>
                        <div class="fw-bold text-success fs-5">R$ {{ number_format($agendamento->comanda->total_pago, 2, ',', '.') }}</div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div class="text-muted small">Forma</div>
                        <div class="fw-bold">
                            @if($agendamento->pagamentos->first())
                                {{ ucfirst($agendamento->pagamentos->first()->forma) }}
                            @else — @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        {{-- Ações --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-gears me-2 text-pink"></i>Ações</h6>
            </div>
            <div class="card-body d-grid gap-2">
                @if(in_array($agendamento->status, ['aguardando', 'confirmado']))
                    <form action="{{ route('manicure.agenda.status', $agendamento) }}" method="POST"
                          data-confirm="Iniciar atendimento?" data-confirm-message="Confirmar o início do atendimento agora?" data-confirm-type="info" data-confirm-ok="Iniciar">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="em_andamento">
                        <button type="submit" class="btn btn-pink w-100">
                            <i class="fa-solid fa-play me-1"></i>Iniciar Atendimento
                        </button>
                    </form>
                @endif

                @if($agendamento->status === 'em_andamento')
                    <form action="{{ route('manicure.agenda.status', $agendamento) }}" method="POST"
                          data-confirm="Finalizar atendimento?" data-confirm-message="Confirmar a conclusão do atendimento?" data-confirm-type="success" data-confirm-ok="Finalizar">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="concluido">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fa-solid fa-flag-checkered me-1"></i>Finalizar Atendimento
                        </button>
                    </form>
                @endif

                @if($agendamento->podeSerCancelado())
                    <form action="{{ route('manicure.agenda.status', $agendamento) }}" method="POST"
                          data-confirm="Marcar como falta?" data-confirm-message="Confirmar que o cliente não compareceu?" data-confirm-type="warning" data-confirm-ok="Marcar falta">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="nao_compareceu">
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fa-solid fa-ban me-1"></i>Não Compareceu
                        </button>
                    </form>
                @endif

                <a href="{{ route('manicure.agenda.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-calendar me-1"></i>Ver Agenda
                </a>
            </div>
        </div>

        {{-- Info Cliente --}}
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-user me-2 text-pink"></i>Perfil do Cliente</h6>
            </div>
            <div class="card-body">
                @if($agendamento->cliente)
                    <div class="text-center mb-3">
                        <div class="avatar-circle mx-auto mb-2">
                            {{ strtoupper(substr($agendamento->cliente->nome, 0, 1)) }}
                        </div>
                        <h6 class="fw-bold mb-0">{{ $agendamento->cliente->nome }}</h6>
                    </div>
                    <ul class="list-unstyled small mb-0">
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Visitas</span>
                            <strong>{{ $agendamento->cliente->total_visitas }}</strong>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Total gasto</span>
                            <strong class="text-pink">R$ {{ number_format($agendamento->cliente->total_gasto, 2, ',', '.') }}</strong>
                        </li>
                        <li class="d-flex justify-content-between py-1">
                            <span class="text-muted">Pontos fidelidade</span>
                            <strong>{{ $agendamento->cliente->pontos_fidelidade }} pts</strong>
                        </li>
                    </ul>
                @else
                    <p class="text-muted small mb-0">
                        <i class="fa-solid fa-user-slash me-1"></i>
                        Cliente avulso: {{ $agendamento->cliente_nome ?? '—' }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

<style>
.avatar-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--pink);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.2rem;
}
</style>
