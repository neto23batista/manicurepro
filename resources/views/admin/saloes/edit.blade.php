@extends('layouts.app')

@section('title', 'Editar Salão')
@section('page-title', 'Editar: ' . $salao->nome)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-edit text-pink me-2" aria-hidden="true"></i> Editar Salão</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.saloes.update', $salao) }}">
                    @csrf @method('PUT')
                    @include('admin.saloes.form', [
                        'submitLabel' => 'Salvar Alterações',
                        'cancelUrl'   => route('admin.saloes.show', $salao),
                    ])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
