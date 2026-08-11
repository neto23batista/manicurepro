@extends('layouts.app')

@section('title', 'Novo pacote')
@section('page-title', 'Novo pacote / combo')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-layer-group text-pink me-2"></i>Criar pacote</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('dono.pacotes.store') }}" method="POST">
                    @csrf
                    @include('dono.pacotes.form')
                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-pink">
                            <i class="fas fa-save me-1"></i> Criar pacote
                        </button>
                        <a href="{{ route('dono.pacotes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
