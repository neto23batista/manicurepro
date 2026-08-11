<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ClientePacote;
use App\Models\ConfiguracaoSalao;
use App\Models\Manicure;
use App\Models\Pacote;
use App\Models\Salao;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\PacoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    $this->salao = Salao::factory()->create(['ativo' => true]);
    ConfiguracaoSalao::create(['salao_id' => $this->salao->id]);
    $this->dono = User::factory()->create([
        'role'     => 'dono',
        'ativo'    => true,
        'salao_id' => $this->salao->id,
    ]);
    $this->cliente = Cliente::factory()->create(['salao_id' => $this->salao->id]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
});

test('dono lista pacotes do próprio salão', function () {
    Pacote::factory()->count(2)->create(['salao_id' => $this->salao->id]);
    Pacote::factory()->create(); // outro salão

    $r = $this->actingAs($this->dono)->get('/dono/pacotes');
    $r->assertOk();
    expect($r->viewData('pacotes')->total())->toBe(2);
});

test('dono cria pacote', function () {
    $this->actingAs($this->dono)->from('/dono/pacotes/create')->post('/dono/pacotes', [
        'nome'          => 'Pacote 5 Sessões',
        'sessoes'       => 5,
        'validade_dias' => 60,
        'preco'         => 199.90,
        'ativo'         => '1',
    ])->assertRedirect('/dono/pacotes');

    $this->assertDatabaseHas('pacotes', [
        'nome'     => 'Pacote 5 Sessões',
        'sessoes'  => 5,
        'salao_id' => $this->salao->id,
        'ativo'    => 1,
    ]);
});

test('dono atualiza e exclui pacote', function () {
    $pacote = Pacote::factory()->create(['salao_id' => $this->salao->id, 'preco' => 100]);

    $this->actingAs($this->dono)->put("/dono/pacotes/{$pacote->id}", [
        'nome'    => $pacote->nome,
        'sessoes' => 8,
        'preco'   => 250,
        'ativo'   => '1',
    ])->assertRedirect('/dono/pacotes');

    expect((int) $pacote->fresh()->sessoes)->toBe(8);
    expect((float) $pacote->fresh()->preco)->toEqual(250.0);

    $this->actingAs($this->dono)->delete("/dono/pacotes/{$pacote->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('pacotes', ['id' => $pacote->id]);
});

test('dono A não pode editar pacote do salão B', function () {
    $outro = Salao::factory()->create();
    $pacoteOutro = Pacote::factory()->create(['salao_id' => $outro->id]);

    $this->actingAs($this->dono)->get("/dono/pacotes/{$pacoteOutro->id}/edit")
        ->assertStatus(403);
});

test('atribuir pacote cria cliente_pacote com sessoes e validade', function () {
    $pacote = Pacote::factory()->create([
        'salao_id'      => $this->salao->id,
        'sessoes'       => 5,
        'validade_dias' => 30,
        'ativo'         => true,
    ]);

    $this->actingAs($this->dono)
        ->post(route('dono.pacotes.atribuir', $pacote), ['cliente_id' => $this->cliente->id])
        ->assertRedirect()
        ->assertSessionHas('success');

    $cp = ClientePacote::first();
    expect($cp)->not->toBeNull();
    expect($cp->cliente_id)->toBe($this->cliente->id);
    expect($cp->sessoes_restantes)->toBe(5);
    expect($cp->expires_at)->not->toBeNull();
    expect($cp->expires_at->isAfter(now()->addDays(29)))->toBeTrue();
});

test('finalizar atendimento decrementa sessao do pacote do cliente', function () {
    $pacote = Pacote::factory()->create([
        'salao_id' => $this->salao->id,
        'sessoes'  => 3,
        'ativo'    => true,
    ]);

    $cp = app(PacoteService::class)->atribuir($pacote, $this->cliente);
    expect($cp->sessoes_restantes)->toBe(3);

    $inicio = now()->subHour();
    $ag = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => 'em_andamento',
        'valor_total'      => 50,
    ]);

    app(AgendaService::class)->finalizarAtendimento($ag, ['forma' => 'pix']);

    expect((int) $cp->fresh()->sessoes_restantes)->toBe(2);
    expect($ag->fresh()->status)->toBe('concluido');
});

test('finalizar sem pacote disponivel nao falha', function () {
    $inicio = now()->subHour();
    $ag = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => 'em_andamento',
        'valor_total'      => 50,
    ]);

    $comanda = app(AgendaService::class)->finalizarAtendimento($ag, ['forma' => 'dinheiro']);

    expect($ag->fresh()->status)->toBe('concluido');
    expect($comanda)->not->toBeNull();
});

test('pacote expirado nao e consumido', function () {
    $pacote = Pacote::factory()->create([
        'salao_id' => $this->salao->id,
        'sessoes'  => 5,
        'ativo'    => true,
    ]);

    $cp = ClientePacote::create([
        'cliente_id'        => $this->cliente->id,
        'pacote_id'         => $pacote->id,
        'sessoes_restantes' => 5,
        'expires_at'        => now()->subDay(),
    ]);

    $inicio = now()->subHour();
    $ag = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => 'em_andamento',
        'valor_total'      => 50,
    ]);

    app(AgendaService::class)->finalizarAtendimento($ag, ['forma' => 'pix']);

    expect((int) $cp->fresh()->sessoes_restantes)->toBe(5);
});

test('cliente nao acessa area de pacotes', function () {
    $cli = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->actingAs($cli)->get('/dono/pacotes')->assertStatus(403);
});
