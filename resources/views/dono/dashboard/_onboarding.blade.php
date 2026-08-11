@if(!empty($onboarding) && ($onboarding['show'] ?? false))
    <div class="card mb-4 border-0" style="background:linear-gradient(135deg,#fff5f9,#ffffff);box-shadow:var(--shadow-sm)">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h5 class="mb-1"><i class="fas fa-rocket text-pink me-2"></i>Primeiros passos</h5>
                    <p class="text-muted small mb-0">{{ $onboarding['done'] }} de {{ $onboarding['total'] }} itens · {{ $onboarding['percent'] }}%</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('dono.onboarding.show') }}" class="btn btn-sm btn-pink">Abrir guia</a>
                    <form action="{{ route('dono.onboarding.dismiss') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Ocultar</button>
                    </form>
                </div>
            </div>
            <div class="progress mb-3" style="height:8px">
                <div class="progress-bar bg-pink" style="width: {{ $onboarding['percent'] }}%"></div>
            </div>
            <ul class="list-unstyled mb-0">
                @foreach($onboarding['items'] as $item)
                    <li class="mb-1 small {{ $item['done'] ? 'text-muted text-decoration-line-through' : '' }}">
                        <i class="fas {{ $item['done'] ? 'fa-check text-success' : 'fa-minus text-muted' }} me-1"></i>
                        {{ $item['label'] }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
