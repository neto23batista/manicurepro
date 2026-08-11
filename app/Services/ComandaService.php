<?php

namespace App\Services;

use App\DataTransferObjects\DadosPagamentoData;
use App\Enums\ComandaStatus;
use App\Enums\FormaPagamento;
use App\Enums\PagamentoStatus;
use App\Events\EstoqueZerado;
use App\Models\Agendamento;
use App\Models\Comanda;
use App\Models\ComandaItem;
use App\Models\Pagamento;
use App\Models\Produto;
use App\Models\ValePresente;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Comanda do atendimento: itens de serviço, venda de produtos (com baixa de
 * estoque) e pagamento ao fechar.
 */
class ComandaService
{
    public function __construct(private EstoqueService $estoque) {}

    /**
     * Vende um produto no atendimento: cria o item na comanda e dá baixa no estoque.
     */
    public function adicionarProduto(Agendamento $agendamento, Produto $produto, float $quantidade, ?int $userId = null): Comanda
    {
        return DB::transaction(function () use ($agendamento, $produto, $quantidade, $userId) {
            if ((float) $produto->estoque_atual < $quantidade) {
                throw ValidationException::withMessages([
                    'error' => "Estoque insuficiente de \"{$produto->nome}\".",
                ]);
            }

            $comanda = $this->travar($this->getOrCreateComanda($agendamento));
            $preco = (float) $produto->preco_venda;

            $comanda->itens()->create([
                'tipo'           => 'produto',
                'produto_id'     => $produto->id,
                'descricao'      => $produto->nome,
                'quantidade'     => $quantidade,
                'preco_unitario' => $preco,
                'subtotal'       => $preco * $quantidade,
            ]);

            $this->estoque->movimentar(
                $produto,
                'saida',
                $quantidade,
                $userId,
                'Venda no atendimento',
                $preco,
                'Agendamento #'.$agendamento->id,
            );

            $produto = $produto->fresh();
            if ((float) $produto->estoque_atual <= 0) {
                EstoqueZerado::dispatch($produto);
            }

            $this->recalcular($comanda);

            return $comanda->fresh();
        });
    }

    /**
     * Remove um item da comanda; se for produto, estorna o estoque.
     */
    public function removerItem(ComandaItem $item, ?int $userId = null): void
    {
        DB::transaction(function () use ($item, $userId) {
            $item->loadMissing('comanda');
            $comanda = $this->travar($item->comanda);

            if ($item->tipo === 'produto' && $item->produto_id) {
                $produto = Produto::find($item->produto_id);
                if ($produto) {
                    $this->estoque->movimentar(
                        $produto,
                        'entrada',
                        (float) $item->quantidade,
                        $userId,
                        'Estorno de venda',
                        null,
                        'Agendamento #'.$comanda->agendamento_id,
                    );
                }
            }

            $item->delete();
            $this->recalcular($comanda);
        });
    }

    /**
     * Expõe criação/recuperação da comanda (ex.: crédito de Pix online).
     */
    public function obterOuCriar(Agendamento $agendamento): Comanda
    {
        return $this->getOrCreateComanda($agendamento);
    }

    /**
     * Cria (ou recupera) a comanda do agendamento, registra os itens dos serviços,
     * fecha e registra o pagamento.
     */
    public function fecharComanda(Agendamento $agendamento, DadosPagamentoData|array $dadosPagamento): Comanda
    {
        $dto = $dadosPagamento instanceof DadosPagamentoData
            ? $dadosPagamento
            : DadosPagamentoData::fromArray($dadosPagamento);

        return DB::transaction(function () use ($agendamento, $dto) {
            $comanda = $this->travar($this->getOrCreateComanda($agendamento));

            // Registra os itens dos serviços (só uma vez).
            if (! $comanda->itens()->where('tipo', 'servico')->exists()) {
                $agendamento->loadMissing('servicos');
                foreach ($agendamento->servicos as $servico) {
                    $comanda->itens()->create([
                        'tipo'           => 'servico',
                        'servico_id'     => $servico->id,
                        'descricao'      => $servico->nome,
                        'quantidade'     => 1,
                        'preco_unitario' => $servico->pivot->preco,
                        'subtotal'       => $servico->pivot->preco,
                    ]);
                }
            }

            $this->recalcular($comanda);

            $gorjeta = max(0, (float) ($dto->gorjeta ?? 0));
            $base = max(
                0,
                (float) $comanda->valor_servicos + (float) $comanda->valor_produtos - (float) $comanda->desconto,
            );
            $comanda->update([
                'gorjeta' => $gorjeta,
                'total'   => $base + $gorjeta,
                'status'  => ComandaStatus::Fechada->value,
            ]);
            $comanda->refresh();

            // Paga apenas o saldo restante — respeita vouchers/vales e Pix online já aplicados.
            $restante = max(0, (float) $comanda->saldo);
            $valor = $dto->valor ?? $restante;

            if ($valor > 0.001) {
                Pagamento::create([
                    'comanda_id'     => $comanda->id,
                    'agendamento_id' => $agendamento->id,
                    'salao_id'       => $agendamento->salao_id,
                    'forma'          => $dto->forma->value,
                    'valor'          => $valor,
                    'status'         => PagamentoStatus::Confirmado->value,
                    'observacoes'    => $dto->observacao,
                ]);
            }

            return $comanda;
        });
    }

