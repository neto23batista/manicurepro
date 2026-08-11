<?php

namespace App\View\Components;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\View\Component;
use Illuminate\View\View;

class Sidebar extends Component
{
    public function render(): View
    {
        /** @var User|null $user */
        $user = auth()->user();

        return view('components.sidebar', [
            'user'  => $user,
            'menus' => $this->menusParaRole($user),
        ]);
    }

    /**
     * Estrutura de menu data-driven por role.
     * Cada grupo: ['label' => 'Gestão', 'items' => [['route' => ..., 'icon' => ..., 'label' => ..., 'active_pattern' => ...]]]
     */
    private function menusParaRole(?User $user): array
    {
        if (!$user) return [];

        $role = $user->roleEnum();

        return match ($role) {
            UserRole::Admin     => $this->menuAdmin(),
            UserRole::Dono      => $this->menuDono(),
            UserRole::Atendente => $this->menuAtendente(),
            UserRole::Manicure  => $this->menuManicure(),
            UserRole::Cliente   => $this->menuCliente(),
            default             => [],
        };
    }

    private function menuAdmin(): array
    {
        return [[
            'label' => 'Administração',
            'items' => [
                ['route' => 'admin.dashboard',       'icon' => 'fa-chart-pie',      'label' => 'Dashboard',  'active_pattern' => 'admin.dashboard'],
                ['route' => 'admin.saloes.index',    'icon' => 'fa-store',          'label' => 'Meu Salão',  'active_pattern' => 'admin.saloes*'],
                ['route' => 'admin.manicures.index', 'icon' => 'fa-hand-sparkles',  'label' => 'Manicures',  'active_pattern' => 'admin.manicures*'],
                ['route' => 'admin.servicos.index',  'icon' => 'fa-spa',            'label' => 'Serviços',   'active_pattern' => 'admin.servicos*'],
                ['route' => 'admin.categorias.index','icon' => 'fa-tags',           'label' => 'Categorias', 'active_pattern' => 'admin.categorias*'],
                ['route' => 'admin.usuarios.index',  'icon' => 'fa-users',          'label' => 'Usuários',   'active_pattern' => 'admin.usuarios*'],
                ['route' => 'admin.relatorios.index','icon' => 'fa-file-lines',     'label' => 'Relatórios', 'active_pattern' => 'admin.relatorios*'],
                ['route' => 'admin.saude',           'icon' => 'fa-heart-pulse',    'label' => 'Saúde',      'active_pattern' => 'admin.saude'],
            ],
        ]];
    }

