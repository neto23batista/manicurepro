<?php

use App\Services\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('ImageOptimizer está disponível quando GD ou Intervention existem', function () {
    $optimizer = new ImageOptimizer;

    expect($optimizer->isAvailable())->toBeTrue();
});

test('ImageOptimizer redimensiona imagem maior que o limite', function () {
    $optimizer = new ImageOptimizer;

    if (! $optimizer->isAvailable()) {
        $this->markTestSkipped('GD/Intervention indisponível neste ambiente.');
    }

    $upload = UploadedFile::fake()->image('grande.jpg', 3000, 2000);
    $path = $optimizer->storeOptimized($upload, 'galeria/test', 'public');

    Storage::disk('public')->assertExists($path);

    $full = Storage::disk('public')->path($path);
    [$width, $height] = getimagesize($full);

    expect($width)->toBeLessThanOrEqual(ImageOptimizer::MAX_WIDTH);
    expect($height)->toBeLessThanOrEqual(ImageOptimizer::MAX_HEIGHT);
    expect($width)->toBe(ImageOptimizer::MAX_WIDTH);
    expect($height)->toBe(1280);
});

test('ImageOptimizer não amplia imagem menor que o limite', function () {
    $optimizer = new ImageOptimizer;

    if (! $optimizer->isAvailable()) {
        $this->markTestSkipped('GD/Intervention indisponível neste ambiente.');
    }

    $upload = UploadedFile::fake()->image('pequena.jpg', 400, 300);
    $path = $optimizer->storeOptimized($upload, 'galeria/test', 'public');

    $full = Storage::disk('public')->path($path);
    [$width, $height] = getimagesize($full);

    expect($width)->toBe(400);
    expect($height)->toBe(300);
});
