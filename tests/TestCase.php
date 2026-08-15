<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CI / Pest não rodam `npm run build` antes dos testes. Um manifest
        // mínimo evita ViteManifestNotFoundException e ainda gera tags
        // <script>/<link> para o middleware CSP aplicar nonce.
        $this->ensureViteManifestStub();
    }

    protected function ensureViteManifestStub(): void
    {
        $dir = public_path('build');
        File::ensureDirectoryExists($dir);

        $manifest = $dir.'/manifest.json';
        if (File::exists($manifest)) {
            return;
        }

        File::put($manifest, json_encode([
            'resources/css/app.css' => [
                'file'    => 'assets/app-test.css',
                'src'     => 'resources/css/app.css',
                'isEntry' => true,
            ],
            'resources/js/app.js' => [
                'file'    => 'assets/app-test.js',
                'src'     => 'resources/js/app.js',
                'isEntry' => true,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        File::ensureDirectoryExists($dir.'/assets');
        File::put($dir.'/assets/app-test.css', '/* test stub */');
        File::put($dir.'/assets/app-test.js', '/* test stub */');
    }
}
