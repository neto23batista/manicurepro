<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListaEspera extends Model
{
    protected $table = 'listas_espera';

    protected $fillable = [
        'salao_id', 'manicure_id', 'cliente_id', 'user_id',
        'data_preferida', 'periodo', 'status', 'notificado_em',
    ];

    protected $casts = [
        'data_preferida' => 'date',
        'notificado_em' => 'datetime',
    ];

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }

    public function manicure()
    {
        return $this->belongsTo(Manicure::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user()
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
}
