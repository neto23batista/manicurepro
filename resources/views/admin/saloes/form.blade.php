{{-- Partial reutilizada por create.blade.php e edit.blade.php. --}}
{{-- Espera: $salao (instância de Salao, pode ser nova) --}}

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label fw-semibold" for="nome">Nome do Salão *</label>
        <input type="text" id="nome" name="nome" class="form-control @error('nome') is-invalid @enderror"
               value="{{ old('nome', $salao->nome ?? '') }}" required placeholder="Ex: Salão Beleza Total">
        @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold" for="telefone">Telefone</label>
        <input type="tel" id="telefone" name="telefone" class="form-control"
               value="{{ old('telefone', $salao->telefone ?? '') }}" placeholder="(11) 99999-9999">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="email">E-mail</label>
        <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $salao->email ?? '') }}" placeholder="salao@email.com">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="whatsapp">WhatsApp</label>
        <input type="tel" id="whatsapp" name="whatsapp" class="form-control"
               value="{{ old('whatsapp', $salao->whatsapp ?? '') }}" placeholder="(11) 99999-9999">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="endereco">Endereço</label>
        <input type="text" id="endereco" name="endereco" class="form-control"
               value="{{ old('endereco', $salao->endereco ?? '') }}" placeholder="Rua, Avenida...">
    </div>
    <div class="col-md-2">
        <label class="form-label fw-semibold" for="numero">Número</label>
        <input type="text" id="numero" name="numero" class="form-control"
               value="{{ old('numero', $salao->numero ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold" for="bairro">Bairro</label>
        <input type="text" id="bairro" name="bairro" class="form-control"
               value="{{ old('bairro', $salao->bairro ?? '') }}">
    </div>
    <div class="col-md-5">
        <label class="form-label fw-semibold" for="cidade">Cidade</label>
        <input type="text" id="cidade" name="cidade" class="form-control"
               value="{{ old('cidade', $salao->cidade ?? '') }}">
    </div>
    <div class="col-md-2">
        <label class="form-label fw-semibold" for="estado">Estado</label>
        <input type="text" id="estado" name="estado" class="form-control"
               value="{{ old('estado', $salao->estado ?? '') }}" maxlength="2" placeholder="SP">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold" for="cep">CEP</label>
        <input type="text" id="cep" name="cep" class="form-control"
               value="{{ old('cep', $salao->cep ?? '') }}" placeholder="00000-000">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold" for="latitude">Latitude</label>
        <input type="number" step="any" id="latitude" name="latitude" class="form-control"
               value="{{ old('latitude', $salao->latitude ?? '') }}" placeholder="-23.5505">
    </div>
    <div class="col-md-3">
        <label class="form-label fw-semibold" for="longitude">Longitude</label>
        <input type="number" step="any" id="longitude" name="longitude" class="form-control"
               value="{{ old('longitude', $salao->longitude ?? '') }}" placeholder="-46.6333">
        <small class="text-muted">Para a busca "perto de mim". Pegue no Google Maps.</small>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold" for="instagram">Instagram</label>
        <div class="input-group">
            <span class="input-group-text">@</span>
            <input type="text" id="instagram" name="instagram" class="form-control"
                   value="{{ old('instagram', $salao->instagram ?? '') }}" placeholder="salao">
        </div>
    </div>

    @isset($salao->id)
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
                       {{ $salao->ativo ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="ativo">Salão Ativo</label>
            </div>
        </div>
    @endisset

    <div class="col-12">
        <label class="form-label fw-semibold" for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao" class="form-control" rows="3"
                  placeholder="Descreva o salão...">{{ old('descricao', $salao->descricao ?? '') }}</textarea>
    </div>
    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-pink">
            <i class="fas fa-save me-2" aria-hidden="true"></i> {{ $submitLabel ?? 'Salvar' }}
        </button>
        <a href="{{ $cancelUrl ?? route('admin.saloes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</div>
