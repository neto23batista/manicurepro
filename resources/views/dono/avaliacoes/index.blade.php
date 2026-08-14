@extends('layouts.app')

@section('title', 'Avaliações')
@section('page-title', 'Avaliações')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fas fa-star text-pink me-2"></i>Moderar avaliações</h5>
        <span class="text-muted small">Ocultar tira a nota da média pública e da página do salão.</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Quando</th>
                        <th>Cliente</th>
                        <th>Manicure</th>
                        <th>Nota</th>
                        <th>Comentário</th>
                        <th>Site</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($avaliacoes as $av)
                        <tr>
                            <td class="text-nowrap small">{{ $av->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $av->cliente?->nome ?? $av->agendamento?->nome_cliente_exibido ?? '—' }}</td>
                            <td>{{ $av->manicure?->nome ?? '—' }}</td>
                            <td class="text-nowrap text-warning">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= $av->nota ? '' : 'text-muted' }} small" aria-hidden="true"></i>
                                @endfor
                            </td>
                            <td class="small">{{ $av->comentario ? Str::limit($av->comentario, 80) : '—' }}</td>
                            <td>
                                @if($av->publicar)
                                    <span class="badge bg-success">Pública</span>
                                @else
                                    <span class="badge bg-secondary">Oculta</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('dono.avaliacoes.publicar', $av) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-outline-secondary" title="{{ $av->publicar ? 'Ocultar do site' : 'Publicar no site' }}">
                                        <i class="fas {{ $av->publicar ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                        {{ $av->publicar ? 'Ocultar' : 'Publicar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">Nenhuma avaliação ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($avaliacoes->hasPages())
        <div class="card-footer">{{ $avaliacoes->links() }}</div>
    @endif
</div>
@endsection
