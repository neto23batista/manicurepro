<?php

use App\Models\Salao;
use App\Models\User;
use App\Models\ValePresente;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->admin = User::factory()->create(['role' => 'admin', 'salao_id' => null, 'ativo' => true]);
});

test('admin (sem salão vinculado) acessa as telas do dono', function () {
    $this->actingAs($this->admin);

    foreach (['/dono/galeria', '/dono/vales', '/dono/financeiro', '/dono/produtos'] as $rota) {
        $this->get($rota)->assertOk();
    }
});

test('admin emite vale e ele cai no salão principal', function () {
    $this->actingAs($this->admin)
        ->post('/dono/vales', ['valor' => 50])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $vale = ValePresente::first();
    expect($vale)->not->toBeNull();
    expect($vale->salao_id)->toBe($this->salao->id);
});
