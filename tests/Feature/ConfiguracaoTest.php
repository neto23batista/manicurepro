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
    ConfiguracaoSalao::create([
        'salao_id'     => $this->salao->id,
        'cor_primaria' => '#e91e8c',
    ]);
    $this->dono = User::factory()->create([
        'role' => 'dono', 'salao_id' => $this->salao->id, 'ativo' => true,
    ]);
});

test('dono acessa página de configurações', function () {
    $this->actingAs($this->dono)->get('/dono/configuracao')
        ->assertOk()
        ->assertSee('name="cor_primaria"', false)
        ->assertSee('corPrimariaPreview', false)
        ->assertSee('name="limite_alerta_no_show"', false);
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
        'cor_primaria'          => '#e91e8c',
        'intervalo_agendamento' => 45,
        'antecedencia_minima'   => 2,
        'antecedencia_maxima'   => 90,
        'cancelamento_prazo'    => 12,
        'pontos_por_real'       => 2,
        'pontos_para_desconto'  => 200,
        'valor_desconto_pontos' => 25,
        'lembrete_horas'        => 48,
        'limite_alerta_no_show' => 2,
        'fidelidade_ativo'      => '1',
        'notificar_email'       => '1',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $config = $this->salao->fresh()->configuracao;
    expect($config->intervalo_agendamento)->toBe(45);
    expect($config->pontos_por_real)->toBe(2);
    expect($config->fidelidade_ativo)->toBeTrue();
});

test('dono atualiza cor primária do tema', function () {
    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/config', [
        'cor_primaria'          => '#9b59b6',
        'intervalo_agendamento' => 30,
        'antecedencia_minima'   => 1,
        'antecedencia_maxima'   => 30,
        'cancelamento_prazo'    => 24,
        'pontos_por_real'       => 1,
        'pontos_para_desconto'  => 100,
        'valor_desconto_pontos' => 10,
        'lembrete_horas'        => 24,
        'limite_alerta_no_show' => 2,
    ])->assertRedirect();

    $config = $this->salao->fresh()->configuracao;
    expect($config->cor_primaria)->toBe('#9b59b6');
    $this->assertDatabaseHas('configuracoes_salao', [
        'salao_id'     => $this->salao->id,
        'cor_primaria' => '#9b59b6',
    ]);
});

test('cor primária sem hash é normalizada e salva', function () {
    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/config', [
        'cor_primaria'          => '1abc9c',
        'intervalo_agendamento' => 30,
        'antecedencia_minima'   => 1,
        'antecedencia_maxima'   => 30,
        'cancelamento_prazo'    => 24,
        'pontos_por_real'       => 1,
        'pontos_para_desconto'  => 100,
        'valor_desconto_pontos' => 10,
        'lembrete_horas'        => 24,
        'limite_alerta_no_show' => 2,
    ])->assertRedirect();

    expect($this->salao->fresh()->configuracao->cor_primaria)->toBe('#1abc9c');
});

test('cor primária inválida é rejeitada', function () {
    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/config', [
        'cor_primaria'          => 'not-a-color',
        'intervalo_agendamento' => 30,
        'antecedencia_minima'   => 1,
        'antecedencia_maxima'   => 30,
        'cancelamento_prazo'    => 24,
        'pontos_por_real'       => 1,
        'pontos_para_desconto'  => 100,
        'valor_desconto_pontos' => 10,
        'lembrete_horas'        => 24,
        'limite_alerta_no_show' => 2,
    ])->assertSessionHasErrors(['cor_primaria']);

    expect($this->salao->fresh()->configuracao->cor_primaria)->toBe('#e91e8c');
});

test('configurações com valores inválidos são rejeitadas', function () {
    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/config', [
        'cor_primaria'          => '#e91e8c',
        'intervalo_agendamento' => 0,        // mínimo 5
        'antecedencia_minima'   => -1,
        'antecedencia_maxima'   => 1,
        'cancelamento_prazo'    => 1,
        'pontos_por_real'       => 1,
        'pontos_para_desconto'  => 1,
        'valor_desconto_pontos' => 1,
        'lembrete_horas'        => 1,
        'limite_alerta_no_show' => 2,
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

test('dono faz upload de capa', function () {
    Storage::fake('public');

    $capa = UploadedFile::fake()->image('capa.jpg', 1200, 400);

    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/dados', [
        'nome'      => $this->salao->nome,
        'foto_capa' => $capa,
    ])->assertRedirect();

    expect($this->salao->fresh()->foto_capa)->not->toBeNull();
    Storage::disk('public')->assertExists($this->salao->fresh()->foto_capa);
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

test('logo acima de 3 MB é rejeitada', function () {
    Storage::fake('public');
    $logo = UploadedFile::fake()->image('logo.png')->size(3073);

    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/dados', [
        'nome' => $this->salao->nome,
        'logo' => $logo,
    ])->assertSessionHasErrors(['logo']);
});

test('capa acima de 5 MB é rejeitada', function () {
    Storage::fake('public');
    $capa = UploadedFile::fake()->image('capa.jpg')->size(5121);

    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/dados', [
        'nome'      => $this->salao->nome,
        'foto_capa' => $capa,
    ])->assertSessionHasErrors(['foto_capa']);
});

test('capa com mime inválido é rejeitada', function () {
    Storage::fake('public');
    $arquivo = UploadedFile::fake()->image('capa.gif', 800, 300);

    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/dados', [
        'nome'      => $this->salao->nome,
        'foto_capa' => $arquivo,
    ])->assertSessionHasErrors(['foto_capa']);
});

test('outro role não acessa configurações', function () {
    $cli = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->actingAs($cli)->get('/dono/configuracao')->assertStatus(403);
});
