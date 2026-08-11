@extends('layouts.app')

@section('title', 'Galeria')
@section('page-title', 'Galeria de Trabalhos')

@section('content')
{{-- Upload --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-cloud-arrow-up text-pink me-2"></i>Adicionar fotos</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('dono.galeria.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Fotos <small class="text-muted">(JPG, PNG ou WEBP — 200×200 a 8000×8000 px, até 5 MB cada)</small></label>
                    <input type="file" name="fotos[]" class="form-control @error('fotos') is-invalid @enderror @error('fotos.*') is-invalid @enderror"
                           accept="image/jpeg,image/png,image/webp" multiple required>
                    @error('fotos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('fotos.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Título <small class="text-muted">(opcional)</small></label>
                    <input type="text" name="titulo" value="{{ old('titulo') }}" maxlength="120"
                           class="form-control" placeholder="Ex: Francesinha com glitter">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Profissional <small class="text-muted">(opcional)</small></label>
                    <select name="manicure_id" class="form-select">
                        <option value="">— Não informar —</option>
                        @foreach($manicures as $m)
                            <option value="{{ $m->id }}" @selected(old('manicure_id') == $m->id)>{{ $m->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="publicar" value="1" id="publicar" checked>
                        <label class="form-check-label" for="publicar">Publicar no site</label>
                    </div>
                </div>
            </div>
            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-pink"><i class="fas fa-plus me-1"></i>Enviar fotos</button>
            </div>
        </form>
    </div>
</div>

{{-- Grade --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fas fa-images text-pink me-2"></i>Fotos da galeria</h5>
        <span class="badge bg-pink-light text-pink">{{ $fotos->total() }} foto(s)</span>
    </div>
    <div class="card-body">
        @if($fotos->count())
            <div class="row g-3">
                @foreach($fotos as $foto)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card h-100 border {{ $foto->publicar ? '' : 'opacity-75' }}">
                            <div class="ratio ratio-1x1">
                                <img src="{{ $foto->foto_url }}" alt="{{ $foto->titulo ?: 'Trabalho' }}"
                                     class="card-img-top object-fit-cover rounded-top">
                            </div>
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <small class="fw-semibold text-truncate">{{ $foto->titulo ?: 'Sem título' }}</small>
                                    @if($foto->publicar)
                                        <span class="badge bg-success">Publicada</span>
                                    @else
                                        <span class="badge bg-secondary">Oculta</span>
                                    @endif
                                </div>
                                @if($foto->manicure)
                                    <small class="text-muted d-block mb-2"><i class="fas fa-hand-sparkles me-1"></i>{{ $foto->manicure->nome }}</small>
                                @endif
                                <div class="d-flex gap-1">
                                    <form method="POST" action="{{ route('dono.galeria.publicar', $foto) }}" class="flex-grow-1">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-secondary w-100" title="{{ $foto->publicar ? 'Ocultar' : 'Publicar' }}">
                                            <i class="fas {{ $foto->publicar ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Editar"
                                            data-bs-toggle="modal" data-bs-target="#edit{{ $foto->id }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('dono.galeria.destroy', $foto) }}"
                                          data-confirm="Remover foto?" data-confirm-message="Esta foto será excluída permanentemente." data-confirm-ok="Remover">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Remover"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Modal: editar foto --}}
                    <div class="modal fade" id="edit{{ $foto->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar foto</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                </div>
                                <form method="POST" action="{{ route('dono.galeria.update', $foto) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-body">
                                        <img src="{{ $foto->foto_url }}" alt="" class="img-fluid rounded mb-3">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Título</label>
                                            <input type="text" name="titulo" value="{{ $foto->titulo }}" maxlength="120" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Descrição <small class="text-muted">(opcional)</small></label>
                                            <textarea name="descricao" rows="2" maxlength="500" class="form-control">{{ $foto->descricao }}</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Profissional</label>
                                            <select name="manicure_id" class="form-select">
                                                <option value="">— Não informar —</option>
                                                @foreach($manicures as $m)
                                                    <option value="{{ $m->id }}" @selected($foto->manicure_id == $m->id)>{{ $m->nome }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" role="switch" name="publicar" value="1" id="pub{{ $foto->id }}" @checked($foto->publicar)>
                                            <label class="form-check-label" for="pub{{ $foto->id }}">Publicar no site</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="destaque" value="1" id="dest{{ $foto->id }}" @checked($foto->destaque)>
                                            <label class="form-check-label" for="dest{{ $foto->id }}">Destacar na galeria</label>
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
                @endforeach
            </div>

            @if($fotos->hasPages())
                <div class="mt-4">{{ $fotos->links() }}</div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-images"></i></div>
                <h6 class="fw-bold">Nenhuma foto na galeria</h6>
                <p>Mostre seus melhores trabalhos! As fotos publicadas aparecem na página do salão.</p>
            </div>
        @endif
    </div>
</div>
@endsection
