<?php

namespace App\Services;

use App\Models\Agendamento;

/**
 * Gera arquivos iCalendar (.ics) para que o cliente adicione o agendamento
 * à sua agenda (Google Agenda, Apple, Outlook…).
 */
class ICalService
{
    public function paraAgendamento(Agendamento $agendamento): string
    {
        $agendamento->loadMissing(['salao', 'manicure', 'servicos']);

        $salao = $agendamento->salao;
        $servicos = $agendamento->servicos->pluck('nome')->implode(', ');
        $titulo = ($salao?->nome ?? 'Agendamento') . ($servicos ? ' — ' . $servicos : '');

        $descricaoPartes = array_filter([
            $servicos ? 'Serviços: ' . $servicos : null,
            $agendamento->manicure ? 'Profissional: ' . $agendamento->manicure->nome : null,
            $agendamento->observacoes ? 'Obs.: ' . $agendamento->observacoes : null,
        ]);

        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'manicurepro';

        $linhas = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//' . $this->escapar($salao?->nome ?? config('app.name')) . '//Agendamento//PT-BR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:agendamento-' . $agendamento->id . '@' . $host,
            'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:' . $agendamento->data_hora_inicio->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:' . $agendamento->data_hora_fim->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:' . $this->escapar($titulo),
            'DESCRIPTION:' . $this->escapar(implode("\n", $descricaoPartes)),
            'LOCATION:' . $this->escapar($salao?->endereco_completo ?? ''),
            'STATUS:' . ($agendamento->status === 'cancelado' ? 'CANCELLED' : 'CONFIRMED'),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        // iCalendar exige quebras de linha CRLF.
        return implode("\r\n", $linhas) . "\r\n";
    }

    public function nomeArquivo(Agendamento $agendamento): string
    {
        return 'agendamento-' . $agendamento->id . '-'
            . $agendamento->data_hora_inicio->format('Y-m-d') . '.ics';
    }

    /**
     * Escapa caracteres especiais conforme RFC 5545 (vírgula, ponto-e-vírgula,
     * barra invertida e quebras de linha).
     */
    private function escapar(string $texto): string
    {
        $texto = str_replace(['\\', ';', ','], ['\\\\', '\\;', '\\,'], $texto);
        return str_replace(["\r\n", "\n", "\r"], '\\n', $texto);
    }
}
