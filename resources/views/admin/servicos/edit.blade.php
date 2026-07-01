@extends('layouts.app')

@section('title', 'Editar Serviço')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0"><i class="fa-solid fa-hand-sparkles me-2 text-pink"></i>Editar Serviço</h2>
    <a href="{{ route('admin.servicos.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i>Voltar
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form action="{{ route('admin.servicos.update', $servico) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Salão <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" value="{{ \App\Models\Salao::principal()?->nome }}" readonly disabled>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nome do Serviço <span class="text-danger">*</span></label>
                    <input type="text" name="nome" class="form-control @error('nome') is-invalid @enderror"
                           value="{{ old('nome', $servico->nome) }}" required>
                    @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Categoria</label>
                    <select name="categoria_id" class="form-select @error('categoria_id') is-invalid @enderror">
                        <option value="">— Sem categoria —</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}"
                                {{ old('categoria_id', $servico->categoria_id) == $cat->id ? 'selected' : '' }}>
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
                               value="{{ old('preco', $servico->preco) }}" required>
                        @error('preco') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Duração (minutos) <span class="text-danger">*</span></label>
                    <input type="number" name="duracao" min="5" step="5"
                           class="form-control @error('duracao') is-invalid @enderror"
                           value="{{ old('duracao', $servico->duracao) }}" required>
                    @error('duracao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Comissão Manicure (%)</label>
                    <div class="input-group">
                        <input type="number" name="comissao_percentual" min="0" max="100" step="0.5"
                               class="form-control @error('comissao_percentual') is-invalid @enderror"
                               value="{{ old('comissao_percentual', $servico->comissao_percentual ?? 40) }}">
                        <span class="input-group-text">%</span>
                    </div>
                    @error('comissao_percentual') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Descrição</label>
                    <textarea name="descricao" rows="3"
                              class="form-control @error('descricao') is-invalid @enderror"
                              placeholder="Descrição detalhada do serviço...">{{ old('descricao', $servico->descricao) }}</textarea>
                    @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="ativo" value="0">
                        <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo"
                               {{ old('ativo', $servico->ativo) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="ativo">Serviço ativo</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="combo" value="0">
                        <input class="form-check-input" type="checkbox" name="combo" value="1" id="combo"
                               {{ old('combo', $servico->combo) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="combo">
                            <i class="fa-solid fa-star me-1 text-warning"></i>Pacote / Combo
                        </label>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-pink">
                    <i class="fa-solid fa-floppy-disk me-1"></i>Salvar Alterações
                </button>
                <a href="{{ route('admin.servicos.index') }}" class="btn btn-outline-secondary">Cancelar</a>

                <form action="{{ route('admin.servicos.destroy', $servico) }}" method="POST" class="ms-auto"
                      data-confirm="Excluir serviço?" data-confirm-message="Esta ação é permanente e não pode ser desfeita." data-confirm-ok="Excluir">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fa-solid fa-trash me-1"></i>Excluir
                    </button>
                </form>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header bg-light">
        <h6 class="mb-0 fw-semibold">
            <i class="fa-solid fa-chart-bar me-2 text-pink"></i>Estatísticas do Serviço
        </h6>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4">
                <div class="stat-mini">
                    <div class="value text-pink fw-bold fs-4">{{ $servico->agendamentos_count ?? 0 }}</div>
                    <div class="text-muted small">Agendamentos</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-mini">
                    <div class="value text-pink fw-bold fs-4">
                        R$ {{ number_format(($servico->agendamentos_count ?? 0) * $servico->preco, 2, ',', '.') }}
                    </div>
                    <div class="text-muted small">Faturamento Estimado</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-mini">
                    <div class="value text-pink fw-bold fs-4">{{ $servico->duracao_formatada }}</div>
                    <div class="text-muted small">Duração</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
