<?php

namespace App\Console\Commands;

use App\Models\Salao;
use App\Services\MarketingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReativarInativos extends Command
{
    protected $signature = 'manicure:reativar-inativos';

    protected $description = 'Envia campanha de reativação para clientes inativos (com cooldown)';

    public function handle(MarketingService $marketing): int
    {
        if (! $marketing->habilitado()) {
            $this->info('Marketing desativado (manicure.marketing.enabled=false).');
            Log::info('manicure:reativar-inativos pulado: desativado por config');

            return self::SUCCESS;
        }

        $salao = Salao::principal();
        if (! $salao) {
            $this->error('Nenhum salão configurado.');
            Log::error('manicure:reativar-inativos: nenhum salão ativo');

            return self::FAILURE;
        }

        $resultado = $marketing->reativarInativos($salao);

        $msg = "Reativações enviadas: {$resultado['enviados']} de {$resultado['candidatos']} (pulados: {$resultado['pulados']}).";
        $this->info($msg);
        Log::info('manicure:reativar-inativos concluído', $resultado);

        return self::SUCCESS;
    }
}
