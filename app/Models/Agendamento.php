<?php

namespace App\Models;

use App\Enums\AgendamentoStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agendamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'salao_id', 'cliente_id', 'manicure_id', 'user_id',
        'data_hora_inicio', 'data_hora_fim', 'status', 'observacoes',
        'observacoes_internas', 'origem', 'valor_total', 'valor_desconto',
        'cupom_id', 'nome_cliente', 'telefone_cliente',
        'confirmado_em', 'lembrete_24h_em', 'lembrete_2h_em',
        'mp_payment_id', 'sinal_status', 'sinal_valor',
        'mp_cobranca_tipo', 'mp_total_status', 'mp_total_valor',
    ];

    protected $casts = [
        'data_hora_inicio' => 'datetime',
        'data_hora_fim'    => 'datetime',
        'valor_total'      => 'decimal:2',
        'valor_desconto'   => 'decimal:2',
        'confirmado_em'    => 'datetime',
        'lembrete_24h_em'  => 'datetime',
        'lembrete_2h_em'   => 'datetime',
        'sinal_valor'      => 'decimal:2',
        'mp_total_valor'   => 'decimal:2',
    ];

    /** Rótulos de status (value => label) para selects e filtros. */
    public const STATUS_LABELS = [
        'aguardando'     => 'Aguardando',
        'confirmado'     => 'Confirmado',
        'em_andamento'   => 'Em Andamento',
        'concluido'      => 'Concluído',
        'cancelado'      => 'Cancelado',
        'nao_compareceu' => 'Não Compareceu',
    ];

    protected static function booted(): void
    {
        static::created(function (Agendamento $agendamento) {
            $agendamento->sincronizarContadorFaltas(null, $agendamento->status);
        });

        static::updated(function (Agendamento $agendamento) {
            if (! $agendamento->wasChanged('status')) {
                return;
            }

            $agendamento->sincronizarContadorFaltas(
                $agendamento->getOriginal('status'),
                $agendamento->status,
            );
        });
    }

    /**
     * Incrementa/decrementa total_faltas do cliente vinculado ao entrar/sair de não compareceu.
     */
    public function sincronizarContadorFaltas(?string $statusAnterior, ?string $statusNovo): void
    {
        if (! $this->cliente_id) {
            return;
        }

        $nc = AgendamentoStatus::NaoCompareceu->value;

        if ($statusAnterior !== $nc && $statusNovo === $nc) {
            Cliente::whereKey($this->cliente_id)->increment('total_faltas');
        } elseif ($statusAnterior === $nc && $statusNovo !== $nc) {
            Cliente::whereKey($this->cliente_id)
                ->where('total_faltas', '>', 0)
                ->decrement('total_faltas');
        }
    }

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return BelongsTo<Manicure, $this> */
    public function manicure(): BelongsTo
    {
        return $this->belongsTo(Manicure::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Servico, $this> */
    public function servicos(): BelongsToMany
    {
        return $this->belongsToMany(Servico::class, 'agendamento_servicos')
            ->withPivot('preco', 'duracao')
            ->withTimestamps();
    }

    /** @return HasOne<Comanda, $this> */
    public function comanda(): HasOne
    {
        return $this->hasOne(Comanda::class);
    }

    /** @return HasMany<NotaFiscal, $this> */
    public function notasFiscais(): HasMany
    {
        return $this->hasMany(NotaFiscal::class);
    }

    /** @return HasMany<Pagamento, $this> */
    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }

    /** @return HasOne<Avaliacao, $this> */
    public function avaliacao(): HasOne
    {
        return $this->hasOne(Avaliacao::class);
    }

    /** @return BelongsTo<Cupom, $this> */
    public function cupom(): BelongsTo
    {
        return $this->belongsTo(Cupom::class);
    }

    public function statusEnum(): ?AgendamentoStatus
    {
        return AgendamentoStatus::tryFrom((string) $this->status);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->statusEnum()?->label() ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return $this->statusEnum()?->color() ?? 'secondary';
    }

    public function getNomeClienteExibidoAttribute(): string
    {
        $cliente = $this->cliente;

        return ($cliente !== null ? $cliente->nome : null)
            ?? $this->nome_cliente
            ?? 'Cliente não identificado';
    }

    public function getDuracaoMinutosAttribute(): int
    {
        return (int) $this->data_hora_inicio->diffInMinutes($this->data_hora_fim);
    }

    public function podeSerCancelado(): bool
    {
        return $this->statusEnum()?->podeSerCancelado() ?? false;
    }

    public function podeSerReagendado(): bool
    {
        return $this->statusEnum()?->podeSerReagendado() ?? false;
    }

    public function sinalPago(): bool
    {
        return $this->sinal_status === 'pago';
    }

    public function sinalPendente(): bool
    {
        return $this->sinal_status === 'pendente';
    }

    public function precisaSinal(): bool
    {
        return $this->sinal_status !== 'pago'
            && (bool) config('manicure.pagamento.mercadopago.enabled')
            && (bool) config('manicure.pagamento.sinal.habilitado')
            && in_array($this->status, ['aguardando', 'confirmado'], true);
    }

    public function pagamentoTotalPago(): bool
    {
        return $this->mp_total_status === 'pago';
    }

    public function precisaPagamentoTotal(): bool
    {
        if ($this->pagamentoTotalPago()) {
            return false;
        }

        if (! (bool) config('manicure.pagamento.mercadopago.enabled')
            || ! (bool) config('manicure.pagamento.total.habilitado')) {
            return false;
        }

        if (! in_array($this->status, ['aguardando', 'confirmado'], true)) {
            return false;
        }

        $liquido = max(0, (float) $this->valor_total - (float) $this->valor_desconto);
        $sinalPago = $this->sinalPago() ? (float) $this->sinal_valor : 0.0;

        return ($liquido - $sinalPago) > 0.001;
    }

    public function scopeHoje($query)
    {
        return $query->whereDate('data_hora_inicio', today());
    }

    public function scopeAtivos($query)
    {
        return $query->whereNotIn('status', [
            AgendamentoStatus::Cancelado->value,
            AgendamentoStatus::NaoCompareceu->value,
        ]);
    }

    public function scopePorStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeConcluidos($query)
    {
        return $query->where('status', AgendamentoStatus::Concluido->value);
    }

    public function scopeCancelados($query)
    {
        return $query->where('status', AgendamentoStatus::Cancelado->value);
    }

    public function scopeNaoCompareceu($query)
    {
        return $query->where('status', AgendamentoStatus::NaoCompareceu->value);
    }

    public function scopeDoMes($query, ?int $mes = null, ?int $ano = null)
    {
        $mes ??= now()->month;
        $ano ??= now()->year;

        return $query->whereMonth('data_hora_inicio', $mes)
            ->whereYear('data_hora_inicio', $ano);
    }

    public function scopeDoSalao($query, int $salaoId)
    {
        return $query->where('salao_id', $salaoId);
    }

    public function scopeDaManicure($query, int $manicureId)
    {
        return $query->where('manicure_id', $manicureId);
    }

    public function scopeEntre($query, Carbon $inicio, Carbon $fim)
    {
        return $query->whereBetween('data_hora_inicio', [$inicio, $fim]);
    }
}
