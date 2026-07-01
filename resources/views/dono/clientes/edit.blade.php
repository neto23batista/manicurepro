@extends('layouts.app')

@section('title', 'Editar cliente')
@section('page-title', 'Editar · ' . $cliente->nome)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-user-edit text-pink me-2"></i>Editar cliente</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('dono.clientes.update', $cliente) }}" method="POST">
                    @csrf @method('PUT')
                    @include('dono.clientes.form')
                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-pink">
                            <i class="fas fa-save me-1"></i>Salvar
                        </button>
                        <a href="{{ route('dono.clientes.show', $cliente) }}" class="btn btn-outline-secondary">Cancelar</a>
                        <form action="{{ route('dono.clientes.destroy', $cliente) }}" method="POST" class="ms-auto"
                              data-confirm="Desativar cliente?" data-confirm-message="O cliente {{ $cliente->nome }} será desativado.">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger">
                                <i class="fas fa-user-slash me-1"></i>Desativar
                            </button>
                        </form>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
