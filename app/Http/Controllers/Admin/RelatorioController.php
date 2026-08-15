<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Salao;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatorioController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request)
    {
        $dados = $this->reports->gerarRelatorio(
            salaoId: $request->salao_id,
            dataInicio: $request->data_inicio ? Carbon::parse($request->data_inicio) : null,
            dataFim: $request->data_fim ? Carbon::parse($request->data_fim) : null,
        );

        return view('admin.relatorios.index', array_merge($dados, [
            'saloes'  => Salao::where('ativo', true)->orderBy('nome')->get(),
            'salaoId' => $request->salao_id,
        ]));
    }

    public function exportPdf(Request $request)
    {
        $dados = $this->reports->gerarRelatorio(
            salaoId: $request->salao_id,
            dataInicio: $request->data_inicio ? Carbon::parse($request->data_inicio) : null,
            dataFim: $request->data_fim ? Carbon::parse($request->data_fim) : null,
        );

        $pdf = Pdf::loadView('pdf.relatorio', $dados)
            ->setPaper('a4', 'landscape');

        return $pdf->download(
            'relatorio-'.$dados['dataInicio']->format('Y-m-d').'-'.$dados['dataFim']->format('Y-m-d').'.pdf',
        );
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $dados = $this->reports->gerarRelatorio(
            salaoId: $request->salao_id,
            dataInicio: $request->data_inicio ? Carbon::parse($request->data_inicio) : null,
            dataFim: $request->data_fim ? Carbon::parse($request->data_fim) : null,
        );

        $filename = 'relatorio-'.$dados['dataInicio']->format('Y-m-d').'-'.$dados['dataFim']->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($dados) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

            fputcsv($out, [
                'Data',
                'Horário',
                'Cliente',
                'Manicure',
                'Serviços',
                'Status',
                'Valor total',
                'Desconto',
                'Líquido',
                'Salão',
            ], ';');

            foreach ($dados['agendamentos'] as $ag) {
                fputcsv($out, [
                    $ag->data_hora_inicio->format('Y-m-d'),
                    $ag->data_hora_inicio->format('H:i'),
                    $ag->nome_cliente_exibido ?? $ag->cliente->nome ?? '',
                    $ag->manicure->nome ?? '',
                    $ag->servicos->pluck('nome')->implode(', '),
                    $ag->status,
                    number_format((float) $ag->valor_total, 2, '.', ''),
                    number_format((float) $ag->valor_desconto, 2, '.', ''),
                    number_format((float) $ag->valor_total - (float) $ag->valor_desconto, 2, '.', ''),
                    $ag->salao->nome ?? '',
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
