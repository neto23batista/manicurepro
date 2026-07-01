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

        if (!$salao) {
            return view('dono.dashboard', ['salao' => null]);
        }

        return view('dono.dashboard', array_merge(
            ['salao' => $salao],
            $this->repo->donoResumoHoje($salao),
            $this->repo->donoResumoMes($salao),
            [
                'manicures'           => $this->repo->donoManicures($salao),
                'proximosAgendamentos'=> $this->repo->donoProximos($salao),
                'dadosSemana'         => $this->repo->donoDadosSemana($salao),
                'servicosPopulares'   => $this->repo->donoServicosPopulares($salao),
            ]
        ));
    }
}
