<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Cupom extends Model
{
    use HasFactory;

    protected $table = 'cupons';

    protected $fillable = [
        'salao_id', 'codigo', 'tipo', 'valor', 'minimo_pedido',
        'maximo_desconto', 'uso_maximo', 'uso_atual', 'validade', 'ativo',
    ];

    protected $casts = [
        'valor'           => 'decimal:2',
        'minimo_pedido'   => 'decimal:2',
        'maximo_desconto' => 'decimal:2',
        'validade'        => 'date',
        'ativo'           => 'boolean',
    ];

    /** @return BelongsTo<Salao, $this> */
    public function salao(): BelongsTo
    {
        return $this->belongsTo(Salao::class);
    }

    public function isValido(?int $salaoId = null): bool
    {
        if (! $this->ativo) {
            return false;
        }

        if ($salaoId !== null && (int) $this->salao_id !== $salaoId) {
            return false;
        }

        // copy(): endOfDay() muta o Carbon; validade do dia ainda conta como válida.
        if ($this->validade && $this->validade->copy()->endOfDay()->isPast()) {
            return false;
        }

        if ($this->uso_maximo !== null && $this->uso_atual >= $this->uso_maximo) {
            return false;
        }

        return true;
    }

    public function calcularDesconto(float $valor): float
    {
        if ($valor < $this->minimo_pedido) {
            return 0;
        }
        if ($this->tipo === 'percentual') {
            $desconto = $valor * ($this->valor / 100);
            if ($this->maximo_desconto) {
                $desconto = min($desconto, $this->maximo_desconto);
            }

            return round($desconto, 2);
        }

        return min((float) $this->valor, $valor);
    }

    /**
     * Aplica o cupom de forma race-safe (lock pessimista) e consome 1 uso.
     * Deve ser chamado dentro de DB::transaction.
     *
     * @throws ValidationException quando expirado, esgotado, inativo ou de outro salão
     */
    public function aplicarPara(int $salaoId, float $valorPedido): float
    {
        $travado = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

        if (! $travado->isValido($salaoId)) {
            throw ValidationException::withMessages([
                'error' => 'Cupom inválido, expirado, esgotado ou de outro salão.',
            ]);
        }

        $desconto = $travado->calcularDesconto($valorPedido);
        $travado->increment('uso_atual');

        // Sincroniza a instância do chamador com o estado persistido.
        $this->setRawAttributes($travado->fresh()->getAttributes(), true);

        return $desconto;
    }
}
