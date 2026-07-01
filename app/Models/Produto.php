<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $fillable = [
        'salao_id', 'nome', 'descricao', 'codigo', 'marca',
        'preco_custo', 'preco_venda', 'estoque_atual', 'estoque_minimo',
        'unidade', 'ativo',
    ];

    protected $casts = [
        'preco_custo' => 'decimal:2',
        'preco_venda' => 'decimal:2',
        'estoque_atual' => 'decimal:3',
        'estoque_minimo' => 'decimal:3',
        'ativo' => 'boolean',
    ];

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }

    public function movimentacoes()
    {
        return $this->hasMany(EstoqueMovimentacao::class);
    }

    public function getEstoqueBaixoAttribute(): bool
    {
        return $this->estoque_atual <= $this->estoque_minimo;
    }
}
