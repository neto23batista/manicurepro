<?php

use App\Models\ConfiguracaoSalao;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    ConfiguracaoSalao::create(['salao_id' => $this->salao->id]);
    $this->dono = User::factory()->create([
        'role' => 'dono', 'salao_id' => $this->salao->id, 'ativo' => true,
    ]);
});

test('dono acessa página de configurações', function () {
    $this->actingAs($this->dono)->get('/dono/configuracao')->assertOk();
});

test('dono atualiza dados do salão', function () {
    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/dados', [
        'nome'     => 'Salão Renovado',
        'telefone' => '(11) 4000-1000',
        'email'    => 'novo@salao.com',
        'cidade'   => 'São Paulo',
        'estado'   => 'SP',
    ])->assertRedirect();

    expect($this->salao->fresh()->nome)->toBe('Salão Renovado');
    expect($this->salao->fresh()->cidade)->toBe('São Paulo');
});

test('dono atualiza horários de funcionamento', function () {
    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/horarios', [
        'horarios' => [
            '1' => ['ativo' => '1', 'hora_abertura' => '09:00', 'hora_fechamento' => '18:00'],
            '2' => ['ativo' => '1', 'hora_abertura' => '09:00', 'hora_fechamento' => '18:00'],
            '0' => ['ativo' => '0'], // domingo fechado
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('horarios_funcionamento', [
        'salao_id' => $this->salao->id, 'dia_semana' => 1, 'ativo' => true,
    ]);
    $this->assertDatabaseHas('horarios_funcionamento', [
        'salao_id' => $this->salao->id, 'dia_semana' => 0, 'ativo' => false,
    ]);
});

test('dono atualiza configurações gerais', function () {
    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/config', [
        'intervalo_agendamento' => 45,
        'antecedencia_minima'   => 2,
        'antecedencia_maxima'   => 90,
        'cancelamento_prazo'    => 12,
        'pontos_por_real'       => 2,
        'pontos_para_desconto'  => 200,
        'valor_desconto_pontos' => 25,
        'lembrete_horas'        => 48,
        'fidelidade_ativo'      => '1',
        'notificar_email'       => '1',
    ])->assertRedirect();

    $config = $this->salao->fresh()->configuracao;
    expect($config->intervalo_agendamento)->toBe(45);
    expect($config->pontos_por_real)->toBe(2);
    expect($config->fidelidade_ativo)->toBeTrue();
});

test('configurações com valores inválidos são rejeitadas', function () {
    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/config', [
        'intervalo_agendamento' => 0,        // mínimo 5
        'antecedencia_minima'   => -1,
        'antecedencia_maxima'   => 1,
        'cancelamento_prazo'    => 1,
        'pontos_por_real'       => 1,
        'pontos_para_desconto'  => 1,
        'valor_desconto_pontos' => 1,
        'lembrete_horas'        => 1,
    ])->assertSessionHasErrors(['intervalo_agendamento', 'antecedencia_minima']);
});

test('dono faz upload de logo', function () {
    Storage::fake('public');

    $logo = UploadedFile::fake()->image('logo.png', 200, 200);

    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/dados', [
        'nome' => $this->salao->nome,
        'logo' => $logo,
    ])->assertRedirect();

    expect($this->salao->fresh()->logo)->not->toBeNull();
    Storage::disk('public')->assertExists($this->salao->fresh()->logo);
});

test('dono remove logo', function () {
    Storage::fake('public');
    $logo = UploadedFile::fake()->image('logo.png');
    $this->salao->update(['logo' => $logo->store('saloes/logos', 'public')]);

    $this->actingAs($this->dono)->from('/dono/configuracao')->delete('/dono/configuracao/logo')
        ->assertRedirect();

    expect($this->salao->fresh()->logo)->toBeNull();
});

test('upload de arquivo não-imagem é rejeitado', function () {
    Storage::fake('public');
    $arquivo = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/dados', [
        'nome' => $this->salao->nome,
        'logo' => $arquivo,
    ])->assertSessionHasErrors(['logo']);
});

test('outro role não acessa configurações', function () {
    $cli = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->actingAs($cli)->get('/dono/configuracao')->assertStatus(403);
});
