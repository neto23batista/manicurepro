<?php

namespace App\Services;

use App\Models\Caixa;
use App\Models\CaixaMovimentacao;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CaixaService
{
    public function aberto(int $salaoId): ?Caixa
    {
        return Caixa::where('salao_id', $salaoId)
            ->whereNull('fechado_em')
            ->latest('aberto_em')
            ->first();
    }

    public function abrir(
        int $salaoId,
        float $saldoInicial = 0,
        ?int $userId = null,
        ?string $observacao = null,
    ): Caixa {
        if ($this->aberto($salaoId) !== null) {
            throw ValidationException::withMessages([
                'caixa' => 'Já existe um caixa aberto neste salão. Feche-o antes de abrir outro.',
            ]);
        }

        if ($saldoInicial < 0) {
            throw ValidationException::withMessages([
                'saldo_inicial' => 'O saldo inicial não pode ser negativo.',
            ]);
        }

        return Caixa::create([
            'salao_id'      => $salaoId,
            'aberto_por'    => $userId,
            'saldo_inicial' => round($saldoInicial, 2),
            'aberto_em'     => now(),
            'observacao'    => $observacao,
        ]);
    }

    public function movimentar(
        Caixa $caixa,
        string $tipo,
        float $valor,
        string $descricao,
        ?int $userId = null,
        ?int $pagamentoId = null,
    ): CaixaMovimentacao {
        if (! $caixa->estaAberto()) {
            throw ValidationException::withMessages([
                'caixa' => 'Não é possível movimentar um caixa fechado.',
            ]);
        }

        if (! in_array($tipo, CaixaMovimentacao::TIPOS, true)) {
            throw ValidationException::withMessages([
                'tipo' => 'Tipo de movimentação inválido.',
            ]);
        }

        if ($valor <= 0) {
            throw ValidationException::withMessages([
                'valor' => 'O valor deve ser maior que zero.',
            ]);
        }

        $descricao = trim($descricao);
        if ($descricao === '') {
            throw ValidationException::withMessages([
                'descricao' => 'Informe a descrição da movimentação.',
            ]);
        }

        return CaixaMovimentacao::create([
            'caixa_id'     => $caixa->id,
            'tipo'         => $tipo,
            'valor'        => round($valor, 2),
            'descricao'    => $descricao,
            'user_id'      => $userId,
            'pagamento_id' => $pagamentoId,
        ]);
    }

    public function saldoCalculado(Caixa $caixa): float
    {
        $movs = $caixa->relationLoaded('movimentacoes')
            ? $caixa->movimentacoes
            : $caixa->movimentacoes()->get();

        $creditos = (float) $movs->filter(fn (CaixaMovimentacao $m) => $m->isCredito())->sum('valor');
        $debitos = (float) $movs->reject(fn (CaixaMovimentacao $m) => $m->isCredito())->sum('valor');

        return round((float) $caixa->saldo_inicial + $creditos - $debitos, 2);
    }

    public function fechar(
        Caixa $caixa,
        float $saldoInformado,
        ?int $userId = null,
        ?string $observacao = null,
    ): Caixa {
        if (! $caixa->estaAberto()) {
            throw ValidationException::withMessages([
                'caixa' => 'Este caixa já está fechado.',
            ]);
        }

        if ($saldoInformado < 0) {
            throw ValidationException::withMessages([
                'saldo_final_informado' => 'O saldo informado não pode ser negativo.',
            ]);
        }

        return DB::transaction(function () use ($caixa, $saldoInformado, $userId, $observacao) {
            $caixa = Caixa::lockForUpdate()->findOrFail($caixa->id);

            if (! $caixa->estaAberto()) {
                throw ValidationException::withMessages([
                    'caixa' => 'Este caixa já está fechado.',
                ]);
            }

            $calculado = $this->saldoCalculado($caixa);
            $informado = round($saldoInformado, 2);
            $diferenca = round($informado - $calculado, 2);

            $caixa->update([
                'fechado_por'           => $userId,
                'saldo_final_informado' => $informado,
                'saldo_calculado'       => $calculado,
                'diferenca'             => $diferenca,
                'fechado_em'            => now(),
                'observacao'            => $observacao !== null
                    ? $observacao
                    : $caixa->observacao,
            ]);

            AuditLogger::log('caixa.fechado', $caixa, [
                'salao_id'              => $caixa->salao_id,
                'saldo_inicial'         => (float) $caixa->saldo_inicial,
                'saldo_calculado'       => $calculado,
                'saldo_final_informado' => $informado,
                'diferenca'             => $diferenca,
            ]);

            return $caixa->fresh(['movimentacoes', 'abertoPor', 'fechadoPor']);
        });
    }

    /**
     * Histórico de caixas do salão (mais recentes primeiro).
     *
     * @return Collection<int, Caixa>
     */
    public function historico(int $salaoId, int $limite = 30): Collection
    {
        return Caixa::with(['abertoPor', 'fechadoPor'])
            ->where('salao_id', $salaoId)
            ->orderByDesc('aberto_em')
            ->limit($limite)
            ->get();
    }
}
