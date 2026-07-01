<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $cliente = $user->cliente;

        $proximosAgendamentos = $cliente
            ? $cliente->agendamentos()
                ->where('data_hora_inicio', '>=', now())
                ->whereNotIn('status', ['cancelado', 'nao_compareceu', 'concluido'])
                ->with(['manicure', 'servicos', 'salao', 'avaliacao'])
                ->orderBy('data_hora_inicio')
                ->take(5)
                ->get()
            : collect();

        $historico = $cliente
            ? $cliente->agendamentos()
                ->whereIn('status', ['concluido'])
                ->with(['manicure', 'servicos', 'salao', 'avaliacao'])
                ->orderByDesc('data_hora_inicio')
                ->take(5)
                ->get()
            : collect();

        $totalVisitas = $cliente?->total_visitas ?? 0;
        $totalGasto = $cliente?->total_gasto ?? 0;
        $pontos = $cliente?->pontos_fidelidade ?? 0;

        return view('cliente.dashboard', compact(
            'user', 'cliente', 'proximosAgendamentos',
            'historico', 'totalVisitas', 'totalGasto', 'pontos'
        ));
    }
}
