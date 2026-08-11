<?php

namespace App\Models;

use App\Services\AgendaService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolgaManicure extends Model
{
    use HasFactory;

    protected $table = 'folgas_manicure';

    protected $fillable = ['manicure_id', 'data', 'motivo', 'dia_todo', 'hora_inicio', 'hora_fim'];

    protected $casts = ['data' => 'date', 'dia_todo' => 'boolean'];

    protected static function booted(): void
    {
        $invalidate = function (FolgaManicure $folga) {
            app(AgendaService::class)->invalidarCacheSlots((int) $folga->manicure_id, $folga->data);
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /** @return BelongsTo<Manicure, $this> */
    public function manicure(): BelongsTo
    {
        return $this->belongsTo(Manicure::class);
    }
}
