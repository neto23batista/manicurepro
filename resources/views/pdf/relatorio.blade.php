<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        .header { background: #e91e8c; color: white; padding: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; font-weight: bold; }
        .header p { margin-top: 4px; opacity: .85; }
        .stats { display: flex; gap: 16px; margin-bottom: 20px; }
        .stat-box { flex: 1; border: 1px solid #e91e8c; border-radius: 6px; padding: 12px; text-align: center; }
        .stat-box .value { font-size: 18px; font-weight: bold; color: #e91e8c; }
        .stat-box .label { font-size: 10px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #e91e8c; color: white; padding: 8px; text-align: left; font-size: 10px; }
        td { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
        tr:nth-child(even) { background: #fdf2f8; }
        .footer { text-align: center; font-size: 9px; color: #999; margin-top: 20px; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 9px; background: #e91e8c22; color: #e91e8c; }
    </style>
</head>
<body>
    <div class="header">
        <h1>💅 {{ config('app.name') }} — Relatório de Atendimentos</h1>
        <p>Período: {{ $dataInicio->format('d/m/Y') }} até {{ $dataFim->format('d/m/Y') }}</p>
        <p>Gerado em: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="value">{{ $resumo['total'] }}</div>
            <div class="label">Total de Atendimentos</div>
        </div>
        <div class="stat-box">
            <div class="value">R$ {{ number_format($resumo['faturamento'], 2, ',', '.') }}</div>
            <div class="label">Faturamento Total</div>
        </div>
        <div class="stat-box">
            <div class="value">R$ {{ $resumo['total'] > 0 ? number_format($resumo['faturamento'] / $resumo['total'], 2, ',', '.') : '0,00' }}</div>
            <div class="label">Ticket Médio</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Manicure</th>
                <th>Serviços</th>
                <th>Data</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($agendamentos as $ag)
                <tr>
                    <td>{{ $ag->id }}</td>
                    <td>{{ $ag->nome_cliente_exibido }}</td>
                    <td>{{ $ag->manicure?->nome }}</td>
                    <td>{{ $ag->servicos->pluck('nome')->implode(', ') }}</td>
                    <td>{{ $ag->data_hora_inicio->format('d/m/Y H:i') }}</td>
                    <td><strong>R$ {{ number_format($ag->valor_total - $ag->valor_desconto, 2, ',', '.') }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        {{ config('app.name') }} — Relatório gerado automaticamente
    </div>
</body>
</html>
