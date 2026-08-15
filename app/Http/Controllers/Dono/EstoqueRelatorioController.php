<?php

namespace App\Http\Controllers\Dono;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use App\Models\Salao;
use App\Services\EstoqueService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EstoqueRelatorioController extends Controller
{
    public function __construct(private EstoqueService $estoque) {}

    private function salaoId(): int
    {
        return (int) (auth()->user()->salao_id ?? Salao::principalId());
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Produto::class);

        $periodo = max(7, min(365, (int) $request->input('periodo', 30)));
        $relatorio = $this->estoque->relatorio($this->salaoId(), $periodo);

        return view('dono.estoque.relatorio', [
            'relatorio' => $relatorio,
            'periodo'   => $periodo,
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Produto::class);

        $periodo = max(7, min(365, (int) $request->input('periodo', 30)));
        $relatorio = $this->estoque->relatorio($this->salaoId(), $periodo);
        $filename = 'estoque-relatorio-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($relatorio, $periodo) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

            fputcsv($out, [
                'Produto',
                'Fornecedor',
                'Estoque',
                'Unidade',
                'Custo',
                'Venda',
                'Margem %',
                'Margem R$',
                "Saídas {$periodo}d",
                'Giro',
                'Parado',
            ], ';');

            foreach ($relatorio['itens'] as $item) {
                fputcsv($out, [
                    $item['produto']->nome,
                    $item['produto']->fornecedor->nome ?? '',
                    number_format($item['estoque_atual'], 3, '.', ''),
                    $item['produto']->unidade,
                    number_format($item['preco_custo'], 2, '.', ''),
                    number_format($item['preco_venda'], 2, '.', ''),
                    $item['margem_pct'] !== null ? number_format($item['margem_pct'], 1, '.', '') : '',
                    number_format($item['margem_valor'], 2, '.', ''),
                    number_format($item['saidas_periodo'], 3, '.', ''),
                    number_format($item['giro'], 2, '.', ''),
                    $item['parado'] ? 'sim' : 'nao',
                ], ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
