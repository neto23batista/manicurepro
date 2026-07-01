@extends('layouts.app')

@section('title', 'Categorias')
@section('page-title', 'Categorias de Serviço')

@section('content')
<div class="row g-4">
    {{-- Form de criação --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-plus text-pink me-2"></i>Nova categoria</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.categorias.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nome *</label>
                        <input type="text" name="nome" required maxlength="100" class="form-control"
                               placeholder="Ex: Tratamentos especiais">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descrição</label>
                        <textarea name="descricao" rows="2" maxlength="500" class="form-control"></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-7">
                            <label class="form-label fw-semibold">Ícone (FA)</label>
                            <input type="text" name="icone" maxlength="50" class="form-control" placeholder="fa-hand-sparkles">
                        </div>
                        <div class="col-5">
                            <label class="form-label fw-semibold">Ordem</label>
                            <input type="number" name="ordem" value="0" min="0" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label fw-semibold">Cor</label>
                        <input type="color" name="cor" value="#ec4899" class="form-control form-control-color" style="height:40px">
                    </div>
                    <button type="submit" class="btn btn-pink w-100">
                        <i class="fas fa-save me-1"></i>Criar categoria
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Lista --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between flex-wrap gap-2">
                <h5 class="card-title mb-0"><i class="fas fa-list text-pink me-2"></i>Todas as categorias</h5>
            </div>
            <div class="card-body p-0">
                @forelse($categorias as $cat)
                    <div class="d-flex align-items-center p-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="me-3 d-flex align-items-center justify-content-center rounded-3"
                             style="width:48px;height:48px;background:{{ $cat->cor ?? '#ec4899' }}22;color:{{ $cat->cor ?? '#ec4899' }}">
                            <i class="fas {{ $cat->icone ?? 'fa-tag' }} fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $cat->nome }}</div>
                            <small class="text-muted">
                                {{ $cat->salao?->nome }} ·
                                <span class="badge bg-secondary">{{ $cat->servicos_count }} serviço(s)</span>
                                @if(!$cat->ativo)
                                    <span class="badge bg-danger">Inativo</span>
                                @endif
                            </small>
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-ghost"
                                    data-bs-toggle="modal" data-bs-target="#editCat{{ $cat->id }}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('admin.categorias.destroy', $cat) }}" method="POST" class="d-inline"
                                  data-confirm="Excluir categoria?" data-confirm-message="A categoria {{ $cat->nome }} será removida.">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-ghost text-danger" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Modal de edição --}}
                    <div class="modal fade" id="editCat{{ $cat->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar categoria</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('admin.categorias.update', $cat) }}" method="POST">
                                    @csrf @method('PUT')
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Nome *</label>
                                            <input type="text" name="nome" value="{{ $cat->nome }}" required class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Descrição</label>
                                            <textarea name="descricao" rows="2" class="form-control">{{ $cat->descricao }}</textarea>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-7">
                                                <label class="form-label">Ícone</label>
                                                <input type="text" name="icone" value="{{ $cat->icone }}" class="form-control">
                                            </div>
                                            <div class="col-5">
                                                <label class="form-label">Ordem</label>
                                                <input type="number" name="ordem" value="{{ $cat->ordem }}" min="0" class="form-control">
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <label class="form-label">Cor</label>
                                            <input type="color" name="cor" value="{{ $cat->cor ?? '#ec4899' }}" class="form-control form-control-color" style="height:40px">
                                        </div>
                                        <div class="form-check form-switch mt-3">
                                            <input type="hidden" name="ativo" value="0">
                                            <input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo{{ $cat->id }}" {{ $cat->ativo ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="ativo{{ $cat->id }}">Categoria ativa</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-pink">Salvar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-tags"></i></div>
                        <h6 class="fw-bold">Nenhuma categoria</h6>
                        <p>Crie categorias para organizar melhor os serviços.</p>
                    </div>
                @endforelse
            </div>
            @if($categorias->hasPages())
                <div class="card-footer">{{ $categorias->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
