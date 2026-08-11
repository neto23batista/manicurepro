<?php

namespace App\Models;

use App\Enums\NotaFiscalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stub de NF-e — armazenamento local apenas.
 * NÃO emite na SEFAZ. Integração fiscal real fica para uma etapa futura.
 */
class NotaFiscal extends Model
{
    protected $table = 'notas_fiscais';

    protected $fillable = [
        'salao_id',
        'agendamento_id',
        'comanda_id',
        'status',
        'numero',
        'chave',
        'payload',
    ];

    protected $casts = [
        'status'  => NotaFiscalStatus::class,
        'payload' => 'array',
    ];

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

    /** @return BelongsTo<Comanda, $this> */
    public function comanda(): BelongsTo
    {
        return $this->belongsTo(Comanda::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    public function ehStub(): bool
    {
        return (bool) data_get($this->payload, 'stub', true);
    }
}
