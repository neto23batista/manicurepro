<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    protected $fillable = [
        'salao_id', 'fornecedor_id', 'nome', 'descricao', 'codigo', 'marca',
        'preco_custo', 'preco_venda', 'estoque_atual', 'estoque_minimo',
        'unidade', 'ativo',
    ];

    protected $casts = [
        'preco_custo'    => 'decimal:2',
        'preco_venda'    => 'decimal:2',
        'estoque_atual'  => 'decimal:3',
        'estoque_minimo' => 'decimal:3',
        'ativo'          => 'boolean',
    ];

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    /** @return BelongsTo<Fornecedor, $this> */
    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    /** @return HasMany<EstoqueMovimentacao, $this> */
    public function movimentacoes(): HasMany
    {
        return $this->hasMany(EstoqueMovimentacao::class);
    }

    public function scopeEstoqueBaixo($query)
    {
        return $query->whereColumn('estoque_atual', '<=', 'estoque_minimo');
    }

    public function getEstoqueBaixoAttribute(): bool
    {
        return $this->estoque_atual <= $this->estoque_minimo;
    }
}
