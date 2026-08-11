<?php

namespace App\Console\Commands;

use App\Services\FidelidadeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpirarPontosFidelidade extends Command
{
    protected $signature = 'manicure:expirar-pontos-fidelidade';

    protected $description = 'Debita pontos de fidelidade com expires_at vencido';

    public function handle(FidelidadeService $fidelidade): int
    {
        if (! config('manicure.fidelidade.expiracao_dias')) {
            $this->info('Expiração de pontos desativada (FIDELIDADE_EXPIRACAO_DIAS vazio).');

            return self::SUCCESS;
        }

        $n = $fidelidade->expirarPontosVencidos();
        $this->info("Pontos expirados processados: {$n}");
        Log::info('manicure:expirar-pontos-fidelidade concluído', ['processados' => $n]);

        return self::SUCCESS;
    }
}