    private function menuDono(): array
    {
        $items = [
            ['route' => 'dono.dashboard',            'icon' => 'fa-chart-pie',     'label' => 'Dashboard',         'active_pattern' => 'dono.dashboard'],
            ['route' => 'dono.agendamentos.index',   'icon' => 'fa-calendar-check','label' => 'Agendamentos',      'active_pattern' => ['dono.agendamentos.index', 'dono.agendamentos.show']],
            ['route' => 'dono.agendamentos.semana',  'icon' => 'fa-calendar-week','label' => 'Agenda semanal',     'active_pattern' => 'dono.agendamentos.semana'],
            ['route' => 'dono.agendamentos.create',  'icon' => 'fa-plus-circle',   'label' => 'Novo Agendamento',  'active_pattern' => 'dono.agendamentos.create'],
            ['route' => 'dono.clientes.index',       'icon' => 'fa-users',         'label' => 'Clientes',          'active_pattern' => 'dono.clientes*'],
            ['route' => 'dono.financeiro.index',           'icon' => 'fa-cash-register', 'label' => 'Caixa & Comissões', 'active_pattern' => 'dono.financeiro.index'],
            ['route' => 'dono.financeiro.caixa.index',     'icon' => 'fa-vault',          'label' => 'Caixa operacional', 'active_pattern' => 'dono.financeiro.caixa*'],
            ['route' => 'dono.financeiro.despesas.index',  'icon' => 'fa-file-invoice-dollar', 'label' => 'Despesas', 'active_pattern' => 'dono.financeiro.despesas*'],
        ];

        // Stub NF-e — só aparece com manicure.fiscal.enabled (NÃO emite SEFAZ)
        if (config('manicure.fiscal.enabled')) {
            $items[] = [
                'route' => 'dono.notas-fiscais.index',
                'icon' => 'fa-file-invoice',
                'label' => 'Notas fiscais (stub)',
                'active_pattern' => 'dono.notas-fiscais*',
            ];
        }

        $items = array_merge($items, [
            ['route' => 'dono.cupons.index',         'icon' => 'fa-ticket',        'label' => 'Cupons',            'active_pattern' => 'dono.cupons*'],
            ['route' => 'dono.pacotes.index',        'icon' => 'fa-layer-group',   'label' => 'Pacotes',           'active_pattern' => 'dono.pacotes*'],
            ['route' => 'dono.vales.index',          'icon' => 'fa-gift',          'label' => 'Vale-presente',     'active_pattern' => 'dono.vales*'],
            ['route' => 'dono.produtos.index',       'icon' => 'fa-box',           'label' => 'Produtos',          'active_pattern' => 'dono.produtos*'],
            ['route' => 'dono.fornecedores.index',   'icon' => 'fa-truck',         'label' => 'Fornecedores',      'active_pattern' => 'dono.fornecedores*'],
            ['route' => 'dono.estoque.inventario.create', 'icon' => 'fa-clipboard-check', 'label' => 'Inventário', 'active_pattern' => 'dono.estoque.inventario*'],
            ['route' => 'dono.estoque.relatorio',    'icon' => 'fa-chart-bar',     'label' => 'Estoque relatório', 'active_pattern' => 'dono.estoque.relatorio*'],
            ['route' => 'dono.galeria.index',        'icon' => 'fa-images',        'label' => 'Galeria',           'active_pattern' => 'dono.galeria*'],
            ['route' => 'dono.folgas.index',         'icon' => 'fa-umbrella-beach','label' => 'Folgas',            'active_pattern' => ['dono.folgas*', 'dono.feriados*']],
            ['route' => 'dono.disponibilidades.index','icon' => 'fa-clock',        'label' => 'Disponibilidade',   'active_pattern' => 'dono.disponibilidades*'],
            ['route' => 'dono.auditoria.index',      'icon' => 'fa-clipboard-list','label' => 'Auditoria',         'active_pattern' => 'dono.auditoria*'],
            ['route' => 'dono.config.edit',          'icon' => 'fa-gear',          'label' => 'Configurações',     'active_pattern' => 'dono.config*'],
        ]);

        return [['label' => 'Gestão', 'items' => $items]];
    }

    /** Atendente: operação do salão; itens sensíveis só com grant extra. */
    private function menuAtendente(): array
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $items = [
            ['route' => 'dono.dashboard',            'icon' => 'fa-chart-pie',     'label' => 'Dashboard',         'active_pattern' => 'dono.dashboard'],
            ['route' => 'dono.agendamentos.index',   'icon' => 'fa-calendar-check','label' => 'Agendamentos',      'active_pattern' => ['dono.agendamentos.index', 'dono.agendamentos.show']],
            ['route' => 'dono.agendamentos.semana',  'icon' => 'fa-calendar-week','label' => 'Agenda semanal',     'active_pattern' => 'dono.agendamentos.semana'],
            ['route' => 'dono.agendamentos.create',  'icon' => 'fa-plus-circle',   'label' => 'Novo Agendamento',  'active_pattern' => 'dono.agendamentos.create'],
            ['route' => 'dono.clientes.index',       'icon' => 'fa-users',         'label' => 'Clientes',          'active_pattern' => 'dono.clientes*'],
            ['route' => 'dono.cupons.index',         'icon' => 'fa-ticket',        'label' => 'Cupons',            'active_pattern' => 'dono.cupons*'],
            ['route' => 'dono.pacotes.index',        'icon' => 'fa-layer-group',   'label' => 'Pacotes',           'active_pattern' => 'dono.pacotes*'],
            ['route' => 'dono.produtos.index',       'icon' => 'fa-box',           'label' => 'Produtos',          'active_pattern' => 'dono.produtos*'],
            ['route' => 'dono.fornecedores.index',   'icon' => 'fa-truck',         'label' => 'Fornecedores',      'active_pattern' => 'dono.fornecedores*'],
            ['route' => 'dono.estoque.inventario.create', 'icon' => 'fa-clipboard-check', 'label' => 'Inventário', 'active_pattern' => 'dono.estoque.inventario*'],
            ['route' => 'dono.estoque.relatorio',    'icon' => 'fa-chart-bar',     'label' => 'Estoque relatório', 'active_pattern' => 'dono.estoque.relatorio*'],
            ['route' => 'dono.galeria.index',        'icon' => 'fa-images',        'label' => 'Galeria',           'active_pattern' => 'dono.galeria*'],
            ['route' => 'dono.folgas.index',         'icon' => 'fa-umbrella-beach','label' => 'Folgas',            'active_pattern' => ['dono.folgas*', 'dono.feriados*']],
            ['route' => 'dono.disponibilidades.index','icon' => 'fa-clock',        'label' => 'Disponibilidade',   'active_pattern' => 'dono.disponibilidades*'],
        ];

