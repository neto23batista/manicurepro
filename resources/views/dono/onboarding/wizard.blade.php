@extends('layouts.app')

@section('title', 'Primeiros passos')
@section('page-title', 'Configuração inicial')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4 p-md-5">
                <div class="mb-4">
                    <p class="text-uppercase small text-muted mb-1">Bem-vindo</p>
                    <h2 class="h3 mb-2">Vamos deixar o {{ $salao->nome }} pronto</h2>
                    <p class="text-muted mb-0">Checklist rápido do 1º acesso. Você pode concluir agora ou continuar depois pelo dashboard.</p>
                </div>

                <div class="progress mb-4" style="height:10px" role="progressbar" aria-valuenow="{{ $progress['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-pink" style="width: {{ $progress['percent'] }}%"></div>
                </div>
                <p class="small text-muted mb-4">{{ $progress['done'] }} de {{ $progress['total'] }} concluídos ({{ $progress['percent'] }}%)</p>

                <ul class="list-group list-group-flush mb-4">
                    @foreach($progress['items'] as $item)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="{{ $item['done'] ? 'text-decoration-line-through text-muted' : '' }}">
                                <i class="fas {{ $item['done'] ? 'fa-check-circle text-success' : 'fa-circle text-muted' }} me-2"></i>
                                {{ $item['label'] }}
                            </span>
                            @if(! $item['done'] && $item['route'] && \Illuminate\Support\Facades\Route::has($item['route']))
                                <a href="{{ route($item['route'], $item['key'] === 'horarios' ? ['tab' => 'operacao'] : ($item['key'] === 'dados' ? ['tab' => 'identidade'] : [])) }}"
                                   class="btn btn-sm btn-outline-pink">Abrir</a>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <div class="d-flex flex-wrap gap-2">
                    @if($progress['complete'])
                        <form action="{{ route('dono.onboarding.complete') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-pink">Concluir configuração</button>
                        </form>
                    @else
                        <a href="{{ route('dono.dashboard') }}" class="btn btn-pink">Ir ao dashboard</a>
                        <form action="{{ route('dono.onboarding.dismiss') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary">Dispensar por agora</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
