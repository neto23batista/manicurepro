<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comanda extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agendamento_id', 'salao_id', 'cliente_id', 'status',
        'valor_servicos', 'valor_produtos', 'desconto', 'total', 'observacoes',
    ];

    protected $casts = [
        'valor_servicos' => 'decimal:2',
        'valor_produtos' => 'decimal:2',
        'desconto' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class);
    }

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function itens()
    {
        return $this->hasMany(ComandaItem::class);
    }

    public function pagamentos()
    {
        return $this->hasMany(Pagamento::class);
    }

    public function getTotalPagoAttribute(): float
    {
        return (float) $this->pagamentos()
            ->where('status', \App\Enums\PagamentoStatus::Confirmado->value)
            ->sum('valor');
    }

    public function getSaldoAttribute(): float
    {
        return $this->total - $this->total_pago;
    }
}
