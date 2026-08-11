<?php

namespace App\Services;

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Cupom;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;

/**
 * Segmentação CRM de clientes (novo / recorrente / inativo / VIP / risco churn).
 * Independente do programa de fidelidade (pontos/resgate).
 */
class ClienteSegmentacao
{
    public const SEGMENTOS = [
        'novo',
        'recorrente',
        'inativo',
        'vip',
        'risco_churn',
    ];

    public const LABELS = [
        'novo'         => 'Novo',
        'recorrente'   => 'Recorrente',
        'inativo'      => 'Inativo',
        'vip'          => 'VIP',
        'risco_churn'  => 'Risco churn',
    ];

    public const CORES = [
        'novo'         => 'info',
        'recorrente'   => 'primary',
        'inativo'      => 'secondary',
        'vip'          => 'warning',
        'risco_churn'  => 'danger',
    ];

    public function label(string $segmento): string
    {
        return self::LABELS[$segmento] ?? $segmento;
    }

    public function cor(string $segmento): string
    {
        return self::CORES[$segmento] ?? 'secondary';
    }

    public function isSegmentoValido(?string $segmento): bool
    {
        return $segmento !== null && $segmento !== '' && in_array($segmento, self::SEGMENTOS, true);
    }

    /**
     * Segmentos aos quais o cliente pertence (podem sobrepor, ex.: VIP + recorrente).
     *
     * @return list<string>
     */
    public function segmentosDe(Cliente $cliente, ?Carbon $ultimaVisita = null): array
    {
        $ultima = $ultimaVisita ?? $this->resolverUltimaVisita($cliente);
        $out = [];

        if ($this->ehNovo($cliente)) {
            $out[] = 'novo';
        }
        if ($this->ehVip($cliente)) {
            $out[] = 'vip';
        }
        if ($this->ehInativo($cliente, $ultima)) {
            $out[] = 'inativo';
        } elseif ($this->ehRiscoChurn($cliente, $ultima)) {
            $out[] = 'risco_churn';
        }
        if ($this->ehRecorrente($cliente, $ultima)) {
            $out[] = 'recorrente';
        }

        return $out;
    }

    public function ehNovo(Cliente $cliente): bool
    {
        $dias = (int) config('manicure.crm.novo_dias', 30);

        return $cliente->created_at !== null
            && $cliente->created_at->gte(now()->subDays($dias));
    }

    public function ehVip(Cliente $cliente): bool
    {
        $gastoMin = (float) config('manicure.crm.vip_gasto_minimo', 500);
        $visitasMin = (int) config('manicure.crm.vip_visitas_minimas', 8);

        return (float) $cliente->total_gasto >= $gastoMin
            || (int) $cliente->total_visitas >= $visitasMin;
    }

    public function ehRecorrente(Cliente $cliente, ?Carbon $ultimaVisita = null): bool
    {
        $minVisitas = (int) config('manicure.crm.recorrente_min_visitas', 3);
        // Ainda “quente”: última visita dentro da janela de risco (antes de esfriar).
        $ativoDias = (int) config('manicure.crm.risco_churn_dias', 40);

        if ((int) $cliente->total_visitas < $minVisitas) {
            return false;
        }

        $ultima = $ultimaVisita ?? $this->resolverUltimaVisita($cliente);
        if ($ultima === null) {
            return false;
        }

        return $ultima->gte(now()->subDays($ativoDias));
    }

    public function ehInativo(Cliente $cliente, ?Carbon $ultimaVisita = null): bool
    {
        $inativoDias = (int) config('manicure.crm.inativo_dias', 60);
        $cutoff = now()->subDays($inativoDias);

        if ((int) $cliente->total_visitas === 0) {
            return $cliente->created_at !== null && $cliente->created_at->lt($cutoff);
        }

        $ultima = $ultimaVisita ?? $this->resolverUltimaVisita($cliente);

        return $ultima !== null && $ultima->lt($cutoff);
    }

