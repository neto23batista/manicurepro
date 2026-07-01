<?php

namespace App\Console\Commands;

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;
use App\Models\User;
use App\Notifications\AgendamentoLembrete;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EnviarLembretes extends Command
{
    protected $signature = 'manicure:enviar-lembretes {janela=24h : Janela do lembrete: 24h ou 2h}';
    protected $description = 'Envia lembretes de agendamentos (24h antes, diário; ou 2h antes, de hora em hora)';

    public function handle(): int
    {
        $janela = $this->argument('janela');

        if (!in_array($janela, ['24h', '2h'], true)) {
            $this->error("Janela inválida: {$janela}. Use 24h ou 2h.");
            return self::FAILURE;
        }

        $colunaControle = $janela === '2h' ? 'lembrete_2h_em' : 'lembrete_24h_em';

        $query = Agendamento::with(['salao.configuracao', 'manicure', 'servicos', 'cliente'])
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

        foreach ($agendamentos as $agendamento) {
            $config = $agendamento->salao->configuracao;
            if (!$config?->notificar_email) {
                continue;
            }

            $user = $agendamento->user
                ?? User::where('email', $agendamento->cliente?->email)->first();

            if ($user && $user->email) {
                try {
                    $user->notify(new AgendamentoLembrete($agendamento, $janela));
                    $agendamento->update([$colunaControle => now()]);
                    $enviados++;
                } catch (\Exception $e) {
                    $this->error("Erro ao notificar agendamento #{$agendamento->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Lembretes ({$janela}) enviados: {$enviados} de {$agendamentos->count()}.");

        return self::SUCCESS;
    }
}
