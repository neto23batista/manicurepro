{{-- Partial form de cliente --}}
<div class="row g-3">
    <div class="col-md-7">
        <label class="form-label fw-semibold">Nome completo *</label>
        <input type="text" name="nome" required maxlength="255"
               class="form-control @error('nome') is-invalid @enderror"
               value="{{ old('nome', $cliente->nome ?? '') }}">
        @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-5">
        <label class="form-label fw-semibold">CPF</label>
        <input type="text" name="cpf" maxlength="14"
               class="form-control"
               value="{{ old('cpf', $cliente->cpf ?? '') }}"
               placeholder="000.000.000-00">
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">E-mail</label>
        <input type="email" name="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $cliente->email ?? '') }}">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Telefone</label>
        <input type="tel" name="telefone"
               class="form-control"
               value="{{ old('telefone', $cliente->telefone ?? '') }}"
               placeholder="(00) 00000-0000">
    </div>

    <div class="col-md-3">
        <label class="form-label fw-semibold">Nascimento</label>
        <input type="date" name="data_nascimento"
               class="form-control"
               value="{{ old('data_nascimento', isset($cliente, $cliente->data_nascimento) ? $cliente->data_nascimento->format('Y-m-d') : '') }}"
               max="{{ today()->toDateString() }}">
    </div>

    <div class="col-12">
        <label class="form-label fw-semibold">Endereço</label>
        <textarea name="endereco" rows="2" maxlength="500" class="form-control">{{ old('endereco', $cliente->endereco ?? '') }}</textarea>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Alergias / Restrições</label>
        <textarea name="alergias" rows="2" maxlength="500" class="form-control"
                  placeholder="Ex: alérgica a esmalte com formol">{{ old('alergias', $cliente->alergias ?? '') }}</textarea>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Observações</label>
        <textarea name="observacoes" rows="2" maxlength="1000" class="form-control"
                  placeholder="Preferências, anotações...">{{ old('observacoes', $cliente->observacoes ?? '') }}</textarea>
    </div>

    @isset($cliente)
    <div class="col-12">
        <div class="form-check form-switch">
            <input type="hidden" name="ativo" value="0">
            <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
                   {{ old('ativo', $cliente->ativo) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="ativo">Cliente ativo</label>
        </div>
    </div>
    @endisset
</div>
