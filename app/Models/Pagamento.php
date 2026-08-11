<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pagamento extends Model
{
    protected $fillable = [
        'comanda_id', 'agendamento_id', 'salao_id',
        'forma', 'valor', 'status', 'referencia', 'observacoes',
    ];

    protected $casts = ['valor' => 'decimal:2'];

    const FORMAS_LABELS = [
        'dinheiro'       => 'Dinheiro',
        'cartao_credito' => 'Cartão de Crédito',
        'cartao_debito'  => 'Cartão de Débito',
        'pix'            => 'PIX',
        'transferencia'  => 'Transferência',
        'voucher'        => 'Voucher',
    ];

    /** @return BelongsTo<Comanda, $this> */
    public function comanda(): BelongsTo
    {
        return $this->belongsTo(Comanda::class);
    }

    /** @return BelongsTo<Agendamento, $this> */
    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }

    public function getFormaLabelAttribute(): string
    {
        return self::FORMAS_LABELS[$this->forma];
    }
}
