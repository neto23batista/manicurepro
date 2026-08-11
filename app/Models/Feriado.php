<?php

namespace App\Models;

use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feriado extends Model
{
    use HasFactory;

    protected $fillable = [
        'salao_id', 'nome', 'mes', 'dia',
        'dia_todo', 'hora_inicio', 'hora_fim', 'ativo',
    ];

    protected $casts = [
        'mes'      => 'integer',
        'dia'      => 'integer',
        'dia_todo' => 'boolean',
        'ativo'    => 'boolean',
    ];

    protected static function booted(): void
    {
        $invalidate = function (Feriado $feriado) {
            app(AgendaService::class)->invalidarCacheSlotsFeriado($feriado);
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    public function ocorreEm(Carbon|string $data): bool
    {
        $data = Carbon::parse($data);

        return (int) $data->month === $this->mes
            && (int) $data->day === $this->dia;
    }

    public function getLabelCurtoAttribute(): string
    {
        return sprintf('%02d/%02d — %s', $this->dia, $this->mes, $this->nome);
    }
}