    /**
     * Resgata um vale-presente na comanda: debita o saldo do vale (até o valor
     * restante a pagar) e registra um pagamento na forma "voucher".
     */
    public function aplicarVale(Agendamento $agendamento, ValePresente $vale, ValePresenteService $vales): Pagamento
    {
        return DB::transaction(function () use ($agendamento, $vale, $vales) {
            $comanda = $this->travar($this->getOrCreateComanda($agendamento));
            $this->recalcular($comanda);
            $comanda->refresh();

            $restante = max(0, (float) $comanda->saldo);
            if ($restante <= 0.001) {
                throw ValidationException::withMessages([
                    'error' => 'Esta comanda já está quitada.',
                ]);
            }

            $debito = $vales->debitar($vale, $restante);

            return Pagamento::create([
                'comanda_id'     => $comanda->id,
                'agendamento_id' => $agendamento->id,
                'salao_id'       => $agendamento->salao_id,
                'forma'          => FormaPagamento::Voucher->value,
                'valor'          => $debito,
                'status'         => PagamentoStatus::Confirmado->value,
                'observacoes'    => 'Vale-presente '.$vale->codigo,
            ]);
        });
    }

    private function getOrCreateComanda(Agendamento $agendamento): Comanda
    {
        $agendamento->loadMissing('comanda');

        if ($agendamento->comanda) {
            return $agendamento->comanda;
        }

        $servicos = (float) $agendamento->valor_total;
        $desconto = (float) $agendamento->valor_desconto;

        try {
            return Comanda::create([
                'agendamento_id' => $agendamento->id,
                'salao_id'       => $agendamento->salao_id,
                'cliente_id'     => $agendamento->cliente_id,
                'valor_servicos' => $servicos,
                'valor_produtos' => 0,
                'desconto'       => $desconto,
                'total'          => max(0, $servicos - $desconto),
                'status'         => ComandaStatus::Aberta->value,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Corrida: outra requisição criou a comanda entre o check e o create.
            // O índice único em comandas.agendamento_id garante uma só — usa a existente.
            return $agendamento->comanda()->firstOrFail();
        }
    }

    /**
     * Retorna a comanda com lock pessimista (SELECT ... FOR UPDATE) — serializa
     * operações concorrentes que derivam totais/saldo e criam pagamentos.
     * (No SQLite dos testes o lock é no-op; no MySQL serializa de fato.)
     */
    private function travar(Comanda $comanda): Comanda
    {
        return Comanda::whereKey($comanda->getKey())->lockForUpdate()->firstOrFail();
    }

    /**
     * Recalcula os totais da comanda. O valor dos serviços é o do agendamento
     * (fonte autoritativa); os produtos vêm dos itens da comanda.
     */
    private function recalcular(Comanda $comanda): void
    {
        $comanda->loadMissing('agendamento');

        $servicos = (float) $comanda->agendamento->valor_total;
        $produtos = (float) $comanda->itens()->where('tipo', 'produto')->sum('subtotal');
        $desconto = (float) $comanda->agendamento->valor_desconto;

        $gorjeta = (float) ($comanda->gorjeta ?? 0);

        $comanda->update([
            'valor_servicos' => $servicos,
            'valor_produtos' => $produtos,
            'desconto'       => $desconto,
            'total'          => max(0, $servicos + $produtos - $desconto + $gorjeta),
        ]);
    }
}
