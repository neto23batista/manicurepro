@extends('layouts.app')

@section('title', 'Novo Agendamento')
@section('page-title', 'Novo Agendamento')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus-circle text-pink me-2"></i> Novo Agendamento
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dono.agendamentos.store') }}" id="formAgendamento">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Manicure *</label>
                            <select name="manicure_id" class="form-select" required id="selectManicure">
                                <option value="">Selecione a manicure</option>
                                @foreach($manicures as $m)
                                    <option value="{{ $m->id }}">{{ $m->nome }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cliente (opcional)</label>
                            <select name="cliente_id" class="form-select">
                                <option value="">Cliente avulso</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c->id }}">{{ $c->nome }} ({{ $c->telefone }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Nome do Cliente (avulso)</label>
                            <input type="text" name="nome_cliente" class="form-control" value="{{ old('nome_cliente') }}" placeholder="Nome do cliente">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Serviços *</label>
                            <div class="servicos-grid row g-2">
                                @foreach($servicos as $s)
                                    <div class="col-md-4">
                                        <label class="servico-card w-100 cursor-pointer">
                                            <input type="checkbox" name="servico_ids[]" value="{{ $s->id }}"
                                                   data-preco="{{ $s->preco }}"
                                                   data-duracao="{{ $s->duracao }}"
                                                   data-servico-id="{{ $s->id }}"
                                                   class="servico-check visually-hidden">
                                            <div class="servico-option p-3 border rounded text-center">
                                                <div class="fw-semibold">{{ $s->nome }}</div>
                                                <div class="text-pink fw-bold servico-preco-label">{{ $s->preco_formatado }}</div>
                                                <small class="text-muted servico-duracao-label">{{ $s->duracao_formatada }}</small>
                                                @if($s->combo)
                                                    <span class="badge bg-pink ms-1">Combo</span>
                                                @endif
                                            </div>
                                        </label>
                                        @if($s->variacoesAtivas->isNotEmpty())
                                            <select name="servico_variacoes[{{ $s->id }}]"
                                                    class="form-select form-select-sm mt-1 variacao-select"
                                                    data-servico-id="{{ $s->id }}"
                                                    data-base-preco="{{ $s->preco }}"
                                                    data-base-duracao="{{ $s->duracao }}"
                                                    disabled>
                                                <option value="" data-preco="{{ $s->preco }}" data-duracao="{{ $s->duracao }}">Padrão</option>
                                                @foreach($s->variacoesAtivas as $v)
                                                    <option value="{{ $v->id }}" data-preco="{{ $v->preco }}" data-duracao="{{ $v->duracao }}">
                                                        {{ $v->nome }} — {{ $v->preco_formatado }} / {{ $v->duracao_formatada }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @error('servico_ids')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            @include('agendamentos._slots_picker', [
                                'dateLabel' => 'Data *',
                                'timeLabel' => 'Horário *',
                                'hint' => 'Selecione manicure, data e serviços para ver os horários.',
                                'dateColClass' => 'col-md-6',
                            ])
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="encaixe" value="0">
                                <input class="form-check-input" type="checkbox" name="encaixe" value="1" id="encaixeCheck"
                                       {{ old('encaixe') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="encaixeCheck">
                                    Encaixe (fora da grade)
                                </label>
                            </div>
                            <small class="text-muted d-block mb-2">
                                Só dono/atendente. Ignora folga/feriado/pausa, mas <strong>nunca</strong> sobrepõe outro agendamento.
                            </small>
                            <div id="encaixeHorario" class="row g-2 {{ old('encaixe') ? '' : 'd-none' }}">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="encaixeDatetime">Data e hora do encaixe *</label>
                                    <input type="datetime-local" id="encaixeDatetime" class="form-control"
                                           value="{{ old('data_hora_inicio') }}">
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Observações</label>
                            <textarea name="observacoes" class="form-control" rows="3" placeholder="Alguma observação especial?">{{ old('observacoes') }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="recorrencia">Repetir</label>
                            <select name="recorrencia" id="recorrencia" class="form-select">
                                <option value="nenhuma">Não repetir</option>
                                <option value="semanal">Toda semana</option>
                                <option value="quinzenal">A cada 15 dias</option>
                                <option value="mensal">Todo mês</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="ocorrencias">Quantas vezes</label>
                            <input type="number" name="ocorrencias" id="ocorrencias" class="form-control"
                                   min="1" max="12" value="1">
                            <small class="text-muted">Inclui o agendamento atual (máx. 12).</small>
                        </div>

                        <div class="col-12">
                            <div class="resumo-agendamento p-3 bg-light rounded d-none" id="resumo">
                                <div class="row">
                                    <div class="col">
                                        <strong>Duração Total:</strong> <span id="duracaoTotal">-</span>
                                    </div>
                                    <div class="col">
                                        <strong>Valor Total:</strong> <span id="valorTotal" class="text-pink fw-bold">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-pink" id="btnSubmit" disabled>
                                <i class="fas fa-calendar-plus me-2"></i> Criar Agendamento
                            </button>
                            <a href="{{ route('dono.agendamentos.index') }}" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.initBookingForm({ mode: 'dono', resumoId: 'resumo' });
});
</script>
@endpush
