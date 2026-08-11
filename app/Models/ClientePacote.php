<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientePacote extends Model
{
    protected $table = 'cliente_pacotes';

    protected $fillable = [
        'cliente_id', 'pacote_id', 'sessoes_restantes', 'expires_at',
    ];

    protected $casts = [
        'sessoes_restantes' => 'integer',
        'expires_at'        => 'datetime',
    ];

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return BelongsTo<Pacote, $this> */
    public function pacote(): BelongsTo
    {
        return $this->belongsTo(Pacote::class);
    }

    public function estaDisponivel(): bool
    {
        if ($this->sessoes_restantes <= 0) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function scopeDisponiveis($query)
    {
        return $query->where('sessoes_restantes', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }
}
