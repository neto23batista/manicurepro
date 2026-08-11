{{-- Partial reutilizada por create e edit --}}
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Nome *</label>
        <input type="text" name="nome" required maxlength="120"
               class="form-control @error('nome') is-invalid @enderror"
               value="{{ old('nome', $pacote->nome ?? '') }}"
               placeholder="Ex: Pacote 5 Sessões">
        @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Sessões *</label>
        <input type="number" name="sessoes" required min="1" max="100"
               class="form-control @error('sessoes') is-invalid @enderror"
               value="{{ old('sessoes', $pacote->sessoes ?? 5) }}">
        @error('sessoes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Validade (dias)</label>
        <input type="number" name="validade_dias" min="1" max="730"
               class="form-control @error('validade_dias') is-invalid @enderror"
               value="{{ old('validade_dias', $pacote->validade_dias ?? '') }}"
               placeholder="Sem prazo">
        @error('validade_dias') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <small class="text-muted">Em branco = sem validade</small>
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Preço *</label>
        <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="number" name="preco" step="0.01" min="0" required
                   class="form-control @error('preco') is-invalid @enderror"
                   value="{{ old('preco', $pacote->preco ?? '') }}">
            @error('preco') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="col-md-4 d-flex align-items-end pb-2">
        <div class="form-check form-switch">
            <input type="hidden" name="ativo" value="0">
            <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
                   {{ old('ativo', $pacote->ativo ?? true) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="ativo">Pacote ativo</label>
        </div>
    </div>
</div>
