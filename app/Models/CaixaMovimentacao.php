<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaixaMovimentacao extends Model
{
    protected $table = 'caixa_movimentacoes';

    public const TIPOS = ['entrada', 'saida', 'sangria', 'suprimento'];

    public const TIPOS_LABELS = [
        'entrada'    => 'Entrada',
        'saida'      => 'Saída',
        'sangria'    => 'Sangria',
        'suprimento' => 'Suprimento',
    ];

    /** Tipos que somam no saldo do caixa; os demais subtraem. */
    public const TIPOS_CREDITO = ['entrada', 'suprimento'];

    protected $fillable = [
        'caixa_id',
        'tipo',
        'valor',
        'descricao',
        'user_id',
        'pagamento_id',
    ];

    protected $casts = ['valor' => 'decimal:2'];

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS_LABELS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    public function isCredito(): bool
    {
        return in_array($this->tipo, self::TIPOS_CREDITO, true);
    }

    /** @return BelongsTo<Caixa, $this> */
    public function caixa(): BelongsTo
    {
        return $this->belongsTo(Caixa::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Pagamento, $this> */
    public function pagamento(): BelongsTo
    {
        return $this->belongsTo(Pagamento::class);
    }
}
