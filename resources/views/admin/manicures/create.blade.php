@extends('layouts.app')

@section('title', 'Nova Manicure')
@section('page-title', 'Cadastrar Nova Manicure')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-hand-sparkles text-pink me-2"></i> Nova Manicure</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.manicures.store') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- Upload de foto --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Foto da profissional</label>
                        <div class="d-flex align-items-center gap-3">
                            <img id="fotoPreview" src="https://ui-avatars.com/api/?name=NM&background=ec4899&color=fff&size=80"
                                 class="rounded-circle" style="width:72px;height:72px;object-fit:cover;border:3px solid var(--pink-200)">
                            <div class="flex-grow-1">
                                <input type="file" name="foto" id="fotoInput" accept="image/*"
                                       class="form-control @error('foto') is-invalid @enderror">
                                <small class="text-muted">JPG/PNG/WebP até 2 MB</small>
                                @error('foto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Salão *</label>
                            <input type="text" class="form-control" value="{{ \App\Models\Salao::principal()?->nome }}" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nome Completo *</label>
                            <input type="text" name="nome" class="form-control @error('nome') is-invalid @enderror"
                                   value="{{ old('nome') }}" required>
                            @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">E-mail (login) *</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Telefone</label>
                            <input type="tel" name="telefone" class="form-control" value="{{ old('telefone') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Senha *</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Mínimo 8 caracteres" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Comissão padrão (%)</label>
                            <input type="number" name="comissao" class="form-control" value="{{ old('comissao', 40) }}" min="0" max="100" step="0.5">
                            <small class="text-muted">Usada quando o serviço não define % nem valor fixo próprio.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Bio</label>
                            <textarea name="bio" class="form-control" rows="3" placeholder="Apresentação profissional...">{{ old('bio') }}</textarea>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-pink">
                                <i class="fas fa-save me-2"></i> Cadastrar Manicure
                            </button>
                            <a href="{{ route('admin.manicures.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('fotoInput')?.addEventListener('change', function(e) {
    const f = e.target.files[0];
    if (f) {
        const r = new FileReader();
        r.onload = ev => document.getElementById('fotoPreview').src = ev.target.result;
        r.readAsDataURL(f);
    }
});
</script>
@endpush
@endsection
