<?php

namespace App\Console\Commands;

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;
use App\Models\User;
use App\Notifications\AgendamentoLembrete;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class EnviarLembretes extends Command
{
    protected $signature = 'manicure:enviar-lembretes {janela=24h : Janela do lembrete: 24h ou 2h}';

    protected $description = 'Envia lembretes de agendamentos (24h antes, diário; ou 2h antes, de hora em hora)';

    public function handle(): int
    {
        $janela = $this->argument('janela');

        if (! in_array($janela, ['24h', '2h'], true)) {
            $this->error("Janela inválida: {$janela}. Use 24h ou 2h.");
            Log::warning('manicure:enviar-lembretes janela inválida', ['janela' => $janela]);

            return self::FAILURE;
        }

        $colunaControle = $janela === '2h' ? 'lembrete_2h_em' : 'lembrete_24h_em';

        $query = Agendamento::with(['salao.configuracao', 'manicure', 'servicos', 'cliente', 'user'])
            ->whereIn('status', [
                AgendamentoStatus::Aguardando->value,
                AgendamentoStatus::Confirmado->value,
            ])
            ->whereNull($colunaControle);

        if ($janela === '2h') {
            // Agendamentos que começam entre agora e ~2h15 à frente
            $query->whereBetween('data_hora_inicio', [now(), now()->copy()->addMinutes(135)]);
        } else {
            $query->whereDate('data_hora_inicio', Carbon::tomorrow());
        }

        $agendamentos = $query->get();
        $enviados = 0;
        $pulados = 0;

        foreach ($agendamentos as $agendamento) {
            $config = $agendamento->salao->configuracao;
            if (! $config?->notificar_email) {
                $pulados++;
                Log::info('Lembrete pulado: notificações de e-mail desligadas no salão', [
                    'agendamento_id' => $agendamento->id,
                    'salao_id'       => $agendamento->salao_id,
                    'janela'         => $janela,
                ]);

                continue;
            }

            $destino = $this->resolverDestino($agendamento);
            if (! $destino) {
                $pulados++;
                Log::info('Lembrete pulado: sem destinatário com e-mail', [
                    'agendamento_id' => $agendamento->id,
                    'janela'         => $janela,
                ]);

                continue;
            }

            // Claim atômico antes do notify — evita duplicata se o cron sobrepor.
            $claimed = Agendamento::whereKey($agendamento->id)
                ->whereNull($colunaControle)
                ->update([$colunaControle => now()]);

            if ($claimed === 0) {
                Log::info('Lembrete já marcado por outra execução', [
                    'agendamento_id' => $agendamento->id,
                    'janela'         => $janela,
                ]);

                continue;
            }

            try {
                $destino->notify(new AgendamentoLembrete($agendamento, $janela));
                $enviados++;
            } catch (\Throwable $e) {
                // Libera o marcador para retry no próximo run.
                Agendamento::whereKey($agendamento->id)->update([$colunaControle => null]);
                $this->error("Erro ao notificar agendamento #{$agendamento->id}: ".$e->getMessage());
                Log::error('Falha ao enviar lembrete', [
                    'agendamento_id' => $agendamento->id,
                    'janela'         => $janela,
                    'erro'           => $e->getMessage(),
                ]);
            }
        }

        $msg = "Lembretes ({$janela}) enviados: {$enviados} de {$agendamentos->count()} (pulados: {$pulados}).";
        $this->info($msg);
        Log::info('manicure:enviar-lembretes concluído', [
            'janela'     => $janela,
            'candidatos' => $agendamentos->count(),
            'enviados'   => $enviados,
            'pulados'    => $pulados,
        ]);

        return self::SUCCESS;
    }

    /**
     * User autenticado, User pelo e-mail do Cliente, ou rota anônima (guest).
     */
    private function resolverDestino(Agendamento $agendamento): mixed
    {
        $user = $agendamento->user
            ?? User::where('email', $agendamento->cliente?->email)->first();

        if ($user?->email) {
            return $user;
        }

        $email = $agendamento->cliente?->email;
        if (! $email) {
            return null;
        }

        $destino = Notification::route('mail', $email);
        $telefone = $agendamento->cliente->telefone;
        if ($telefone) {
            $destino->route('whatsapp', $telefone);
        }

        return $destino;
    }
}
