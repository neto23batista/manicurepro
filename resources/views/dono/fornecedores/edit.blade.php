@extends('layouts.app')

@section('title', 'Editar fornecedor')
@section('page-title', 'Editar fornecedor')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-truck text-pink me-2"></i>{{ $fornecedor->nome }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dono.fornecedores.update', $fornecedor) }}">
                    @csrf @method('PUT')
                    @include('dono.fornecedores._form', ['fornecedor' => $fornecedor])
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-pink"><i class="fas fa-save me-1"></i>Salvar alterações</button>
                        <a href="{{ route('dono.fornecedores.index') }}" class="btn btn-outline-secondary">Voltar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
