<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupCommand extends Command
{
    protected $signature = 'manicure:backup
                            {--keep=14 : Quantidade de backups a manter (mais antigos são removidos)}';

    protected $description = 'Gera backup do banco e de storage/app/public em storage/app/backups';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $stamp = now()->format('Y-m-d_His');
        $workDir = $dir.DIRECTORY_SEPARATOR.'tmp_'.$stamp;
        File::ensureDirectoryExists($workDir);

        try {
            $this->backupDatabase($workDir);
            $this->backupPublicStorage($workDir);

            $zipPath = $dir.DIRECTORY_SEPARATOR."manicurepro_{$stamp}.zip";
            if (! $this->zipDirectory($workDir, $zipPath)) {
                $this->error('Falha ao criar o arquivo ZIP do backup.');

                return self::FAILURE;
            }

            $this->info("Backup criado: {$zipPath}");
            $this->pruneOldBackups($dir, max(1, (int) $this->option('keep')));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            report($e);
            $this->error('Backup falhou. Veja o log para detalhes.');

            return self::FAILURE;
        } finally {
            if (File::isDirectory($workDir)) {
                File::deleteDirectory($workDir);
            }
        }
    }

    private function backupDatabase(string $workDir): void
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}");

        if (($connection['driver'] ?? '') === 'sqlite') {
            $dbPath = $connection['database'] ?? database_path('database.sqlite');
            if (! is_string($dbPath) || $dbPath === ':memory:' || ! File::exists($dbPath)) {
                throw new \RuntimeException(
                    'Backup SQLite exige arquivo em disco (DB_DATABASE apontando para um .sqlite). :memory: não é suportado.',
                );
            }
            File::copy($dbPath, $workDir.DIRECTORY_SEPARATOR.'database.sqlite');
            $this->line('  ✓ Banco SQLite copiado');

            return;
        }

        if (($connection['driver'] ?? '') === 'mysql') {
            $out = $workDir.DIRECTORY_SEPARATOR.'database.sql';
            $host = (string) ($connection['host'] ?? '127.0.0.1');
            $port = (string) ($connection['port'] ?? '3306');
            $user = (string) ($connection['username'] ?? '');
            $pass = (string) ($connection['password'] ?? '');
            $name = (string) ($connection['database'] ?? '');

            $cmd = ['mysqldump', '--host='.$host, '--port='.$port, '--user='.$user];
            if ($pass !== '') {
                $cmd[] = '--password='.$pass;
            }
            $cmd[] = $name;

            $process = new Process($cmd);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException(
                    'mysqldump falhou. Confira se o binário está no PATH. '.$process->getErrorOutput(),
                );
            }

            File::put($out, $process->getOutput());
            if (File::size($out) === 0) {
                throw new \RuntimeException('mysqldump gerou arquivo vazio.');
            }

            $this->line('  ✓ Dump MySQL gerado');

            return;
        }

        throw new \RuntimeException("Driver de banco não suportado pelo backup: {$driver}");
    }

    private function backupPublicStorage(string $workDir): void
    {
        $public = storage_path('app/public');
        $dest = $workDir.DIRECTORY_SEPARATOR.'storage_public';
        File::ensureDirectoryExists($dest);

        if (File::isDirectory($public)) {
            File::copyDirectory($public, $dest);
        }

        $this->line('  ✓ storage/app/public copiado');
    }

    private function zipDirectory(string $source, string $zipPath): bool
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Extensão PHP zip não está disponível.');
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $source = realpath($source) ?: $source;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($files as $file) {
            $path = $file->getRealPath();
            $relative = substr($path, strlen($source) + 1);
            $relative = str_replace('\\', '/', $relative);

            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
            } else {
                $zip->addFile($path, $relative);
            }
        }

        return $zip->close();
    }

    private function pruneOldBackups(string $dir, int $keep): void
    {
        $zips = collect(File::files($dir))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.zip'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        foreach ($zips->slice($keep) as $old) {
            File::delete($old->getPathname());
            $this->line('  · removido backup antigo: '.$old->getFilename());
        }
    }
}