    public function ehRiscoChurn(Cliente $cliente, ?Carbon $ultimaVisita = null): bool
    {
        $riscoDias = (int) config('manicure.crm.risco_churn_dias', 40);
        $inativoDias = (int) config('manicure.crm.inativo_dias', 60);

        if ((int) $cliente->total_visitas < 1 || $riscoDias >= $inativoDias) {
            return false;
        }

        $ultima = $ultimaVisita ?? $this->resolverUltimaVisita($cliente);
        if ($ultima === null) {
            return false;
        }

        // Esfriando: última visita entre risco_churn_dias e inativo_dias atrás.
        return $ultima->lt(now()->subDays($riscoDias))
            && $ultima->gte(now()->subDays($inativoDias));
    }

    /**
     * Aplica filtro de segmento na listagem (sem N+1).
     *
     * @param  Builder<\App\Models\Cliente>|Relation<\App\Models\Cliente, \Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\App\Models\Cliente>|Relation<\App\Models\Cliente, \Illuminate\Database\Eloquent\Model>
     */
    public function aplicarFiltro(Builder|Relation $query, string $segmento): Builder|Relation
    {
        if (! $this->isSegmentoValido($segmento)) {
            return $query;
        }

        $concluido = AgendamentoStatus::Concluido->value;
        $ultimaSql = '(SELECT MAX(data_hora_inicio) FROM agendamentos'
            .' WHERE agendamentos.cliente_id = clientes.id'
            .' AND agendamentos.status = ?)';

        return match ($segmento) {
            'novo' => $query->where(
                'clientes.created_at',
                '>=',
                now()->subDays((int) config('manicure.crm.novo_dias', 30)),
            ),
            'vip' => $query->where(function (Builder $q) {
                $q->where('clientes.total_gasto', '>=', (float) config('manicure.crm.vip_gasto_minimo', 500))
                    ->orWhere('clientes.total_visitas', '>=', (int) config('manicure.crm.vip_visitas_minimas', 8));
            }),
            'recorrente' => $query
                ->where('clientes.total_visitas', '>=', (int) config('manicure.crm.recorrente_min_visitas', 3))
                ->whereRaw("{$ultimaSql} >= ?", [
                    $concluido,
                    now()->subDays((int) config('manicure.crm.risco_churn_dias', 40)),
                ]),
            'inativo' => $query->where(function (Builder $q) use ($ultimaSql, $concluido) {
                $cutoff = now()->subDays((int) config('manicure.crm.inativo_dias', 60));
                $q->where(function (Builder $q2) use ($ultimaSql, $concluido, $cutoff) {
                    $q2->where('clientes.total_visitas', '>=', 1)
                        ->whereRaw("{$ultimaSql} < ?", [$concluido, $cutoff]);
                })->orWhere(function (Builder $q2) use ($cutoff) {
                    $q2->where('clientes.total_visitas', 0)
                        ->where('clientes.created_at', '<', $cutoff);
                });
            }),
            'risco_churn' => $query
                ->where('clientes.total_visitas', '>=', 1)
                ->whereRaw("{$ultimaSql} < ?", [
                    $concluido,
                    now()->subDays((int) config('manicure.crm.risco_churn_dias', 40)),
                ])
                ->whereRaw("{$ultimaSql} >= ?", [
                    $concluido,
                    now()->subDays((int) config('manicure.crm.inativo_dias', 60)),
                ]),
            default => $query,
        };
    }

    /**
     * Anexa última visita concluída em lote (evita N+1 na listagem).
     *
     * @param  Builder<\App\Models\Cliente>|Relation<\App\Models\Cliente, \Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<\App\Models\Cliente>|Relation<\App\Models\Cliente, \Illuminate\Database\Eloquent\Model>
     */
    public function withUltimaVisita(Builder|Relation $query): Builder|Relation
    {
        $table = $query instanceof Relation
            ? $query->getRelated()->getTable()
            : $query->getModel()->getTable();

        $base = $query instanceof Relation ? $query->getQuery() : $query;
        if ($base->getQuery()->columns === null) {
            $query->select("{$table}.*");
        }

        $concluido = AgendamentoStatus::Concluido->value;

        return $query->addSelect([
            'ultima_visita_em' => Agendamento::query()
                ->selectRaw('MAX(data_hora_inicio)')
                ->whereColumn('cliente_id', "{$table}.id")
                ->where('status', $concluido),
        ]);
    }

