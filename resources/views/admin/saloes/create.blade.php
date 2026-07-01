@extends('layouts.app')

@section('title', 'Novo Salão')
@section('page-title', 'Cadastrar Novo Salão')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-plus text-pink me-2" aria-hidden="true"></i> Novo Salão</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.saloes.store') }}">
                    @csrf
                    @include('admin.saloes.form', [
                        'salao'       => new \App\Models\Salao(),
                        'submitLabel' => 'Criar Salão',
                        'cancelUrl'   => route('admin.saloes.index'),
                    ])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
