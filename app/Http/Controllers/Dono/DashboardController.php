<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Repositories\DashboardRepository;

class DashboardController extends Controller
{
    public function __construct(private DashboardRepository $repo) {}

    public function index()
    {
        $salao = auth()->user()->salao;

        if (! $salao) {
            return view('dono.dashboard', [
                'salao'        => null,
                'quickActions' => $this->quickActions(),
                'baixoEstoque' => 0,
            ]);
        }

        return view('dono.dashboard', array_merge(
            [
                'salao'        => $salao,
                'quickActions' => $this->quickActions(),
            ],
            $this->repo->donoResumoHoje($salao),
            $this->repo->donoResumoMes($salao),
            [
                'manicures'            => $this->repo->donoManicures($salao),
                'proximosAgendamentos' => $this->repo->donoProximos($salao),
                'dadosSemana'          => $this->repo->donoDadosSemana($salao),
                'servicosPopulares'    => $this->repo->donoServicosPopulares($salao),
                'baixoEstoque'         => $this->repo->donoBaixoEstoque($salao),
            ],
        ));
    }

    /**
     * Ações rápidas alinhadas ao FAB + command palette (grupo Ações / navegação chave).
     */
    private function quickActions(): array
    {
        return [
            [
                'label'   => 'Novo agendamento',
                'icon'    => 'fa-calendar-plus',
                'url'     => route('dono.agendamentos.create'),
                'primary' => true,
            ],
            [
                'label' => 'Novo cliente',
                'icon'  => 'fa-user-plus',
                'url'   => route('dono.clientes.create'),
            ],
            [
                'label' => 'Novo cupom',
                'icon'  => 'fa-ticket',
                'url'   => route('dono.cupons.create'),
            ],
            [
                'label' => 'Agendamentos',
                'icon'  => 'fa-calendar-check',
                'url'   => route('dono.agendamentos.index'),
            ],
            [
                'label' => 'Clientes',
                'icon'  => 'fa-users',
                'url'   => route('dono.clientes.index'),
            ],
            [
                'label'  => 'Busca rápida',
                'icon'   => 'fa-search',
                'url'    => '#',
                'action' => 'command-palette',
                'kbd'    => 'Ctrl+K',
            ],
        ];
    }
}
