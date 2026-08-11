<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracaoSalao;
use App\Repositories\DashboardRepository;
use App\Services\ClienteSegmentacao;
use App\Services\OnboardingService;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardRepository $repo,
        private ClienteSegmentacao $crm,
        private OnboardingService $onboarding,
    ) {}

    public function index()
    {
        $user = auth()->user();
        $salao = $user->salao;

        if (! $salao) {
            return view('dono.dashboard', [
                'salao'        => null,
                'quickActions' => $this->quickActions(),
                'baixoEstoque' => 0,
                'alertas'      => [],
                'onboarding'   => null,
            ]);
        }

        $config = $salao->configuracao ?? ConfiguracaoSalao::create(['salao_id' => $salao->id]);

        // Wizard de 1º acesso — só dono/admin
        if (($user->isDono() || $user->isSuperAdmin())
            && $this->onboarding->shouldForceWizard($salao, $config)
            && ! request()->boolean('skip_onboarding')) {
            return redirect()->route('dono.onboarding.show');
        }

        $onboardingPayload = null;
        if ($user->isDono() || $user->isSuperAdmin()) {
            $progress = $this->onboarding->progress($salao);
            $onboardingPayload = array_merge($progress, [
                'show' => $this->onboarding->shouldShowChecklist($salao, $config),
            ]);
        }

        return view('dono.dashboard', array_merge(
            [
                'salao'        => $salao,
                'quickActions' => $this->quickActions(),
                'onboarding'   => $onboardingPayload,
            ],
            $this->repo->donoResumoHoje($salao),
            $this->repo->donoResumoMes($salao),
            [
                'manicures'            => $this->repo->donoManicures($salao),
                'proximosAgendamentos' => $this->repo->donoProximos($salao),
                'dadosSemana'          => $this->repo->donoDadosSemana($salao),
                'servicosPopulares'    => $this->repo->donoServicosPopulares($salao),
                'baixoEstoque'         => $this->repo->donoBaixoEstoque($salao),
                'alertas'              => $this->repo->donoAlertasNegocio($salao, $this->crm),
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
