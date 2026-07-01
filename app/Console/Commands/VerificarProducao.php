<?php

namespace App\Console\Commands;

use App\Services\ProducaoChecker;
use Illuminate\Console\Command;

class VerificarProducao extends Command
{
    protected $signature = 'manicure:verificar-producao';
    protected $description = 'Checklist de prontidão para produção (ambiente, e-mail, fila, cron, storage, segurança)';

    public function handle(ProducaoChecker $checker): int
    {
        $this->info('Checklist de produção — Fernanda Silva Nails');
        $this->newLine();

        $checks = $checker->verificar();

        foreach ($checks as $c) {
            $icone = match ($c['nivel']) {
                ProducaoChecker::OK    => '<fg=green>✓</>',
                ProducaoChecker::AVISO => '<fg=yellow>⚠</>',
                default                => '<fg=red>✗</>',
            };

            $this->line("  {$icone} <options=bold>{$c['item']}</>: {$c['msg']}");
        }

        $this->newLine();

        // Fonte única da definição de "erro crítico": ProducaoChecker::temErroCritico.
        if ($checker->temErroCritico($checks) && app()->environment('production')) {
            $this->error('Há itens críticos pendentes para produção.');
            return self::FAILURE;
        }

        $this->info('Verificação concluída.');
        return self::SUCCESS;
    }
}
