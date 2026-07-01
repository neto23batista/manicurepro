<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisponibilidadeManicure extends Model
{
    protected $table = 'disponibilidades_manicure';

    protected $fillable = ['manicure_id', 'dia_semana', 'hora_inicio', 'hora_fim', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function manicure()
    {
        return $this->belongsTo(Manicure::class);
    }

    public function getDiaNomeAttribute(): string
    {
        return HorarioFuncionamento::DIAS[$this->dia_semana] ?? '';
    }
}
