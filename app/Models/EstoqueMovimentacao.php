<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstoqueMovimentacao extends Model
{
    protected $table = 'estoque_movimentacoes';

    protected $fillable = [
        'produto_id', 'salao_id', 'user_id', 'tipo',
        'quantidade', 'preco_unitario', 'motivo', 'referencia',
    ];

    protected $casts = [
        'quantidade' => 'decimal:3',
        'preco_unitario' => 'decimal:2',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
