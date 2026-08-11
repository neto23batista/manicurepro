{{-- Partial reutilizada por create e edit --}}
@php
    $clientes = $clientes ?? collect();
    $servicos = $servicos ?? collect();
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Código *</label>
        <input type="text" name="codigo" required maxlength="30"
               class="form-control @error('codigo') is-invalid @enderror"
               value="{{ old('codigo', $cupom->codigo ?? '') }}"
               placeholder="Ex: PRIMEIRACOMPRA10"
               style="text-transform:uppercase">
        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Tipo *</label>
        <select name="tipo" class="form-select @error('tipo') is-invalid @enderror" required id="tipoSelect">
            <option value="percentual" {{ old('tipo', $cupom->tipo ?? '') === 'percentual' ? 'selected' : '' }}>Percentual (%)</option>
            <option value="fixo" {{ old('tipo', $cupom->tipo ?? '') === 'fixo' ? 'selected' : '' }}>Valor fixo (R$)</option>
        </select>
        @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Valor *</label>
        <div class="input-group">
            <span class="input-group-text" id="valorPrefix">{{ old('tipo', $cupom->tipo ?? 'percentual') === 'percentual' ? '%' : 'R$' }}</span>
            <input type="number" name="valor" step="0.01" min="0" required
                   class="form-control @error('valor') is-invalid @enderror"
                   value="{{ old('valor', $cupom->valor ?? '') }}">
            @error('valor') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Pedido mínimo</label>
        <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="number" name="minimo_pedido" step="0.01" min="0"
                   class="form-control"
                   value="{{ old('minimo_pedido', $cupom->minimo_pedido ?? '') }}"
                   placeholder="0,00">
        </div>
        <small class="text-muted">Deixe em branco para sem mínimo</small>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Desconto máximo</label>
        <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="number" name="maximo_desconto" step="0.01" min="0"
                   class="form-control"
                   value="{{ old('maximo_desconto', $cupom->maximo_desconto ?? '') }}"
                   placeholder="0,00">
        </div>
        <small class="text-muted">Limite p/ cupons percentuais</small>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Uso máximo (global)</label>
        <input type="number" name="uso_maximo" min="1"
               class="form-control"
               value="{{ old('uso_maximo', $cupom->uso_maximo ?? '') }}"
               placeholder="Ilimitado">
        <small class="text-muted">Quantos usos no total</small>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Uso máx. por cliente</label>
        <input type="number" name="uso_maximo_por_cliente" min="1"
               class="form-control"
               value="{{ old('uso_maximo_por_cliente', $cupom->uso_maximo_por_cliente ?? '') }}"
               placeholder="Ilimitado">
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Cliente específico</label>
        <select name="cliente_id" class="form-select">
            <option value="">— Qualquer cliente —</option>
            @foreach($clientes as $cli)
                <option value="{{ $cli->id }}"
                    {{ (string) old('cliente_id', $cupom->cliente_id ?? '') === (string) $cli->id ? 'selected' : '' }}>
                    {{ $cli->nome }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Serviço específico</label>
        <select name="servico_id" class="form-select">
            <option value="">— Qualquer serviço —</option>
            @foreach($servicos as $srv)
                <option value="{{ $srv->id }}"
                    {{ (string) old('servico_id', $cupom->servico_id ?? '') === (string) $srv->id ? 'selected' : '' }}>
                    {{ $srv->nome }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Validade</label>
        <input type="date" name="validade"
               class="form-control @error('validade') is-invalid @enderror"
               value="{{ old('validade', isset($cupom) && $cupom->validade ? $cupom->validade->format('Y-m-d') : '') }}"
               min="{{ today()->toDateString() }}">
        @error('validade') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <small class="text-muted">Deixe em branco para sem validade</small>
    </div>

    <div class="col-md-6 d-flex flex-column justify-content-end gap-2 pb-2">
        <div class="form-check form-switch">
            <input type="hidden" name="ativo" value="0">
            <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
                   {{ old('ativo', $cupom->ativo ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="ativo">Cupom ativo</label>
        </div>
        <div class="form-check form-switch">
            <input type="hidden" name="primeira_compra" value="0">
            <input class="form-check-input" type="checkbox" name="primeira_compra" value="1" id="primeira"
                   {{ old('primeira_compra', $cupom->primeira_compra ?? false) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="primeira">Só primeira compra</label>
        </div>
        <div class="form-check form-switch">
            <input type="hidden" name="anti_stacking_fidelidade" value="0">
            <input class="form-check-input" type="checkbox" name="anti_stacking_fidelidade" value="1" id="antiStack"
                   {{ old('anti_stacking_fidelidade', $cupom->anti_stacking_fidelidade ?? false) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="antiStack">Não acumula com fidelidade (sem pontos neste atendimento)</label>
        </div>
    </div>
</div>

<script>
document.getElementById('tipoSelect').addEventListener('change', function() {
    document.getElementById('valorPrefix').textContent = this.value === 'percentual' ? '%' : 'R$';
});
</script>
