<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicoVariacao extends Model
{
    use HasFactory;

    protected $table = 'servico_variacoes';

    protected $fillable = [
        'servico_id', 'nome', 'preco', 'duracao', 'ordem', 'ativo',
    ];

    protected $casts = [
        'preco'   => 'decimal:2',
        'duracao' => 'integer',
        'ordem'   => 'integer',
        'ativo'   => 'boolean',
    ];

    /** @return BelongsTo<Servico, $this> */
    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function getDuracaoFormatadaAttribute(): string
    {
        $h = intdiv($this->duracao, 60);
        $m = $this->duracao % 60;
        if ($h > 0) {
            return "{$h}h".($m > 0 ? " {$m}min" : '');
        }

        return "{$m}min";
    }

    public function getPrecoFormatadoAttribute(): string
    {
        return 'R$ '.number_format((float) $this->preco, 2, ',', '.');
    }
}
