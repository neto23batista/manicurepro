<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FidelidadePonto extends Model
{
    protected $table = 'fidelidade_pontos';

    protected $fillable = [
        'cliente_id', 'salao_id', 'agendamento_id',
        'pontos', 'tipo', 'descricao', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    /** @return BelongsTo<Agendamento, $this> */
    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }
}
