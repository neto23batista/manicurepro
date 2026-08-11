<?php

namespace App\Models;

use App\Services\AgendaService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Folga extends Model
{
    use HasFactory;

    protected $fillable = ['salao_id', 'data', 'motivo', 'dia_todo', 'hora_inicio', 'hora_fim'];

    protected $casts = ['data' => 'date', 'dia_todo' => 'boolean'];

    protected static function booted(): void
    {
        $invalidate = function (Folga $folga) {
            app(AgendaService::class)->invalidarCacheSlotsSalao((int) $folga->salao_id, $folga->data);
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }
}
