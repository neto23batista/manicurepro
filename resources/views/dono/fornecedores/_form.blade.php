@php($f = $fornecedor ?? null)
<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
        <input type="text" name="nome" class="form-control @error('nome') is-invalid @enderror"
               value="{{ old('nome', $f->nome ?? '') }}" required autofocus maxlength="255">
        @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Documento</label>
        <input type="text" name="documento" class="form-control" value="{{ old('documento', $f->documento ?? '') }}"
               maxlength="30" placeholder="CNPJ / CPF">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Contato</label>
        <input type="text" name="contato" class="form-control" value="{{ old('contato', $f->contato ?? '') }}" maxlength="255">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Telefone</label>
        <input type="text" name="telefone" class="form-control" value="{{ old('telefone', $f->telefone ?? '') }}" maxlength="30">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">E-mail</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $f->email ?? '') }}" maxlength="255">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Observações</label>
        <textarea name="observacoes" rows="2" class="form-control" maxlength="2000">{{ old('observacoes', $f->observacoes ?? '') }}</textarea>
    </div>
    @if($f)
        <div class="col-12">
            <div class="form-check form-switch">
                <input type="hidden" name="ativo" value="0">
                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
                       {{ old('ativo', $f->ativo) ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="ativo">Fornecedor ativo</label>
            </div>
        </div>
    @endif
</div>
