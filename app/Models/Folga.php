<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folga extends Model
{
    use HasFactory;

    protected $fillable = ['salao_id', 'data', 'motivo', 'dia_todo', 'hora_inicio', 'hora_fim'];

    protected $casts = ['data' => 'date', 'dia_todo' => 'boolean'];

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }
}
