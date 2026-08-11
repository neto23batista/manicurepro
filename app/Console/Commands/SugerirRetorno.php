<?php

namespace App\Console\Commands;

use App\Models\Salao;
use App\Services\MarketingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SugerirRetorno extends Command
{
    protected $signature = 'manicure:sugerir-retorno';

    protected $description = 'Sugere retorno aos clientes pela cadência configurada';

    public function handle(MarketingService $marketing): int
    {
        if (! $marketing->habilitado()) {
            $this->info('Marketing desativado (manicure.marketing.enabled=false).');
            Log::info('manicure:sugerir-retorno pulado: desativado por config');

            return self::SUCCESS;
        }

        $salao = Salao::principal();
        if (! $salao) {
            $this->error('Nenhum salão configurado.');
            Log::error('manicure:sugerir-retorno: nenhum salão ativo');

            return self::FAILURE;
        }

        $resultado = $marketing->sugerirRetornos($salao);

        $msg = "Sugestões de retorno enviadas: {$resultado['enviados']} de {$resultado['candidatos']} (pulados: {$resultado['pulados']}).";
        $this->info($msg);
        Log::info('manicure:sugerir-retorno concluído', $resultado);

        return self::SUCCESS;
    }
}
