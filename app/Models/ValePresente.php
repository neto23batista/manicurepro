<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValePresente extends Model
{
    protected $table = 'vales_presente';

    public const STATUS_ATIVO = 'ativo';

    public const STATUS_USADO = 'usado';

    public const STATUS_CANCELADO = 'cancelado';

    protected $fillable = [
        'salao_id', 'codigo', 'valor', 'saldo',
        'comprador_nome', 'comprador_contato', 'beneficiario_nome',
        'mensagem', 'validade', 'status',
    ];

    protected $casts = [
        'valor'    => 'decimal:2',
        'saldo'    => 'decimal:2',
        'validade' => 'date',
    ];

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    public function getExpiradoAttribute(): bool
    {
        // copy(): endOfDay() muta o Carbon; sem isso a validade do model fica 23:59:59.
        return $this->validade !== null && $this->validade->copy()->endOfDay()->isPast();
    }

    public function estaDisponivel(): bool
    {
        return $this->status === self::STATUS_ATIVO
            && (float) $this->saldo > 0
            && ! $this->expirado;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === self::STATUS_ATIVO && $this->expirado) {
            return 'Expirado';
        }

        return match ($this->status) {
            self::STATUS_ATIVO     => 'Ativo',
            self::STATUS_USADO     => 'Usado',
            self::STATUS_CANCELADO => 'Cancelado',
            default                => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->status === self::STATUS_ATIVO && $this->expirado) {
            return 'secondary';
        }

        return match ($this->status) {
            self::STATUS_ATIVO     => 'success',
            self::STATUS_USADO     => 'info',
            self::STATUS_CANCELADO => 'danger',
            default                => 'secondary',
        };
    }

    public function scopeDisponiveis($query)
    {
        return $query->where('status', self::STATUS_ATIVO)
            ->where('saldo', '>', 0)
            ->where(function ($q) {
                $q->whereNull('validade')->orWhereDate('validade', '>=', today());
            });
    }
}
