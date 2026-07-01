@extends('layouts.app')

@section('title', 'Novo cliente')
@section('page-title', 'Cadastrar cliente')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-user-plus text-pink me-2"></i>Novo cliente</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('dono.clientes.store') }}" method="POST">
                    @csrf
                    @include('dono.clientes.form')
                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-pink">
                            <i class="fas fa-save me-1"></i>Cadastrar
                        </button>
                        <a href="{{ route('dono.clientes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
