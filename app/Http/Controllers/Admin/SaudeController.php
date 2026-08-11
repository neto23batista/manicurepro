<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SaudeController extends Controller
{
    public function index(): View
    {
        return view('admin.saude', [
            'checks' => [
                'database'    => $this->checkDatabase(),
                'cache'       => $this->checkCache(),
                'queue'       => $this->checkQueue(),
                'failed_jobs' => $this->checkFailedJobs(),
            ],
        ]);
    }

    /**
     * @return array{ok: bool, label: string, detail: string}
     */
    private function checkDatabase(): array
    {
        try {
            $driver = config('database.default');
            DB::connection()->select('select 1 as ok');

            return [
                'ok'     => true,
                'label'  => 'Banco de dados',
                'detail' => "Conexão OK (driver: {$driver})",
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'ok'     => false,
                'label'  => 'Banco de dados',
                'detail' => 'Falha ao conectar. Verifique DB_* no .env.',
            ];
        }
    }

    /**
     * @return array{ok: bool, label: string, detail: string}
     */
    private function checkCache(): array
    {
        try {
            $store = config('cache.default');
            $key = 'manicure_health_'.uniqid('', true);
            Cache::put($key, 'ok', 10);
            $ok = Cache::pull($key) === 'ok';

            return [
                'ok'     => $ok,
                'label'  => 'Cache',
                'detail' => $ok
                    ? "Leitura/escrita OK (store: {$store})"
                    : "Falha na leitura/escrita (store: {$store})",
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'ok'     => false,
                'label'  => 'Cache',
                'detail' => 'Falha ao acessar o cache.',
            ];
        }
    }

    /**
     * @return array{ok: bool, label: string, detail: string}
     */
    private function checkQueue(): array
    {
        $connection = (string) config('queue.default');

        if ($connection === 'sync') {
            return [
                'ok'     => true,
                'label'  => 'Fila',
                'detail' => 'Driver sync (jobs rodam inline; worker não é necessário).',
            ];
        }

        try {
            $pending = Schema::hasTable('jobs')
                ? (int) DB::table('jobs')->count()
                : 0;

            return [
                'ok'     => true,
                'label'  => 'Fila',
                'detail' => "Conexão: {$connection}. Jobs pendentes: {$pending}.",
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'ok'     => false,
                'label'  => 'Fila',
                'detail' => "Não foi possível inspecionar a fila ({$connection}).",
            ];
        }
    }

    /**
     * @return array{ok: bool, label: string, detail: string, items?: list<array{id: int|string, queue: string, failed_at: string|null}>}
     */
    private function checkFailedJobs(): array
    {
        try {
            if (! Schema::hasTable('failed_jobs')) {
                return [
                    'ok'     => true,
                    'label'  => 'Failed jobs',
                    'detail' => 'Tabela failed_jobs ainda não existe.',
                    'items'  => [],
                ];
            }

            $count = (int) DB::table('failed_jobs')->count();
            $items = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(10)
                ->get(['id', 'queue', 'failed_at'])
                ->map(fn ($row) => [
                    'id'        => $row->id,
                    'queue'     => (string) $row->queue,
                    'failed_at' => $row->failed_at ? (string) $row->failed_at : null,
                ])
                ->all();

            return [
                'ok'     => $count === 0,
                'label'  => 'Failed jobs',
                'detail' => $count === 0
                    ? 'Nenhum job falhou.'
                    : "{$count} job(s) falho(s). Use `php artisan queue:failed` / `queue:retry`.",
                'items'  => $items,
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'ok'     => false,
                'label'  => 'Failed jobs',
                'detail' => 'Não foi possível ler failed_jobs.',
                'items'  => [],
            ];
        }
    }
}
