<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComandaItem extends Model
{
    protected $table = 'comanda_itens';

    protected $fillable = [
        'comanda_id', 'tipo', 'servico_id', 'produto_id',
        'descricao', 'quantidade', 'preco_unitario', 'subtotal',
    ];

    protected $casts = [
        'quantidade'     => 'decimal:3',
        'preco_unitario' => 'decimal:2',
        'subtotal'       => 'decimal:2',
    ];

    /** @return BelongsTo<Comanda, $this> */
    public function comanda(): BelongsTo
    {
        return $this->belongsTo(Comanda::class);
    }

    /** @return BelongsTo<Servico, $this> */
    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    /** @return BelongsTo<Produto, $this> */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
