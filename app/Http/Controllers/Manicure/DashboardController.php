<?php

namespace App\Http\Controllers\Manicure;

use App\Http\Controllers\Controller;
use App\Repositories\DashboardRepository;

class DashboardController extends Controller
{
    public function __construct(private DashboardRepository $repo) {}

    public function index()
    {
        $manicure = auth()->user()->manicure;

        if (!$manicure) {
            return view('manicure.dashboard', ['manicure' => null]);
        }

        return view('manicure.dashboard', array_merge(
            ['manicure' => $manicure],
            [
                'agendamentosHoje'     => $this->repo->manicureHoje($manicure),
                'proximoAgendamento'   => $this->repo->manicureProximo($manicure),
                'proximosAgendamentos' => $this->repo->manicureProximos7Dias($manicure),
            ],
            $this->repo->manicureMetricasMes($manicure)
        ));
    }
}
