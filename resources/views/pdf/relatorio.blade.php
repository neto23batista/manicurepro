<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório — {{ $salaoNome }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #333; }
        .header { background: #e91e8c; color: white; padding: 16px 20px; margin-bottom: 16px; }
        .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 8px; }
        .meta { width: 100%; border-collapse: collapse; }
        .meta td { color: white; font-size: 11px; padding: 2px 0; vertical-align: top; opacity: .95; }
        .meta .label { width: 90px; opacity: .8; }
        .section-title { font-size: 13px; font-weight: bold; color: #e91e8c; margin: 0 0 8px; border-bottom: 2px solid #e91e8c; padding-bottom: 4px; }
        .stats { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .stats td { width: 16.66%; border: 1px solid #e91e8c; padding: 10px 6px; text-align: center; vertical-align: middle; }
        .stats .value { font-size: 14px; font-weight: bold; color: #e91e8c; display: block; }
        .stats .label { font-size: 9px; color: #666; display: block; margin-top: 2px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.data th { background: #e91e8c; color: white; padding: 7px 8px; text-align: left; font-size: 10px; }
        table.data th.num, table.data td.num { text-align: right; }
        table.data th.center, table.data td.center { text-align: center; }
        table.data td { padding: 5px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
        table.data tr:nth-child(even) { background: #fdf2f8; }
        table.data tfoot td { background: #fce7f3; font-weight: bold; border-top: 2px solid #e91e8c; }
        .hint { font-size: 9px; color: #777; margin: -8px 0 16px; }
        .footer { text-align: center; font-size: 9px; color: #999; margin-top: 12px; }
        .empty { text-align: center; color: #999; padding: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }} — Relatório de Atendimentos</h1>
        <table class="meta">
            <tr>
                <td class="label">Salão</td>
                <td><strong>{{ $salaoNome }}</strong></td>
            </tr>
            <tr>
                <td class="label">Período</td>
                <td><strong>{{ $dataInicio->format('d/m/Y') }}</strong> até <strong>{{ $dataFim->format('d/m/Y') }}</strong></td>
            </tr>
            <tr>
                <td class="label">Gerado em</td>
                <td>{{ now()->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Totais do período</div>
    <table class="stats">
        <tr>
            <td>
                <span class="value">{{ $resumo['total'] }}</span>
                <span class="label">Agendamentos</span>
            </td>
            <td>
                <span class="value">{{ $resumo['concluidos'] }}</span>
                <span class="label">Concluídos</span>
            </td>
            <td>
                <span class="value">{{ $resumo['cancelados'] }}</span>
                <span class="label">Cancelados</span>
            </td>
            <td>
                <span class="value">R$ {{ number_format($resumo['liquido'], 2, ',', '.') }}</span>
                <span class="label">Faturamento líquido</span>
            </td>
            <td>
                <span class="value">R$ {{ number_format($resumo['ticket_medio'], 2, ',', '.') }}</span>
                <span class="label">Ticket médio</span>
            </td>
            <td>
                <span class="value">R$ {{ number_format($resumo['comissoes'], 2, ',', '.') }}</span>
                <span class="label">Comissões</span>
            </td>
        </tr>
    </table>

    <div class="section-title">Comissão por profissional</div>
    <p class="hint">Base = serviços líquidos (valor − desconto) dos atendimentos concluídos. Taxa conforme cadastro da manicure.</p>
    <table class="data">
        <thead>
            <tr>
                <th>Profissional</th>
                <th class="center">Atend.</th>
                <th class="center">Concl.</th>
                <th class="num">Base (R$)</th>
                <th class="center">Taxa</th>
                <th class="num">Comissão (R$)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($porManicure as $m)
                <tr>
                    <td>{{ $m['nome'] }}</td>
                    <td class="center">{{ $m['total'] }}</td>
                    <td class="center">{{ $m['concluidos'] }}</td>
                    <td class="num">{{ number_format($m['base'], 2, ',', '.') }}</td>
                    <td class="center">
                        @if($m['taxa_comissao'] > 0)
                            {{ rtrim(rtrim(number_format($m['taxa_comissao'], 2, ',', '.'), '0'), ',') }}%
                        @else
                            —
                        @endif
                    </td>
                    <td class="num"><strong>{{ number_format($m['comissao'], 2, ',', '.') }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Nenhum atendimento no período</td></tr>
            @endforelse
        </tbody>
        @if($porManicure->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="3">Total</td>
                    <td class="num">{{ number_format($resumo['base_comissao'], 2, ',', '.') }}</td>
                    <td></td>
                    <td class="num">{{ number_format($resumo['comissoes'], 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="section-title">Atendimentos</div>
    <table class="data">
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Manicure</th>
                <th>Serviços</th>
                <th>Data</th>
                <th class="num">Valor líquido</th>
            </tr>
        </thead>
        <tbody>
            @forelse($agendamentos as $ag)
                <tr>
                    <td>{{ $ag->id }}</td>
                    <td>{{ $ag->nome_cliente_exibido }}</td>
                    <td>{{ $ag->manicure?->nome }}</td>
                    <td>{{ $ag->servicos->pluck('nome')->implode(', ') }}</td>
                    <td>{{ $ag->data_hora_inicio->format('d/m/Y H:i') }}</td>
                    <td class="num"><strong>R$ {{ number_format($ag->valor_total - $ag->valor_desconto, 2, ',', '.') }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Nenhum agendamento no período</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ config('app.name') }} — {{ $salaoNome }} — {{ $dataInicio->format('d/m/Y') }} a {{ $dataFim->format('d/m/Y') }}
    </div>
</body>
</html>
