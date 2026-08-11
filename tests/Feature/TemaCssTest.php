<?php

use App\Models\Salao;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('layout de auth aplica cor_primaria nas CSS variables', function () {
    config(['manicure.tema.cor_primaria' => '#112233']);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('--cor-primaria: #112233', false)
        ->assertSee('--pink-500: #112233', false)
        ->assertSee('name="theme-color" content="#112233"', false)
        ->assertSee('skip-link', false)
        ->assertSee('id="mainContent"', false);
});

test('página pública aplica cor_primaria do salão (DB) sobre o config', function () {
    config(['manicure.tema.cor_primaria' => '#abcdef']);

    $salao = Salao::factory()->create(['ativo' => true, 'nome' => 'Salão Tema']);
    \App\Models\ConfiguracaoSalao::create([
        'salao_id' => $salao->id,
        'cor_primaria' => '#ff5500',
        'intervalo_agendamento' => 30,
        'antecedencia_minima' => 1,
        'antecedencia_maxima' => 30,
        'permitir_agendamento_online' => true,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('--cor-primaria: #ff5500', false)
        ->assertSee('--pink: #ff5500', false)
        ->assertDontSee('--cor-primaria: #abcdef', false);
});

test('página pública aplica cor_primaria nas CSS variables', function () {
    config(['manicure.tema.cor_primaria' => '#abcdef']);
    Salao::factory()->create(['ativo' => true, 'nome' => 'Salão Tema']);

    $this->get('/')
        ->assertOk()
        ->assertSee('--cor-primaria: #abcdef', false)
        ->assertSee('--pink: #abcdef', false);
});

test('footer público oculta links sociais quando config está vazia', function () {
    config([
        'manicure.social.instagram' => null,
        'manicure.social.facebook' => null,
        'manicure.social.tiktok' => null,
        'manicure.social.whatsapp' => null,
    ]);

    $html = view('components.public-footer', ['compact' => false, 'salao' => null])->render();

    expect($html)->not->toContain('href="#"')
        ->and($html)->not->toContain('aria-label="Instagram"');
});

test('footer público renderiza link social a partir da config', function () {
    config(['manicure.social.instagram' => '@fernandasilvanails']);

    $html = view('components.public-footer', ['compact' => false, 'salao' => null])->render();

    expect($html)->toContain('https://instagram.com/fernandasilvanails')
        ->and($html)->toContain('aria-label="Instagram"');
});
