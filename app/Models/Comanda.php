<?php

namespace App\Models;

use App\Enums\PagamentoStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comanda extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agendamento_id', 'salao_id', 'cliente_id', 'status',
        'valor_servicos', 'valor_produtos', 'desconto', 'gorjeta', 'total', 'observacoes',
    ];

    protected $casts = [
        'valor_servicos' => 'decimal:2',
        'valor_produtos' => 'decimal:2',
        'desconto'       => 'decimal:2',
        'gorjeta'        => 'decimal:2',
        'total'          => 'decimal:2',
    ];

    /** @return BelongsTo<Agendamento, $this> */
    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return HasMany<ComandaItem, $this> */
    public function itens(): HasMany
    {
        return $this->hasMany(ComandaItem::class);
    }

    /** @return HasMany<Pagamento, $this> */
    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }

    /** @return HasMany<NotaFiscal, $this> */
    public function notasFiscais(): HasMany
    {
        return $this->hasMany(NotaFiscal::class);
    }

    public function getTotalPagoAttribute(): float
    {
        return (float) $this->pagamentos()
            ->where('status', PagamentoStatus::Confirmado->value)
            ->sum('valor');
    }

    public function getSaldoAttribute(): float
    {
        return $this->total - $this->total_pago;
    }
}
