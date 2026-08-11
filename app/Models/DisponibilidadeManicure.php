<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisponibilidadeManicure extends Model
{
    protected $table = 'disponibilidades_manicure';

    protected $fillable = ['manicure_id', 'dia_semana', 'hora_inicio', 'hora_fim', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    /** @return BelongsTo<Manicure, $this> */
    public function manicure(): BelongsTo
    {
        return $this->belongsTo(Manicure::class);
    }

    public function getDiaNomeAttribute(): string
    {
        return HorarioFuncionamento::DIAS[$this->dia_semana] ?? '';
    }
}
