<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ClienteFichaHistorico;
use App\Models\ConfiguracaoSalao;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create();
    ConfiguracaoSalao::create(['salao_id' => $this->salao->id]);

    $this->dono = User::factory()->create([
        'role' => 'dono',
        'salao_id' => $this->salao->id,
        'ativo' => true,
    ]);

    $this->userManicure = User::factory()->create([
        'role' => 'manicure',
        'salao_id' => $this->salao->id,
        'ativo' => true,
    ]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id' => $this->userManicure->id,
        'ativo' => true,
    ]);

    $this->cliente = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'alergias' => 'Formol',
    ]);

    $inicio = now()->addDay()->setTime(10, 0);
    $this->agendamento = Agendamento::factory()->create([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'cliente_id' => $this->cliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim' => $inicio->copy()->addMinutes(60),
        'status' => 'confirmado',
    ]);
});

test('dono salva ficha de unhas ao editar cliente', function () {
    $this->actingAs($this->dono)->put("/dono/clientes/{$this->cliente->id}", [
        'nome' => $this->cliente->nome,
        'notas_unhas' => 'Unhas curtas e frágeis',
        'cores_preferidas' => 'Nude e rosa',
        'contraindicacoes' => 'Evitar gel',
        'ultima_formula' => 'Risqué Nude 42 + base Strength',
        'ativo' => 1,
    ])->assertRedirect('/dono/clientes');

    $this->assertDatabaseHas('clientes', [
        'id' => $this->cliente->id,
        'notas_unhas' => 'Unhas curtas e frágeis',
        'cores_preferidas' => 'Nude e rosa',
        'contraindicacoes' => 'Evitar gel',
        'ultima_formula' => 'Risqué Nude 42 + base Strength',
    ]);
});

test('dono vê ficha de unhas no show do cliente', function () {
    $this->cliente->update([
        'cores_preferidas' => 'Vermelho clássico',
        'ultima_formula' => 'Colorama 12',
    ]);

    $this->actingAs($this->dono)
        ->get(route('dono.clientes.show', $this->cliente))
        ->assertOk()
        ->assertSee('Ficha de unhas', false)
        ->assertSee('Vermelho clássico', false)
        ->assertSee('Colorama 12', false);
});

test('manicure atualiza ficha pela agenda', function () {
    $this->actingAs($this->userManicure)
        ->from(route('manicure.agenda.show', $this->agendamento))
        ->patch(route('manicure.agenda.ficha', $this->agendamento), [
            'notas_unhas' => 'Alongamento fibra',
            'cores_preferidas' => 'Branco francês',
            'contraindicacoes' => 'Sem acetona pura',
            'ultima_formula' => 'Gel Ideal White',
            'registrar_visita' => 0,
        ])
        ->assertRedirect();

    expect($this->cliente->fresh())
        ->notas_unhas->toBe('Alongamento fibra')
        ->cores_preferidas->toBe('Branco francês')
        ->contraindicacoes->toBe('Sem acetona pura')
        ->ultima_formula->toBe('Gel Ideal White');

    expect(ClienteFichaHistorico::count())->toBe(0);
});

test('manicure registra histórico da visita ao salvar ficha', function () {
    $this->actingAs($this->userManicure)
        ->patch(route('manicure.agenda.ficha', $this->agendamento), [
            'notas_unhas' => 'Manutenção',
            'cores_preferidas' => 'Rosa chá',
            'ultima_formula' => 'Impala Rosa Chá',
            'registrar_visita' => 1,
            'notas_visita' => 'Retoque nas laterais',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('cliente_ficha_historico', [
        'cliente_id' => $this->cliente->id,
        'salao_id' => $this->salao->id,
        'agendamento_id' => $this->agendamento->id,
        'user_id' => $this->userManicure->id,
        'cores' => 'Rosa chá',
        'formula' => 'Impala Rosa Chá',
        'notas' => 'Retoque nas laterais',
    ]);
});

test('manicure vê ficha no detalhe do agendamento', function () {
    $this->cliente->update([
        'contraindicacoes' => 'Evitar gel',
        'cores_preferidas' => 'Nude',
    ]);

    $this->actingAs($this->userManicure)
        ->get(route('manicure.agenda.show', $this->agendamento))
        ->assertOk()
        ->assertSee('Ficha de unhas', false)
        ->assertSee('Evitar gel', false)
        ->assertSee('Nude', false);
});

test('manicure de outro salão não atualiza ficha', function () {
    $outroSalao = Salao::factory()->create();
    $outroUser = User::factory()->create([
        'role' => 'manicure',
        'salao_id' => $outroSalao->id,
        'ativo' => true,
    ]);
    Manicure::factory()->create([
        'salao_id' => $outroSalao->id,
        'user_id' => $outroUser->id,
        'ativo' => true,
    ]);

    $this->actingAs($outroUser)
        ->patch(route('manicure.agenda.ficha', $this->agendamento), [
            'cores_preferidas' => 'Hack',
        ])
        ->assertForbidden();
});
