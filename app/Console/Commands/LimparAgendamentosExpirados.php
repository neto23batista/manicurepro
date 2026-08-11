<?php

namespace App\Console\Commands;

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;
use App\Models\SlotHold;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LimparAgendamentosExpirados extends Command
{
    protected $signature = 'manicure:limpar-expirados';

    protected $description = 'Marca como não_compareceu agendamentos passados sem finalização';

    public function handle(): void
    {
        $limite = Carbon::now()->subHours(2);
        $statusNaoCompareceu = AgendamentoStatus::NaoCompareceu->value;

        $total = 0;

        Agendamento::query()
            ->where('data_hora_fim', '<', $limite)
            ->whereIn('status', [
                AgendamentoStatus::Aguardando->value,
                AgendamentoStatus::Confirmado->value,
                AgendamentoStatus::EmAndamento->value,
            ])
            ->orderBy('id')
            ->chunkById(100, function ($agendamentos) use ($statusNaoCompareceu, &$total) {
                foreach ($agendamentos as $agendamento) {
                    $agendamento->update(['status' => $statusNaoCompareceu]);
                    $total++;
                }
            });

        $this->info("Agendamentos expirados marcados: {$total}");

        $holds = SlotHold::where('expires_at', '<', Carbon::now())->delete();
        $this->info("Reservas temporárias expiradas removidas: {$holds}");
    }
}
