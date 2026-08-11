<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caixa extends Model
{
    protected $table = 'caixas';

    protected $fillable = [
        'salao_id',
        'aberto_por',
        'fechado_por',
        'saldo_inicial',
        'saldo_final_informado',
        'saldo_calculado',
        'diferenca',
        'aberto_em',
        'fechado_em',
        'observacao',
    ];

    protected $casts = [
        'saldo_inicial'         => 'decimal:2',
        'saldo_final_informado' => 'decimal:2',
        'saldo_calculado'       => 'decimal:2',
        'diferenca'             => 'decimal:2',
        'aberto_em'             => 'datetime',
        'fechado_em'            => 'datetime',
    ];

    public function estaAberto(): bool
    {
        return $this->fechado_em === null;
    }

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    /** @return BelongsTo<User, $this> */
    public function abertoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aberto_por');
    }

    /** @return BelongsTo<User, $this> */
    public function fechadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fechado_por');
    }

    /** @return HasMany<CaixaMovimentacao, $this> */
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(CaixaMovimentacao::class);
    }
}
