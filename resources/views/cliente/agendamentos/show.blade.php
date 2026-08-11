@extends('layouts.app')

@section('title', 'Meu Agendamento #' . $agendamento->id)
@section('page-title', 'Detalhes do Agendamento')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Agendamento #{{ $agendamento->id }}</h5>
                <span class="badge bg-{{ $agendamento->status_color }} fs-6">{{ $agendamento->status_label }}</span>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="text-muted small">Salão</label>
                            <p class="fw-semibold mb-0">{{ $agendamento->salao?->nome }}</p>
                            <small class="text-muted">{{ $agendamento->salao?->endereco_completo }}</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="text-muted small">Manicure</label>
                            <div class="d-flex align-items-center">
                                <img src="{{ $agendamento->manicure?->foto_url }}" width="36" height="36" class="rounded-circle me-2">
                                <p class="fw-semibold mb-0">{{ $agendamento->manicure?->nome }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="text-muted small">Data e Hora</label>
                            <p class="fw-semibold mb-0">
                                <i class="fas fa-calendar text-pink me-1"></i>
                                {{ $agendamento->data_hora_inicio->format('d/m/Y') }}
                            </p>
                            <p class="fw-semibold mb-0">
                                <i class="fas fa-clock text-pink me-1"></i>
                                {{ $agendamento->data_hora_inicio->format('H:i') }} -
                                {{ $agendamento->data_hora_fim->format('H:i') }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-group">
                            <label class="text-muted small">Serviços</label>
                            @foreach($agendamento->servicos as $s)
                                <div class="d-flex justify-content-between">
                                    <span>{{ $s->nome }}</span>
                                    <span class="text-pink fw-semibold">R$ {{ number_format($s->pivot->preco, 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                            @if($agendamento->valor_desconto > 0)
                                <div class="d-flex justify-content-between text-success">
                                    <span>Desconto</span>
                                    <span>- R$ {{ number_format($agendamento->valor_desconto, 2, ',', '.') }}</span>
                                </div>
                            @endif
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total</span>
                                <span class="text-pink">R$ {{ number_format($agendamento->valor_total - $agendamento->valor_desconto, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    @if($agendamento->observacoes)
                        <div class="col-12">
                            <div class="info-group">
                                <label class="text-muted small">Observações</label>
                                <p class="mb-0">{{ $agendamento->observacoes }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Avaliação --}}
        @if($agendamento->status === 'concluido')
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-star text-pink me-2"></i> Avaliação
                    </h5>
                </div>
                <div class="card-body">
                    @if($agendamento->avaliacao)
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-warning fs-3">{{ $agendamento->avaliacao->estrelas }}</div>
                            <div>
                                <div class="fw-bold">{{ $agendamento->avaliacao->nota }}/5</div>
                                @if($agendamento->avaliacao->comentario)
                                    <p class="mb-0 fst-italic">"{{ $agendamento->avaliacao->comentario }}"</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-muted mb-3">Como foi seu atendimento? Deixe sua avaliação!</p>
                        <form method="POST" action="{{ route('cliente.agendamentos.avaliar', $agendamento) }}" id="formAvaliacao">
                            @csrf
                            <div class="mb-3">
                                <div class="star-rating d-flex gap-2 mb-2" id="starRating">
                                    @for($i = 1; $i <= 5; $i++)
                                        <label class="star-label cursor-pointer fs-2" data-nota="{{ $i }}">
                                            <i class="far fa-star text-warning"></i>
                                        </label>
                                    @endfor
                                </div>
                                <input type="hidden" name="nota" id="notaValue" required>
                                <small class="text-muted d-block" id="notaHelp">Clique nas estrelas para avaliar</small>
                            </div>
                            <div class="mb-3">
                                <textarea name="comentario" class="form-control" rows="3" maxlength="500"
                                          placeholder="Conte-nos sobre sua experiência..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-pink" id="btnAvaliar" disabled>
                                <i class="fas fa-paper-plane me-2"></i> Enviar Avaliação
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        {{-- Ações --}}
        <div class="d-flex gap-2 flex-wrap">
            @if($agendamento->precisaSinal())
                <a href="{{ route('cliente.agendamentos.sinal', $agendamento) }}" class="btn btn-success">
                    <i class="fas fa-qrcode me-2"></i> Pagar sinal
                </a>
            @endif
            @if($agendamento->precisaPagamentoTotal())
                <a href="{{ route('cliente.agendamentos.pagamento', $agendamento) }}" class="btn btn-success">
                    <i class="fas fa-qrcode me-2"></i>
                    {{ $agendamento->sinalPago() ? 'Pagar restante' : 'Pagar valor total' }}
                </a>
            @endif
            @if(!in_array($agendamento->status, ['cancelado', 'nao_compareceu']))
                <a href="{{ route('cliente.agendamentos.ical', $agendamento) }}" class="btn btn-outline-pink">
                    <i class="fas fa-calendar-plus me-2"></i> Baixar .ics
                </a>
                <a href="{{ $googleCalendarUrl }}" class="btn btn-outline-pink" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-google me-2"></i> Google Calendar
                </a>
            @endif
            @if($agendamento->podeSerReagendado())
                <a href="{{ route('cliente.agendamentos.reagendar.form', $agendamento) }}" class="btn btn-pink">
                    <i class="fas fa-clock-rotate-left me-2"></i> Remarcar
                </a>
            @endif
            @if($agendamento->podeSerCancelado())
                <form method="POST" action="{{ route('cliente.agendamentos.cancelar', $agendamento) }}"
                      data-confirm="Cancelar agendamento?" data-confirm-ok="Cancelar agendamento">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-times me-2"></i> Cancelar Agendamento
                    </button>
                </form>
            @endif
            <a href="{{ route('cliente.agendamentos.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Voltar
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const stars = document.querySelectorAll('.star-label');
const notaInput = document.getElementById('notaValue');
const btnAvaliar = document.getElementById('btnAvaliar');
const notaHelp = document.getElementById('notaHelp');
const labels = ['', 'Péssimo', 'Ruim', 'Regular', 'Bom', 'Excelente!'];

stars.forEach((label) => {
    label.addEventListener('click', function() {
        const nota = parseInt(this.dataset.nota);
        notaInput.value = nota;
        stars.forEach((s, i) => {
            s.querySelector('i').className = i < nota ? 'fas fa-star text-warning' : 'far fa-star text-warning';
        });
        notaHelp.textContent = labels[nota];
        notaHelp.className = 'text-pink fw-semibold d-block';
        btnAvaliar.disabled = false;
    });

    label.addEventListener('mouseenter', function() {
        const nota = parseInt(this.dataset.nota);
        stars.forEach((s, i) => {
            if (i < nota) s.querySelector('i').className = 'fas fa-star text-warning';
        });
    });
});

document.getElementById('starRating').addEventListener('mouseleave', function() {
    const nota = parseInt(notaInput.value) || 0;
    stars.forEach((s, i) => {
        s.querySelector('i').className = i < nota ? 'fas fa-star text-warning' : 'far fa-star text-warning';
    });
});
</script>
@endpush
