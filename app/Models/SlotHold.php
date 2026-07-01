<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlotHold extends Model
{
    protected $fillable = [
        'manicure_id', 'data_hora_inicio', 'data_hora_fim', 'token', 'expires_at',
    ];

    protected $casts = [
        'data_hora_inicio' => 'datetime',
        'data_hora_fim' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function scopeAtivos($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function manicure()
    {
        return $this->belongsTo(Manicure::class);
    }
}
