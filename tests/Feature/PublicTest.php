<?php

use App\Models\Salao;
use App\Models\ConfiguracaoSalao;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('página inicial pública é acessível', function () {
    // Instalação single-tenant: a home é a página do salão único.
    $salao = Salao::factory()->create(['ativo' => true, 'nome' => 'Fernanda Silva Nails']);

    $response = $this->get('/');
    $response->assertStatus(200);
    $response->assertSee($salao->nome);
});

test('página do salão é acessível', function () {
    $salao = Salao::factory()->create(['ativo' => true, 'slug' => 'meu-salao-teste']);

    $response = $this->get('/salao/meu-salao-teste');
    $response->assertStatus(200);
    $response->assertSee($salao->nome);
});

test('salão inativo retorna 404', function () {
    $salao = Salao::factory()->create(['ativo' => false, 'slug' => 'salao-inativo']);

    $response = $this->get('/salao/salao-inativo');
    $response->assertStatus(404);
});

test('página de agendamento online é acessível quando habilitada', function () {
    $salao = Salao::factory()->create(['ativo' => true]);
    ConfiguracaoSalao::create([
        'salao_id' => $salao->id,
        'permitir_agendamento_online' => true,
        'intervalo_agendamento' => 30,
        'antecedencia_minima' => 1,
        'antecedencia_maxima' => 30,
    ]);

    $response = $this->get("/salao/{$salao->slug}/agendar");
    $response->assertStatus(200);
});

test('página de agendamento redireciona quando desabilitada', function () {
    $salao = Salao::factory()->create(['ativo' => true]);
    ConfiguracaoSalao::create([
        'salao_id' => $salao->id,
        'permitir_agendamento_online' => false,
        'intervalo_agendamento' => 30,
        'antecedencia_minima' => 1,
        'antecedencia_maxima' => 30,
    ]);

    $response = $this->get("/salao/{$salao->slug}/agendar");
    $response->assertRedirect(route('public.salao', $salao->slug));
});

test('endpoint de slots requer parâmetros', function () {
    $response = $this->get('/api/slots');
    $response->assertStatus(302); // redirect por validação
});

test('página de login é acessível para visitantes', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
    $response->assertSee('Entrar');
});

test('usuário autenticado é redirecionado da página de login', function () {
    $user = \App\Models\User::factory()->create(['role' => 'cliente', 'ativo' => true]);

    $response = $this->actingAs($user)->get('/login');
    $response->assertRedirect();
});
