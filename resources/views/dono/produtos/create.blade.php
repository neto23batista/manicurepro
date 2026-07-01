@extends('layouts.app')

@section('title', 'Novo produto')
@section('page-title', 'Novo produto')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-box text-pink me-2"></i>Novo produto</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dono.produtos.store') }}">
                    @csrf
                    @include('dono.produtos._form', ['produto' => null])
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-pink"><i class="fas fa-save me-1"></i>Cadastrar produto</button>
                        <a href="{{ route('dono.produtos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
