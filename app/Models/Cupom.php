<?php

namespace App\Models;

use App\Enums\AgendamentoStatus;
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
        'maximo_desconto', 'uso_maximo', 'uso_atual', 'uso_maximo_por_cliente',
        'validade', 'ativo', 'origem', 'primeira_compra', 'cliente_id',
        'servico_id', 'anti_stacking_fidelidade',
    ];

    protected $casts = [
        'valor'                    => 'decimal:2',
        'minimo_pedido'            => 'decimal:2',
        'maximo_desconto'          => 'decimal:2',
        'validade'                 => 'date',
        'ativo'                    => 'boolean',
        'primeira_compra'          => 'boolean',
        'anti_stacking_fidelidade' => 'boolean',
        'uso_maximo_por_cliente'   => 'integer',
    ];

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

    /** @return BelongsTo<Servico, $this> */
    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
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
        if ($valor < (float) $this->minimo_pedido) {
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
     * Regras avançadas (primeira compra, cliente/serviço, uso/cliente, anti-stacking).
     *
     * @param  array<int>  $servicoIds
     *
     * @throws ValidationException
     */
    public function assertElegivel(?int $clienteId, array $servicoIds = [], float $valorPedido = 0): void
    {
        if ($valorPedido > 0 && $valorPedido < (float) $this->minimo_pedido) {
            throw ValidationException::withMessages([
                'error' => 'Pedido abaixo do valor mínimo do cupom.',
            ]);
        }

        if ($this->cliente_id !== null) {
            if ($clienteId === null || (int) $clienteId !== (int) $this->cliente_id) {
                throw ValidationException::withMessages([
                    'error' => 'Este cupom é exclusivo de outro cliente.',
                ]);
            }
        }

        if ($this->servico_id !== null) {
            if (! in_array((int) $this->servico_id, array_map('intval', $servicoIds), true)) {
                throw ValidationException::withMessages([
                    'error' => 'Cupom válido apenas para o serviço indicado.',
                ]);
            }
        }

        if ($this->primeira_compra) {
            if ($clienteId === null) {
                throw ValidationException::withMessages([
                    'error' => 'Cupom de primeira compra exige cliente identificado.',
                ]);
            }

            $jaComprou = Agendamento::query()
                ->where('cliente_id', $clienteId)
                ->where('status', AgendamentoStatus::Concluido->value)
                ->exists();

            if ($jaComprou) {
                throw ValidationException::withMessages([
                    'error' => 'Cupom válido apenas na primeira compra.',
                ]);
            }
        }

        if ($this->uso_maximo_por_cliente !== null) {
            if ($clienteId === null) {
                throw ValidationException::withMessages([
                    'error' => 'Este cupom exige cliente identificado.',
                ]);
            }

            $usos = Agendamento::query()
                ->where('cliente_id', $clienteId)
                ->where('cupom_id', $this->id)
                ->where('status', '!=', AgendamentoStatus::Cancelado->value)
                ->count();

            if ($usos >= (int) $this->uso_maximo_por_cliente) {
                throw ValidationException::withMessages([
                    'error' => 'Você já atingiu o limite de uso deste cupom.',
                ]);
            }
        }

    }

    /**
     * Cupom promocional anti-stacking: não acumula com crédito de pontos no atendimento.
     * Cupons gerados pelo resgate (origem=fidelidade) são o próprio benefício.
     */
    public function bloqueiaCreditoFidelidade(): bool
    {
        return $this->anti_stacking_fidelidade && $this->origem !== 'fidelidade';
    }

    /**
     * Aplica o cupom de forma race-safe (lock pessimista) e consome 1 uso.
     * Deve ser chamado dentro de DB::transaction.
     *
     * @param  array<int>  $servicoIds
     *
     * @throws ValidationException quando expirado, esgotado, inativo ou de outro salão
     */
    public function aplicarPara(
        int $salaoId,
        float $valorPedido,
        ?int $clienteId = null,
        array $servicoIds = []
    ): float {
        $travado = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

        if (! $travado->isValido($salaoId)) {
            throw ValidationException::withMessages([
                'error' => 'Cupom inválido, expirado, esgotado ou de outro salão.',
            ]);
        }

        $travado->assertElegivel($clienteId, $servicoIds, $valorPedido);

        $desconto = $travado->calcularDesconto($valorPedido);
        if ($desconto <= 0 && (float) $travado->minimo_pedido > 0 && $valorPedido < (float) $travado->minimo_pedido) {
            throw ValidationException::withMessages([
                'error' => 'Pedido abaixo do valor mínimo do cupom.',
            ]);
        }

        $travado->increment('uso_atual');

        // Sincroniza a instância do chamador com o estado persistido.
        $this->setRawAttributes($travado->fresh()->getAttributes(), true);

        return $desconto;
    }
}