        if ($user?->hasExtraPermission('financeiro.view')) {
            $items[] = ['route' => 'dono.financeiro.index', 'icon' => 'fa-cash-register', 'label' => 'Caixa & Comissões', 'active_pattern' => 'dono.financeiro.index'];
        }
        if ($user?->hasExtraPermission('financeiro.caixa')) {
            $items[] = ['route' => 'dono.financeiro.caixa.index', 'icon' => 'fa-vault', 'label' => 'Caixa operacional', 'active_pattern' => 'dono.financeiro.caixa*'];
        }
        if ($user?->hasExtraPermission('financeiro.despesas')) {
            $items[] = ['route' => 'dono.financeiro.despesas.index', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Despesas', 'active_pattern' => 'dono.financeiro.despesas*'];
        }
        if ($user?->hasExtraPermission('vales.manage')) {
            $items[] = ['route' => 'dono.vales.index', 'icon' => 'fa-gift', 'label' => 'Vale-presente', 'active_pattern' => 'dono.vales*'];
        }
        if ($user?->hasExtraPermission('auditoria.view')) {
            $items[] = ['route' => 'dono.auditoria.index', 'icon' => 'fa-clipboard-list', 'label' => 'Auditoria', 'active_pattern' => 'dono.auditoria*'];
        }
        if ($user?->hasExtraPermission('config.manage')) {
            $items[] = ['route' => 'dono.config.edit', 'icon' => 'fa-gear', 'label' => 'Configurações', 'active_pattern' => 'dono.config*'];
        }

        return [['label' => 'Gestão', 'items' => $items]];
    }

    private function menuManicure(): array
    {
        return [[
            'label' => 'Minha Área',
            'items' => [
                ['route' => 'manicure.dashboard',     'icon' => 'fa-chart-pie',     'label' => 'Dashboard',     'active_pattern' => 'manicure.dashboard'],
                ['route' => 'manicure.agenda.index',  'icon' => 'fa-calendar-alt',  'label' => 'Minha Agenda',  'active_pattern' => 'manicure.agenda*'],
                ['route' => 'manicure.disponibilidade.edit', 'icon' => 'fa-clock', 'label' => 'Disponibilidade', 'active_pattern' => 'manicure.disponibilidade*'],
                ['route' => 'manicure.folgas.index',  'icon' => 'fa-umbrella-beach','label' => 'Minhas Folgas', 'active_pattern' => 'manicure.folgas*'],
            ],
        ]];
    }

    private function menuCliente(): array
    {
        return [[
            'label' => 'Minha Área',
            'items' => [
                ['route' => 'cliente.dashboard',           'icon' => 'fa-home',          'label' => 'Início',             'active_pattern' => 'cliente.dashboard'],
                ['route' => 'cliente.agendamentos.index',  'icon' => 'fa-calendar-check','label' => 'Meus Agendamentos',  'active_pattern' => 'cliente.agendamentos*'],
                ['route' => 'cliente.agendamentos.create', 'icon' => 'fa-plus-circle',   'label' => 'Novo Agendamento',   'active_pattern' => null],
                ['route' => 'cliente.fidelidade.index',    'icon' => 'fa-gem',           'label' => 'Fidelidade',         'active_pattern' => 'cliente.fidelidade*'],
                ['route' => 'cliente.lista-espera.index',  'icon' => 'fa-bell',          'label' => 'Lista de Espera',    'active_pattern' => 'cliente.lista-espera*'],
            ],
        ]];
    }
}
