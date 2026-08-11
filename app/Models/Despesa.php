<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Despesa extends Model
{
    protected $table = 'despesas';

    public const CATEGORIAS = [
        'aluguel'      => 'Aluguel',
        'agua_luz'     => 'Água / Luz',
        'internet'     => 'Internet / Telefone',
        'produtos'     => 'Produtos e insumos',
        'equipamentos' => 'Equipamentos',
        'marketing'    => 'Marketing',
        'impostos'     => 'Impostos e taxas',
        'pessoal'      => 'Pessoal',
        'outros'       => 'Outros',
    ];

    protected $fillable = [
        'salao_id',
        'descricao',
        'categoria',
        'fornecedor',
        'valor',
        'vencimento',
        'pago_em',
        'recorrente',
        'user_id',
    ];

    protected $casts = [
        'valor'      => 'decimal:2',
        'vencimento' => 'date',
        'pago_em'    => 'datetime',
        'recorrente' => 'boolean',
    ];

    public function getCategoriaLabelAttribute(): string
    {
        return self::CATEGORIAS[$this->categoria] ?? ucfirst((string) $this->categoria);
    }

    public function estaPaga(): bool
    {
        return $this->pago_em !== null;
    }

    public function estaVencida(): bool
    {
        return ! $this->estaPaga() && $this->vencimento->isPast() && ! $this->vencimento->isToday();
    }

    /** @param Builder<self> $query */
    public function scopePendentes(Builder $query): Builder
    {
        return $query->whereNull('pago_em');
    }

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
