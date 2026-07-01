@extends('layouts.app')

@section('title', 'Novo cupom')
@section('page-title', 'Novo cupom de desconto')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-ticket text-pink me-2"></i>Criar cupom</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('dono.cupons.store') }}" method="POST">
                    @csrf
                    @include('dono.cupons.form')
                    <hr class="my-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-pink">
                            <i class="fas fa-save me-1"></i> Criar cupom
                        </button>
                        <a href="{{ route('dono.cupons.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
