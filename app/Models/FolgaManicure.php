<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FolgaManicure extends Model
{
    use HasFactory;

    protected $table = 'folgas_manicure';

    protected $fillable = ['manicure_id', 'data', 'motivo', 'dia_todo', 'hora_inicio', 'hora_fim'];

    protected $casts = ['data' => 'date', 'dia_todo' => 'boolean'];

    public function manicure()
    {
        return $this->belongsTo(Manicure::class);
    }
}
