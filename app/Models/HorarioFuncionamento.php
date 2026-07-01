<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioFuncionamento extends Model
{
    protected $table = 'horarios_funcionamento';

    protected $fillable = ['salao_id', 'dia_semana', 'hora_abertura', 'hora_fechamento', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    const DIAS = [
        0 => 'Domingo', 1 => 'Segunda-feira', 2 => 'Terça-feira',
        3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado',
    ];

    const DIAS_ABREV = [
        0 => 'Dom', 1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb',
    ];

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }

    public function getDiaNomeAttribute(): string
    {
        return self::DIAS[$this->dia_semana] ?? '';
    }
}
