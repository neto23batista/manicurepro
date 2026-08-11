<?php

namespace App\Models;

use App\Services\AgendaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisponibilidadeManicure extends Model
{
    protected $table = 'disponibilidades_manicure';

    protected $fillable = [
        'manicure_id', 'dia_semana', 'hora_inicio', 'hora_fim',
        'pausa_inicio', 'pausa_fim', 'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];

    protected static function booted(): void
    {
        $invalidate = function (DisponibilidadeManicure $disp) {
            app(AgendaService::class)->invalidarCacheSlotsDisponibilidade($disp);
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /** @return BelongsTo<Manicure, $this> */
    public function manicure(): BelongsTo
    {
        return $this->belongsTo(Manicure::class);
    }

    public function getDiaNomeAttribute(): string
    {
        return HorarioFuncionamento::DIAS[$this->dia_semana] ?? '';
    }

    public function temPausa(): bool
    {
        return filled($this->pausa_inicio) && filled($this->pausa_fim);
    }
}