    /**
     * Métricas do show: ticket médio, última/próxima visita, LTV — queries agregadas.
     *
     * @return array{
     *     ticket_medio: float,
     *     ltv: float,
     *     visitas_concluidas: int,
     *     ultima_visita: ?Carbon,
     *     proxima_visita: ?Carbon,
     *     segmentos: list<string>
     * }
     */
    public function metricas(Cliente $cliente): array
    {
        $concluido = AgendamentoStatus::Concluido->value;

        $agg = Agendamento::query()
            ->where('cliente_id', $cliente->id)
            ->where('status', $concluido)
            ->selectRaw(
                'COUNT(*) as visitas,'
                .' COALESCE(SUM(valor_total - valor_desconto), 0) as ltv,'
                .' COALESCE(AVG(valor_total - valor_desconto), 0) as ticket_medio,'
                .' MAX(data_hora_inicio) as ultima_visita'
            )
            ->first();

        $visitas = (int) ($agg->visitas ?? 0);
        $ltv = round((float) ($agg->ltv ?? 0), 2);
        // Fallback para contadores denormalizados se ainda não houver agendamentos concluídos no banco.
        if ($visitas === 0 && (float) $cliente->total_gasto > 0) {
            $ltv = round((float) $cliente->total_gasto, 2);
            $visitas = max(1, (int) $cliente->total_visitas);
        }

        $ticket = $visitas > 0
            ? round($ltv / $visitas, 2)
            : round((float) ($agg->ticket_medio ?? 0), 2);

        $ultima = ! empty($agg->ultima_visita)
            ? Carbon::parse($agg->ultima_visita)
            : null;

        $proxima = Agendamento::query()
            ->where('cliente_id', $cliente->id)
            ->where('data_hora_inicio', '>', now())
            ->whereNotIn('status', [
                AgendamentoStatus::Cancelado->value,
                AgendamentoStatus::NaoCompareceu->value,
            ])
            ->orderBy('data_hora_inicio')
            ->value('data_hora_inicio');

        return [
            'ticket_medio'       => $ticket,
            'ltv'                => $ltv > 0 ? $ltv : round((float) $cliente->total_gasto, 2),
            'visitas_concluidas' => $visitas > 0 ? $visitas : (int) $cliente->total_visitas,
            'ultima_visita'      => $ultima,
            'proxima_visita'     => $proxima ? Carbon::parse($proxima) : null,
            'segmentos'          => $this->segmentosDe($cliente, $ultima),
        ];
    }

    /**
     * Gera (ou reutiliza no mês) cupom de reativação para cliente inativo.
     * Reusa o model Cupom — não cria sistema paralelo de fidelidade.
     */
    public function gerarCupomReativacao(Cliente $cliente): Cupom
    {
        if (! $this->ehInativo($cliente)) {
            throw ValidationException::withMessages([
                'cliente' => 'Cupom de reativação só pode ser gerado para clientes no segmento inativo.',
            ]);
        }

        $cfg = config('manicure.crm.reativacao', []);
        $codigo = 'REATIVA-'.$cliente->id.'-'.now()->format('Ym');
        $validadeDias = (int) ($cfg['cupom_validade_dias'] ?? 30);

        return Cupom::firstOrCreate(
            [
                'salao_id' => $cliente->salao_id,
                'codigo'   => $codigo,
            ],
            [
                'tipo'       => $cfg['cupom_tipo'] ?? 'percentual',
                'valor'      => (float) ($cfg['cupom_valor'] ?? 15),
                'uso_maximo' => 1,
                'uso_atual'  => 0,
                'validade'   => now()->addDays($validadeDias)->toDateString(),
                'ativo'      => true,
                'origem'     => 'crm',
                'cliente_id' => $cliente->id,
            ],
        );
    }

    private function resolverUltimaVisita(Cliente $cliente): ?Carbon
    {
        if (isset($cliente->ultima_visita_em) && $cliente->ultima_visita_em) {
            return Carbon::parse($cliente->ultima_visita_em);
        }

        $raw = Agendamento::query()
            ->where('cliente_id', $cliente->id)
            ->where('status', AgendamentoStatus::Concluido->value)
            ->max('data_hora_inicio');

        return $raw ? Carbon::parse($raw) : null;
    }
}
