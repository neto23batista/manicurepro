<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComandaItem extends Model
{
    protected $table = 'comanda_itens';

    protected $fillable = [
        'comanda_id', 'tipo', 'servico_id', 'produto_id',
        'descricao', 'quantidade', 'preco_unitario', 'subtotal',
    ];

    protected $casts = [
        'quantidade' => 'decimal:3',
        'preco_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function comanda()
    {
        return $this->belongsTo(Comanda::class);
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }
}
