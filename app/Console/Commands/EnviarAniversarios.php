<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Models\Cupom;
use App\Models\Salao;
use App\Notifications\AniversarioCliente;
use Illuminate\Console\Command;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Log;

class EnviarAniversarios extends Command
{
    protected $signature = 'manicure:enviar-aniversarios';

    protected $description = 'Envia felicitações de aniversário aos clientes (e opcionalmente um cupom de presente)';

    public function handle(): int
    {
        if (! config('manicure.aniversario.enabled')) {
            $this->info('Felicitação de aniversário desativada (manicure.aniversario.enabled=false).');
            Log::info('manicure:enviar-aniversarios pulado: desativado por config');

            return self::SUCCESS;
        }

        $salao = Salao::principal();
        if (! $salao) {
            $this->error('Nenhum salão configurado.');
            Log::error('manicure:enviar-aniversarios: nenhum salão ativo');

            return self::FAILURE;
        }

        $hoje = now();

        // Nascidos em 29/02: em ano não bissexto, felicita em 28/02.
        $incluiDia29Fev = $hoje->month === 2 && $hoje->day === 28 && ! $hoje->isLeapYear();

        $clientes = Cliente::where('salao_id', $salao->id)
            ->where('ativo', true)
            ->whereNotNull('data_nascimento')
            ->whereMonth('data_nascimento', $hoje->month)
            ->where(function ($q) use ($hoje, $incluiDia29Fev) {
                $q->whereDay('data_nascimento', $hoje->day);
                if ($incluiDia29Fev) {
                    $q->orWhereDay('data_nascimento', 29);
                }
            })
            // Idempotência explícita: ainda não felicitado este ano.
            ->where(function ($q) use ($hoje) {
                $q->whereNull('aniversario_enviado_em')
                    ->orWhereYear('aniversario_enviado_em', '<', $hoje->year);
            })
            ->get();

        $enviados = 0;
        $pulados = 0;

        foreach ($clientes as $cliente) {
            if (! $cliente->email && ! $cliente->telefone) {
                $pulados++;
                Log::info('Aniversário pulado: cliente sem e-mail nem telefone', [
                    'cliente_id' => $cliente->id,
                ]);

                continue;
            }

            $cupom = $this->resolverCupom($salao, $cliente);

            // Roteia apenas canais com destino — evita rota de e-mail nula
            // (cliente só com telefone) que faria o job de mail falhar no worker.
            $destino = new AnonymousNotifiable;
            if ($cliente->email) {
                $destino->route('mail', $cliente->email);
            }
            if ($cliente->telefone) {
                $destino->route('whatsapp', $cliente->telefone);
            }

            // Claim atômico antes do notify — evita duplicata se o cron sobrepor.
            $claimed = Cliente::whereKey($cliente->id)
                ->where(function ($q) use ($hoje) {
                    $q->whereNull('aniversario_enviado_em')
                        ->orWhereYear('aniversario_enviado_em', '<', $hoje->year);
                })
                ->update(['aniversario_enviado_em' => $hoje->toDateString()]);

            if ($claimed === 0) {
                Log::info('Aniversário já marcado por outra execução', [
                    'cliente_id' => $cliente->id,
                ]);

                continue;
            }

            try {
                $destino->notify(new AniversarioCliente($cliente, $salao->nome, $cupom));
                $enviados++;
            } catch (\Throwable $e) {
                // Libera o marcador para retry no próximo run.
                $cliente->forceFill(['aniversario_enviado_em' => null])->save();
                $this->error("Erro ao felicitar cliente #{$cliente->id}: ".$e->getMessage());
                Log::error('Falha ao enviar aniversário', [
                    'cliente_id' => $cliente->id,
                    'erro'       => $e->getMessage(),
                ]);
            }
        }

        $msg = "Felicitações de aniversário enviadas: {$enviados} de {$clientes->count()} (pulados: {$pulados}).";
        $this->info($msg);
        Log::info('manicure:enviar-aniversarios concluído', [
            'candidatos' => $clientes->count(),
            'enviados'   => $enviados,
            'pulados'    => $pulados,
            'data'       => $hoje->toDateString(),
        ]);

        return self::SUCCESS;
    }

    /**
     * Gera (ou recupera) o cupom-presente de aniversário do ano para o cliente.
     * Retorna null se a geração de cupom estiver desativada.
     */
    private function resolverCupom(Salao $salao, Cliente $cliente): ?Cupom
    {
        if (! config('manicure.aniversario.cupom_presente')) {
            return null;
        }

        $codigo = 'NIVER-'.$cliente->id.'-'.now()->year;
        $validadeDias = (int) config('manicure.aniversario.cupom_validade_dias', 30);

        return Cupom::firstOrCreate(
            ['salao_id' => $salao->id, 'codigo' => $codigo],
            [
                'tipo'       => config('manicure.aniversario.cupom_tipo', 'percentual'),
                'valor'      => (float) config('manicure.aniversario.cupom_valor', 15),
                'uso_maximo' => 1,
                'uso_atual'  => 0,
                'validade'   => now()->addDays($validadeDias)->toDateString(),
                'ativo'      => true,
            ],
        );
    }
}
