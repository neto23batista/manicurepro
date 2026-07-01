@extends('layouts.app')

@section('title', 'Novo Serviço')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0"><i class="fa-solid fa-hand-sparkles me-2 text-pink"></i>Novo Serviço</h2>
    <a href="{{ route('admin.servicos.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="{{ route('admin.servicos.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Salão <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" value="{{ \App\Models\Salao::principal()?->nome }}" readonly disabled>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nome do Serviço <span class="text-danger">*</span></label>
                    <input type="text" name="nome" class="form-control @error('nome') is-invalid @enderror"
                           value="{{ old('nome') }}" placeholder="Ex: Manicure Simples" required>
                    @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Categoria</label>
                    <select name="categoria_id" class="form-select @error('categoria_id') is-invalid @enderror">
                        <option value="">— Sem categoria —</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ old('categoria_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->nome }}
                            </option>
                        @endforeach
                    </select>
                    @error('categoria_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Preço (R$) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" name="preco" step="0.01" min="0"
                               class="form-control @error('preco') is-invalid @enderror"
                               value="{{ old('preco') }}" placeholder="0,00" required>
                        @error('preco') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Duração (minutos) <span class="text-danger">*</span></label>
                    <input type="number" name="duracao" min="5" step="5"
                           class="form-control @error('duracao') is-invalid @enderror"
                           value="{{ old('duracao', 30) }}" required>
                    @error('duracao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Comissão Manicure (%)</label>
                    <div class="input-group">
                        <input type="number" name="comissao_percentual" min="0" max="100" step="0.5"
                               class="form-control @error('comissao_percentual') is-invalid @enderror"
                               value="{{ old('comissao_percentual', 40) }}">
                        <span class="input-group-text">%</span>
                    </div>
                    @error('comissao_percentual') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Descrição</label>
                    <textarea name="descricao" rows="3"
                              class="form-control @error('descricao') is-invalid @enderror"
                              placeholder="Descrição detalhada do serviço...">{{ old('descricao') }}</textarea>
                    @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="ativo" value="0">
                        <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
                               {{ old('ativo', '1') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="ativo">Serviço ativo</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="combo" value="0">
                        <input class="form-check-input" type="checkbox" name="combo" value="1" id="combo"
                               {{ old('combo') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="combo">
                            <i class="fa-solid fa-star me-1 text-warning"></i>Pacote / Combo
                        </label>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-pink">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Salvar Serviço
                </button>
                <a href="{{ route('admin.servicos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
