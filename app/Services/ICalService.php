<?php

namespace App\Services;

use App\Models\Agendamento;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Gera arquivos iCalendar (.ics) e links de template do Google Calendar
 * (sem OAuth — o cliente/profissional importa manualmente).
 */
class ICalService
{
    public function paraAgendamento(Agendamento $agendamento): string
    {
        return $this->calendario([$agendamento], incluirCliente: false);
    }

    /**
     * @param  iterable<Agendamento>  $agendamentos
     */
    public function paraAgendamentos(iterable $agendamentos): string
    {
        return $this->calendario($agendamentos, incluirCliente: true);
    }

    public function nomeArquivo(Agendamento $agendamento): string
    {
        return 'agendamento-'.$agendamento->id.'-'
            .$agendamento->data_hora_inicio->format('Y-m-d').'.ics';
    }

    public function nomeArquivoAgenda(Carbon $inicio, Carbon $fim): string
    {
        $de = $inicio->toDateString();
        $ate = $fim->toDateString();

        return $de === $ate
            ? "agenda-{$de}.ics"
            : "agenda-{$de}-a-{$ate}.ics";
    }

    /**
     * URL de template do Google Calendar (abre o formulário "criar evento").
     * Não requer OAuth nem sync bidirecional.
     */
    public function linkGoogleCalendar(Agendamento $agendamento): string
    {
        $agendamento->loadMissing(['salao', 'manicure', 'servicos']);

        $meta = $this->metaEvento($agendamento, incluirCliente: false);
        $dates = $agendamento->data_hora_inicio->copy()->utc()->format('Ymd\THis\Z')
            .'/'.$agendamento->data_hora_fim->copy()->utc()->format('Ymd\THis\Z');

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action'   => 'TEMPLATE',
            'text'     => $meta['titulo'],
            'dates'    => $dates,
            'details'  => $meta['descricao'],
            'location' => $meta['local'],
        ]);
    }

    /**
     * @param  iterable<Agendamento>  $agendamentos
     */
    private function calendario(iterable $agendamentos, bool $incluirCliente = false): string
    {
        $itens = Collection::make($agendamentos);
        $itens->each(fn (Agendamento $a) => $a->loadMissing(['salao', 'manicure', 'servicos', 'cliente']));

        $primeiro = $itens->first();
        $salaoNome = ($primeiro !== null ? $primeiro->salao->nome : null) ?? config('app.name');
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'manicurepro';

        $linhas = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.$this->escapar($salaoNome).'//Agenda//PT-BR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        foreach ($itens as $agendamento) {
            $linhas = array_merge($linhas, $this->vevent($agendamento, $host, $incluirCliente));
        }

        $linhas[] = 'END:VCALENDAR';

        return implode("\r\n", $linhas)."\r\n";
    }

    /**
     * @return list<string>
     */
    private function vevent(Agendamento $agendamento, string $host, bool $incluirCliente): array
    {
        $meta = $this->metaEvento($agendamento, $incluirCliente);

        return [
            'BEGIN:VEVENT',
            'UID:agendamento-'.$agendamento->id.'@'.$host,
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$agendamento->data_hora_inicio->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$agendamento->data_hora_fim->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escapar($meta['titulo']),
            'DESCRIPTION:'.$this->escapar($meta['descricao']),
            'LOCATION:'.$this->escapar($meta['local']),
            'STATUS:'.($agendamento->status === 'cancelado' ? 'CANCELLED' : 'CONFIRMED'),
            'END:VEVENT',
        ];
    }

    /**
     * @return array{titulo: string, descricao: string, local: string}
     */
    private function metaEvento(Agendamento $agendamento, bool $incluirCliente = false): array
    {
        $salao = $agendamento->salao;
        $servicos = $agendamento->servicos->pluck('nome')->implode(', ');
        $titulo = ($salao->nome ?? 'Agendamento').($servicos ? ' — '.$servicos : '');

        $descricaoPartes = array_filter([
            $servicos ? 'Serviços: '.$servicos : null,
            $agendamento->manicure ? 'Profissional: '.$agendamento->manicure->nome : null,
            $incluirCliente ? 'Cliente: '.$agendamento->nome_cliente_exibido : null,
            $agendamento->observacoes ? 'Obs.: '.$agendamento->observacoes : null,
        ]);

        return [
            'titulo'    => $titulo,
            'descricao' => implode("\n", $descricaoPartes),
            'local'     => $salao->endereco_completo ?? '',
        ];
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
