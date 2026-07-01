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
            UserRole::Dono,
            UserRole::Atendente => $this->menuDono(),
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
            ],
        ]];
    }

    private function menuDono(): array
    {
        return [[
            'label' => 'Gestão',
            'items' => [
                ['route' => 'dono.dashboard',            'icon' => 'fa-chart-pie',     'label' => 'Dashboard',         'active_pattern' => 'dono.dashboard'],
                ['route' => 'dono.agendamentos.index',   'icon' => 'fa-calendar-check','label' => 'Agendamentos',      'active_pattern' => ['dono.agendamentos.index', 'dono.agendamentos.show']],
                ['route' => 'dono.agendamentos.create',  'icon' => 'fa-plus-circle',   'label' => 'Novo Agendamento',  'active_pattern' => 'dono.agendamentos.create'],
                ['route' => 'dono.clientes.index',       'icon' => 'fa-users',         'label' => 'Clientes',          'active_pattern' => 'dono.clientes*'],
                ['route' => 'dono.financeiro.index',     'icon' => 'fa-cash-register', 'label' => 'Caixa & Comissões', 'active_pattern' => 'dono.financeiro*'],
                ['route' => 'dono.cupons.index',         'icon' => 'fa-ticket',        'label' => 'Cupons',            'active_pattern' => 'dono.cupons*'],
                ['route' => 'dono.vales.index',          'icon' => 'fa-gift',          'label' => 'Vale-presente',     'active_pattern' => 'dono.vales*'],
                ['route' => 'dono.produtos.index',       'icon' => 'fa-box',           'label' => 'Produtos',          'active_pattern' => 'dono.produtos*'],
                ['route' => 'dono.galeria.index',        'icon' => 'fa-images',        'label' => 'Galeria',           'active_pattern' => 'dono.galeria*'],
                ['route' => 'dono.folgas.index',         'icon' => 'fa-umbrella-beach','label' => 'Folgas',            'active_pattern' => 'dono.folgas*'],
                ['route' => 'dono.config.edit',          'icon' => 'fa-gear',          'label' => 'Configurações',     'active_pattern' => 'dono.config*'],
            ],
        ]];
    }

    private function menuManicure(): array
    {
        return [[
            'label' => 'Minha Área',
            'items' => [
                ['route' => 'manicure.dashboard',     'icon' => 'fa-chart-pie',     'label' => 'Dashboard',     'active_pattern' => 'manicure.dashboard'],
                ['route' => 'manicure.agenda.index',  'icon' => 'fa-calendar-alt',  'label' => 'Minha Agenda',  'active_pattern' => 'manicure.agenda*'],
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
