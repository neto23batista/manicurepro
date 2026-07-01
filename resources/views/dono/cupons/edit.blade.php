@extends('layouts.app')

@section('title', 'Editar cupom')
@section('page-title', 'Editar cupom · ' . $cupom->codigo)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-edit text-pink me-2"></i>Editar cupom</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('dono.cupons.update', $cupom) }}" method="POST">
                    @csrf @method('PUT')
                    @include('dono.cupons.form')
                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-pink">
                            <i class="fas fa-save me-1"></i> Salvar alterações
                        </button>
                        <a href="{{ route('dono.cupons.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
