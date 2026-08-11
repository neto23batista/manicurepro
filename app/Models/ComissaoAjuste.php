<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComissaoAjuste extends Model
{
    protected $table = 'comissao_ajustes';

    protected $fillable = [
        'salao_id',
        'manicure_id',
        'periodo_inicio',
        'periodo_fim',
        'valor',
        'motivo',
        'user_id',
    ];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fim'    => 'date',
        'valor'          => 'decimal:2',
    ];

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
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
}
