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
                                                   class="servico-check visually-hidden">
                                            <div class="servico-option p-3 border rounded text-center">
                                                <div class="fw-semibold">{{ $s->nome }}</div>
                                                <div class="text-pink fw-bold">{{ $s->preco_formatado }}</div>
                                                <small class="text-muted">{{ $s->duracao_formatada }}</small>
                                                @if($s->combo)
                                                    <span class="badge bg-pink ms-1">Combo</span>
                                                @endif
                                            </div>
                                        </label>
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
    const checks = document.querySelectorAll('.servico-check');
    const btnSubmit = document.getElementById('btnSubmit');
    const selectManicure = document.getElementById('selectManicure');
    const resumo = document.getElementById('resumo');

    function getDuracao() {
        let duracao = 0;
        checks.forEach((c) => { if (c.checked) duracao += parseInt(c.dataset.duracao); });
        return duracao;
    }

    function getManicureId() {
        return selectManicure.value || null;
    }

    function calcularResumo() {
        let duracao = 0, valor = 0, selecionados = 0;
        checks.forEach((c) => {
            if (c.checked) {
                duracao += parseInt(c.dataset.duracao);
                valor += parseFloat(c.dataset.preco);
                selecionados++;
            }
            c.closest('label').querySelector('.servico-option').classList.toggle('selected', c.checked);
        });
        if (selecionados > 0) {
            resumo.classList.remove('d-none');
            const h = Math.floor(duracao / 60), m = duracao % 60;
            document.getElementById('duracaoTotal').textContent = h > 0 ? `${h}h ${m}min` : `${m}min`;
            document.getElementById('valorTotal').textContent = 'R$ ' + valor.toFixed(2).replace('.', ',');
        } else {
            resumo.classList.add('d-none');
        }
        picker?.load();
    }

    function verificarBotao() {
        btnSubmit.disabled = !(getDuracao() > 0 && getManicureId()
            && picker?.inputData?.value && picker?.getValue());
    }

    const picker = window.createSlotPicker({
        getManicureId,
        getDuracao,
        emptyHint: 'Selecione manicure, data e serviços para ver os horários.',
        onChange: verificarBotao,
        onSlotsLoaded: () => verificarBotao(),
    });

    checks.forEach((c) => c.addEventListener('change', calcularResumo));
    selectManicure.addEventListener('change', () => picker?.load());
});
</script>
@endpush
