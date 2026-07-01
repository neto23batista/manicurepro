<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GaleriaFoto extends Model
{
    protected $table = 'galeria_fotos';

    protected $fillable = [
        'salao_id', 'manicure_id', 'caminho', 'titulo',
        'descricao', 'ordem', 'publicar', 'destaque',
    ];

    protected $casts = [
        'publicar' => 'boolean',
        'destaque' => 'boolean',
        'ordem'    => 'integer',
    ];

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }

    public function manicure()
    {
        return $this->belongsTo(Manicure::class);
    }

    public function getFotoUrlAttribute(): string
    {
        if ($this->caminho && file_exists(public_path('storage/' . $this->caminho))) {
            return asset('storage/' . $this->caminho);
        }
        return asset('images/logo-default.png');
    }

    public function scopePublicadas($query)
    {
        return $query->where('publicar', true);
    }
}
