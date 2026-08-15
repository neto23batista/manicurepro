<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\DashboardRepository;

class DashboardController extends Controller
{
    public function __construct(private DashboardRepository $repo) {}

    public function index()
    {
        return view('admin.dashboard', array_merge(
            $this->repo->adminTotais(),
            $this->repo->adminAgendamentosResumo(),
            [
                'saloesMaisAtivos'     => $this->repo->topSaloes(),
                'agendamentosRecentes' => $this->repo->agendamentosRecentes(),
                'dadosMeses'           => $this->repo->dadosMeses(),
            ],
        ));
    }
}
