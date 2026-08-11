<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListaEspera extends Model
{
    protected $table = 'listas_espera';

    protected $fillable = [
        'salao_id', 'manicure_id', 'cliente_id', 'user_id',
        'data_preferida', 'periodo', 'status', 'notificado_em',
    ];

    protected $casts = [
        'data_preferida' => 'date',
        'notificado_em'  => 'datetime',
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

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAguardando($query)
    {
        return $query->where('status', 'aguardando');
    }

    public function getPeriodoLabelAttribute(): string
    {
        return match ($this->periodo) {
            'manha' => 'Manhã',
            'tarde' => 'Tarde',
            'noite' => 'Noite',
            default => 'Qualquer horário',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'notificado' => 'Vaga avisada',
            'atendido'   => 'Atendido',
            'cancelado'  => 'Cancelado',
            default      => 'Aguardando vaga',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'notificado' => 'success',
            'atendido'   => 'primary',
            'cancelado'  => 'secondary',
            default      => 'warning',
        };
    }

    public function getStatusHintAttribute(): string
    {
        return match ($this->status) {
            'notificado' => 'Abrimos uma vaga que combina com sua preferência. Agende agora para garantir.',
            'atendido'   => 'Você já usou esta inscrição da lista de espera.',
            'cancelado'  => 'Esta inscrição foi cancelada.',
            default      => 'Estamos de olho na agenda. Avisaremos assim que abrir uma vaga.',
        };
    }

    public function getEstaAtivaAttribute(): bool
    {
        return in_array($this->status, ['aguardando', 'notificado'], true);
    }
}
