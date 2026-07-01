<?php

use App\Models\GaleriaFoto;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->dono = User::factory()->create(['role' => 'dono', 'salao_id' => $this->salao->id, 'ativo' => true]);
});

function novaFoto(int $salaoId, array $attrs = []): GaleriaFoto
{
    return GaleriaFoto::create(array_merge([
        'salao_id' => $salaoId,
        'caminho'  => 'galeria/' . $salaoId . '/exemplo.jpg',
        'titulo'   => 'Trabalho',
        'publicar' => true,
    ], $attrs));
}

test('dono envia fotos e elas são salvas no disco e no banco', function () {
    $this->actingAs($this->dono)->post('/dono/galeria', [
        'titulo'   => 'Francesinha',
        'publicar' => '1',
        'fotos'    => [
            UploadedFile::fake()->image('nail1.jpg'),
            UploadedFile::fake()->image('nail2.png'),
        ],
    ])->assertRedirect('/dono/galeria')->assertSessionHasNoErrors();

    $fotos = GaleriaFoto::where('salao_id', $this->salao->id)->get();
    expect($fotos)->toHaveCount(2);
    expect($fotos->first()->salao_id)->toBe($this->salao->id);

    foreach ($fotos as $foto) {
        Storage::disk('public')->assertExists($foto->caminho);
    }
});

test('upload exige imagem válida', function () {
    $this->actingAs($this->dono)->post('/dono/galeria', [
        'fotos' => [UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf')],
    ])->assertSessionHasErrors('fotos.0');

    expect(GaleriaFoto::count())->toBe(0);
});

test('toggle publicar alterna a visibilidade', function () {
    $foto = novaFoto($this->salao->id, ['publicar' => true]);

    $this->actingAs($this->dono)->patch("/dono/galeria/{$foto->id}/publicar")->assertRedirect();
    expect($foto->fresh()->publicar)->toBeFalse();

    $this->actingAs($this->dono)->patch("/dono/galeria/{$foto->id}/publicar")->assertRedirect();
    expect($foto->fresh()->publicar)->toBeTrue();
});

test('dono edita título e profissional da foto', function () {
    $foto = novaFoto($this->salao->id);

    $this->actingAs($this->dono)->put("/dono/galeria/{$foto->id}", [
        'titulo'   => 'Nova arte',
        'publicar' => '1',
    ])->assertRedirect();

    expect($foto->fresh()->titulo)->toBe('Nova arte');
});

test('remover foto apaga registro e arquivo', function () {
    $path = UploadedFile::fake()->image('x.jpg')->store('galeria/' . $this->salao->id, 'public');
    $foto = novaFoto($this->salao->id, ['caminho' => $path]);
    Storage::disk('public')->assertExists($path);

    $this->actingAs($this->dono)->delete("/dono/galeria/{$foto->id}")->assertRedirect();

    expect(GaleriaFoto::find($foto->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('dono não gerencia foto de outro salão', function () {
    $outro = Salao::factory()->create();
    $foto = novaFoto($outro->id);

    $this->actingAs($this->dono)->patch("/dono/galeria/{$foto->id}/publicar")->assertForbidden();
    $this->actingAs($this->dono)->delete("/dono/galeria/{$foto->id}")->assertForbidden();
});

test('apenas fotos publicadas aparecem na página pública', function () {
    novaFoto($this->salao->id, ['titulo' => 'Visível', 'publicar' => true]);
    novaFoto($this->salao->id, ['titulo' => 'Oculta', 'publicar' => false]);

    $resp = $this->get('/');
    $resp->assertOk();
    $resp->assertSee('Visível');
    $resp->assertDontSee('Oculta');
});

test('dono abre o índice da galeria', function () {
    novaFoto($this->salao->id, ['titulo' => 'Arte da grade']);

    $this->actingAs($this->dono)->get('/dono/galeria')
        ->assertOk()
        ->assertSee('Galeria de Trabalhos')
        ->assertSee('Arte da grade');
});

test('cliente não acessa a galeria do dono', function () {
    $cliente = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->actingAs($cliente)->get('/dono/galeria')->assertForbidden();
});
