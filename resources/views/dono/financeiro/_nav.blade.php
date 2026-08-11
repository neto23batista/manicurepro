<ul class="nav nav-pills gap-2 mb-4 flex-wrap">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dono.financeiro.index') ? 'active bg-pink' : 'text-muted' }}"
           href="{{ route('dono.financeiro.index') }}">
            <i class="fas fa-chart-pie me-1"></i>Resumo &amp; Comissões
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dono.financeiro.caixa*') ? 'active bg-pink' : 'text-muted' }}"
           href="{{ route('dono.financeiro.caixa.index') }}">
            <i class="fas fa-vault me-1"></i>Caixa operacional
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('dono.financeiro.despesas*') ? 'active bg-pink' : 'text-muted' }}"
           href="{{ route('dono.financeiro.despesas.index') }}">
            <i class="fas fa-file-invoice-dollar me-1"></i>Despesas
        </a>
    </li>
</ul>
