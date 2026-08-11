@php($p = $produto ?? null)
<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
        <input type="text" name="nome" class="form-control @error('nome') is-invalid @enderror"
               value="{{ old('nome', $p->nome ?? '') }}" required autofocus>
        @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Marca</label>
        <input type="text" name="marca" class="form-control" value="{{ old('marca', $p->marca ?? '') }}" maxlength="255">
    </div>

    <div class="col-md-4">
        <label class="form-label fw-semibold">Código / SKU</label>
        <input type="text" name="codigo" class="form-control" value="{{ old('codigo', $p->codigo ?? '') }}" maxlength="50">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Fornecedor</label>
        <select name="fornecedor_id" class="form-select @error('fornecedor_id') is-invalid @enderror">
            <option value="">— Sem fornecedor —</option>
            @foreach($fornecedores ?? [] as $forn)
                <option value="{{ $forn->id }}" @selected((string) old('fornecedor_id', $p->fornecedor_id ?? '') === (string) $forn->id)>
                    {{ $forn->nome }}
                </option>
            @endforeach
        </select>
        @error('fornecedor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Unidade</label>
        <input type="text" name="unidade" class="form-control" value="{{ old('unidade', $p->unidade ?? 'un') }}"
               maxlength="20" placeholder="un, ml, g…">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Estoque mínimo</label>
        <input type="number" step="0.001" min="0" name="estoque_minimo" class="form-control"
               value="{{ old('estoque_minimo', $p->estoque_minimo ?? config('manicure.estoque.minimo_padrao', 1)) }}">
        <small class="text-muted">Alerta quando o estoque ficar neste nível ou abaixo.</small>
    </div>

    <div class="col-md-6">
        <label class="form-label fw-semibold">Preço de custo</label>
        <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="number" step="0.01" min="0" name="preco_custo" class="form-control"
                   value="{{ old('preco_custo', $p->preco_custo ?? '') }}">
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Preço de venda</label>
        <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="number" step="0.01" min="0" name="preco_venda" class="form-control"
                   value="{{ old('preco_venda', $p->preco_venda ?? '') }}">
        </div>
    </div>

    @unless($p)
        <div class="col-md-6">
            <label class="form-label fw-semibold">Estoque inicial</label>
            <input type="number" step="0.001" min="0" name="estoque_atual" class="form-control"
                   value="{{ old('estoque_atual', 0) }}">
            <small class="text-muted">Depois, use “Movimentar” para repor ou dar baixa.</small>
        </div>
    @endunless

    <div class="col-12">
        <label class="form-label fw-semibold">Descrição</label>
        <textarea name="descricao" rows="2" class="form-control" maxlength="1000">{{ old('descricao', $p->descricao ?? '') }}</textarea>
    </div>

    @if($p)
        <div class="col-12">
            <div class="form-check form-switch">
                <input type="hidden" name="ativo" value="0">
                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
                       {{ old('ativo', $p->ativo) ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="ativo">Produto ativo</label>
            </div>
        </div>
    @endif
</div>
