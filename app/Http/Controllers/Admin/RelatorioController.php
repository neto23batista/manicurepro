<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Salao;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RelatorioController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request)
    {
        $dados = $this->reports->gerarRelatorio(
            salaoId:    $request->salao_id,
            dataInicio: $request->data_inicio ? Carbon::parse($request->data_inicio) : null,
            dataFim:    $request->data_fim ? Carbon::parse($request->data_fim) : null,
        );

        return view('admin.relatorios.index', array_merge($dados, [
            'saloes'   => Salao::where('ativo', true)->orderBy('nome')->get(),
            'salaoId'  => $request->salao_id,
        ]));
    }

    public function exportPdf(Request $request)
    {
        $dados = $this->reports->gerarRelatorio(
            salaoId:    $request->salao_id,
            dataInicio: $request->data_inicio ? Carbon::parse($request->data_inicio) : null,
            dataFim:    $request->data_fim ? Carbon::parse($request->data_fim) : null,
        );

        $pdf = Pdf::loadView('pdf.relatorio', $dados)
            ->setPaper('a4', 'landscape');

        return $pdf->download(
            'relatorio-' . $dados['dataInicio']->format('Y-m-d') . '-' . $dados['dataFim']->format('Y-m-d') . '.pdf'
        );
    }
}
