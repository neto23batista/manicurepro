<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Isolado: troca DB_DATABASE para arquivo SQLite e não deve compartilhar
 * RefreshDatabase/:memory: com outros testes do mesmo arquivo.
 */
test('manicure:backup gera zip com sqlite e storage_public', function () {
    $dbPath = storage_path('framework/testing/backup-source.sqlite');
    File::ensureDirectoryExists(dirname($dbPath));
    if (File::exists($dbPath)) {
        File::delete($dbPath);
    }

    $pdo = new PDO('sqlite:'.$dbPath);
    $pdo->exec('CREATE TABLE _ok (id INTEGER PRIMARY KEY)');
    $pdo = null;

    config([
        'database.default'                     => 'sqlite',
        'database.connections.sqlite.database' => $dbPath,
    ]);
    DB::purge('sqlite');

    File::ensureDirectoryExists(storage_path('app/public'));
    File::put(storage_path('app/public/health-check.txt'), 'ok');

    $this->artisan('manicure:backup', ['--keep' => 3])
        ->assertSuccessful();

    $zips = File::glob(storage_path('app/backups/manicurepro_*.zip'));
    expect($zips)->not->toBeEmpty();

    $zip = new ZipArchive;
    expect($zip->open($zips[array_key_last($zips)]))->toBeTrue();
    expect($zip->locateName('database.sqlite'))->not->toBeFalse();
    expect($zip->locateName('storage_public/health-check.txt'))->not->toBeFalse();
    $zip->close();
});
