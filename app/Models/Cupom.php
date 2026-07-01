<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cupom extends Model
{
    use HasFactory;

    protected $table = 'cupons';

    protected $fillable = [
        'salao_id', 'codigo', 'tipo', 'valor', 'minimo_pedido',
        'maximo_desconto', 'uso_maximo', 'uso_atual', 'validade', 'ativo',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'minimo_pedido' => 'decimal:2',
        'maximo_desconto' => 'decimal:2',
        'validade' => 'date',
        'ativo' => 'boolean',
    ];

    public function salao()
    {
        return $this->belongsTo(Salao::class);
    }

    public function isValido(): bool
    {
        if (!$this->ativo) return false;
        if ($this->validade && $this->validade->isPast()) return false;
        if ($this->uso_maximo && $this->uso_atual >= $this->uso_maximo) return false;
        return true;
    }

    public function calcularDesconto(float $valor): float
    {
        if ($valor < $this->minimo_pedido) return 0;
        if ($this->tipo === 'percentual') {
            $desconto = $valor * ($this->valor / 100);
            if ($this->maximo_desconto) {
                $desconto = min($desconto, $this->maximo_desconto);
            }
            return round($desconto, 2);
        }
        return min($this->valor, $valor);
    }
}
