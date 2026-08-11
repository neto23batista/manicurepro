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
        <form action="{{ route('admin.servicos.update', $servico) }}" method="POST" enctype="multipart/form-data">
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
                    <label class="form-label fw-semibold">Comissão % <span class="text-muted fw-normal">(opcional)</span></label>
                    <div class="input-group">
                        <input type="number" name="comissao_percentual" min="0" max="100" step="0.5"
                               class="form-control @error('comissao_percentual') is-invalid @enderror"
                               value="{{ old('comissao_percentual', $servico->comissao_percentual) }}"
                               placeholder="Usa % da manicure">
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted">Sobrepõe a % do profissional neste serviço.</small>
                    @error('comissao_percentual') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Comissão fixa <span class="text-muted fw-normal">(opcional)</span></label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" name="comissao_fixo" min="0" max="9999.99" step="0.01"
                               class="form-control @error('comissao_fixo') is-invalid @enderror"
                               value="{{ old('comissao_fixo', $servico->comissao_fixo) }}"
                               placeholder="Ex: 15,00">
                    </div>
                    <small class="text-muted">Se preenchida, prevalece sobre a %.</small>
                    @error('comissao_fixo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Custo estimado (R$)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="number" name="custo_estimado" step="0.01" min="0"
                               class="form-control @error('custo_estimado') is-invalid @enderror"
                               value="{{ old('custo_estimado', $servico->custo_estimado) }}" placeholder="Opcional">
                        @error('custo_estimado') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Imagem</label>
                    @if($servico->imagem_url)
                        <div class="mb-2">
                            <img src="{{ $servico->imagem_url }}" alt="" class="rounded" style="max-height:64px">
                            <div class="form-check mt-1">
                                <input type="checkbox" name="remover_imagem" value="1" class="form-check-input" id="rmImg">
                                <label for="rmImg" class="form-check-label small">Remover imagem</label>
                            </div>
                        </div>
                    @endif
                    <input type="file" name="imagem" accept="image/*"
                           class="form-control @error('imagem') is-invalid @enderror">
                    @error('imagem') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-layer-group me-1 text-pink"></i>Variações (opcional)</h6>
            <div id="variacoes-wrap" class="d-flex flex-column gap-2 mb-3">
                @php
                    $oldVars = old('variacoes');
                    if ($oldVars === null) {
                        $oldVars = $servico->variacoes->map(fn ($v) => [
                            'id' => $v->id, 'nome' => $v->nome, 'preco' => $v->preco,
                            'duracao' => $v->duracao, 'ordem' => $v->ordem, 'ativo' => $v->ativo,
                        ])->all();
                        if ($oldVars === []) {
                            $oldVars = [['nome' => '', 'preco' => '', 'duracao' => 30]];
                        }
                    }
                @endphp
                @foreach($oldVars as $i => $v)
                <div class="row g-2 align-items-end variacao-row border rounded p-2">
                    @if(!empty($v['id']))
                        <input type="hidden" name="variacoes[{{ $i }}][id]" value="{{ $v['id'] }}">
                    @endif
                    <div class="col-md-3">
                        <label class="form-label small">Nome</label>
                        <input type="text" name="variacoes[{{ $i }}][nome]" class="form-control" value="{{ $v['nome'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Preço</label>
                        <input type="number" step="0.01" min="0" name="variacoes[{{ $i }}][preco]" class="form-control" value="{{ $v['preco'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Duração</label>
                        <input type="number" min="5" name="variacoes[{{ $i }}][duracao]" class="form-control" value="{{ $v['duracao'] ?? 30 }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Ordem</label>
                        <input type="number" min="0" name="variacoes[{{ $i }}][ordem]" class="form-control" value="{{ $v['ordem'] ?? $i }}">
                    </div>
                    <div class="col-md-2">
                        <input type="hidden" name="variacoes[{{ $i }}][ativo]" value="1">
                        <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.variacao-row').remove()">Remover</button>
                    </div>
                </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="add-variacao">
                <i class="fa-solid fa-plus me-1"></i>Adicionar variação
            </button>

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
<script>
(function () {
    const wrap = document.getElementById('variacoes-wrap');
    document.getElementById('add-variacao')?.addEventListener('click', () => {
        const i = wrap.querySelectorAll('.variacao-row').length;
        const div = document.createElement('div');
        div.className = 'row g-2 align-items-end variacao-row border rounded p-2';
        div.innerHTML = `
            <div class="col-md-3"><label class="form-label small">Nome</label>
                <input type="text" name="variacoes[${i}][nome]" class="form-control"></div>
            <div class="col-md-3"><label class="form-label small">Preço</label>
                <input type="number" step="0.01" min="0" name="variacoes[${i}][preco]" class="form-control"></div>
            <div class="col-md-2"><label class="form-label small">Duração</label>
                <input type="number" min="5" name="variacoes[${i}][duracao]" class="form-control" value="30"></div>
            <div class="col-md-2"><label class="form-label small">Ordem</label>
                <input type="number" min="0" name="variacoes[${i}][ordem]" class="form-control" value="${i}"></div>
            <div class="col-md-2"><input type="hidden" name="variacoes[${i}][ativo]" value="1">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="this.closest('.variacao-row').remove()">Remover</button></div>`;
        wrap.appendChild(div);
    });
})();
</script>

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
