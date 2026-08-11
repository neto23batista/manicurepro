<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avaliacao extends Model
{
    protected $table = 'avaliacoes';

    protected $fillable = [
        'agendamento_id', 'cliente_id', 'manicure_id', 'salao_id',
        'nota', 'comentario', 'resposta', 'respondido_em', 'publicar',
    ];

    protected $casts = [
        'respondido_em' => 'datetime',
        'publicar'      => 'boolean',
    ];

    /** @return BelongsTo<Agendamento, $this> */
    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return BelongsTo<Manicure, $this> */
    public function manicure(): BelongsTo
    {
        return $this->belongsTo(Manicure::class);
    }

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    public function getEstrelasAttribute(): string
    {
        return str_repeat('★', $this->nota).str_repeat('☆', 5 - $this->nota);
    }
}
