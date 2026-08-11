<?php

use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\FidelidadePonto;
use App\Models\ListaEspera;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create();
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id]);
    $this->user = User::factory()->create(['role' => 'cliente', 'password' => bcrypt('senha123')]);
    $this->cliente = Cliente::factory()->create([
        'user_id' => $this->user->id,
        'salao_id' => $this->salao->id,
        'nome' => 'Maria Teste',
        'email' => 'maria@teste.com',
        'telefone' => '11999990000',
        'cpf' => '52998224725',
        'endereco' => 'Rua A, 100',
        'alergias' => 'Esmalte X',
        'pontos_fidelidade' => 50,
    ]);
});

test('cliente exporta seus dados em JSON com agendamentos, avaliações, fidelidade e lista de espera', function () {
    $agendamento = Agendamento::factory()->create([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'cliente_id' => $this->cliente->id,
        'user_id' => $this->user->id,
        'status' => 'concluido',
        'nome_cliente' => 'Maria Teste',
        'telefone_cliente' => '11999990000',
        'valor_total' => 80,
    ]);

    Avaliacao::create([
        'agendamento_id' => $agendamento->id,
        'cliente_id' => $this->cliente->id,
        'manicure_id' => $this->manicure->id,
        'salao_id' => $this->salao->id,
        'nota' => 5,
        'comentario' => 'Atendimento ótimo',
    ]);

    FidelidadePonto::create([
        'cliente_id' => $this->cliente->id,
        'salao_id' => $this->salao->id,
        'agendamento_id' => $agendamento->id,
        'pontos' => 10,
        'tipo' => 'ganho',
        'descricao' => 'Visita concluída',
    ]);

    ListaEspera::create([
        'salao_id' => $this->salao->id,
        'cliente_id' => $this->cliente->id,
        'user_id' => $this->user->id,
        'periodo' => 'tarde',
        'status' => 'aguardando',
    ]);

    $r = $this->actingAs($this->user)->get(route('perfil.exportar'));

    $r->assertOk();
    $r->assertHeader('content-disposition');
    expect($r->json('usuario.email'))->toBe($this->user->email);
    expect($r->json())->toHaveKeys(['usuario', 'cliente', 'agendamentos', 'avaliacoes', 'fidelidade', 'lista_espera']);
    expect($r->json('agendamentos'))->toHaveCount(1);
    expect($r->json('agendamentos.0.status'))->toBe('concluido');
    expect((float) $r->json('agendamentos.0.valor_total'))->toBe(80.0);
    expect($r->json('avaliacoes'))->toHaveCount(1);
    expect($r->json('avaliacoes.0.nota'))->toBe(5);
    expect($r->json('avaliacoes.0.comentario'))->toBe('Atendimento ótimo');
    expect($r->json('fidelidade'))->toHaveCount(1);
    expect($r->json('fidelidade.0.pontos'))->toBe(10);
    expect($r->json('lista_espera'))->toHaveCount(1);
    expect($r->json('lista_espera.0.periodo'))->toBe('tarde');
    expect($r->json('lista_espera.0.status'))->toBe('aguardando');
});

test('cliente exclui a conta confirmando a senha e anonimiza dados relacionados', function () {
    $futuro = Agendamento::factory()->create([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'cliente_id' => $this->cliente->id,
        'user_id' => $this->user->id,
        'status' => 'confirmado',
        'data_hora_inicio' => now()->addDays(3),
        'data_hora_fim' => now()->addDays(3)->addMinutes(30),
        'nome_cliente' => 'Maria Teste',
        'telefone_cliente' => '11999990000',
        'observacoes' => 'Prefere esmalte rosa',
    ]);

    $passado = Agendamento::factory()->create([
        'salao_id' => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'cliente_id' => $this->cliente->id,
        'user_id' => $this->user->id,
        'status' => 'concluido',
        'data_hora_inicio' => now()->subDays(5),
        'data_hora_fim' => now()->subDays(5)->addMinutes(30),
        'nome_cliente' => 'Maria Teste',
        'telefone_cliente' => '11999990000',
    ]);

    Avaliacao::create([
        'agendamento_id' => $passado->id,
        'cliente_id' => $this->cliente->id,
        'manicure_id' => $this->manicure->id,
        'salao_id' => $this->salao->id,
        'nota' => 4,
        'comentario' => 'Meu comentário pessoal',
        'publicar' => true,
    ]);

    FidelidadePonto::create([
        'cliente_id' => $this->cliente->id,
        'salao_id' => $this->salao->id,
        'pontos' => 10,
        'tipo' => 'ganho',
        'descricao' => 'Bônus',
    ]);

    $espera = ListaEspera::create([
        'salao_id' => $this->salao->id,
        'cliente_id' => $this->cliente->id,
        'user_id' => $this->user->id,
        'periodo' => 'manha',
        'status' => 'aguardando',
    ]);

    $this->actingAs($this->user)
        ->delete(route('perfil.conta.destroy'), ['password' => 'senha123'])
        ->assertRedirect(route('public.index'));

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    $this->assertSoftDeleted('clientes', ['id' => $this->cliente->id]);

    $cliente = Cliente::withTrashed()->find($this->cliente->id);
    expect($cliente->nome)->toBe('Cliente removido');
    expect($cliente->email)->toBeNull();
    expect($cliente->telefone)->toBeNull();
    expect($cliente->cpf)->toBeNull();
    expect($cliente->endereco)->toBeNull();
    expect($cliente->alergias)->toBeNull();
    expect($cliente->user_id)->toBeNull();
    expect($cliente->pontos_fidelidade)->toBe(0);
    expect($cliente->ativo)->toBeFalse();

    $futuro->refresh();
    expect($futuro->status)->toBe('cancelado');
    expect($futuro->nome_cliente)->toBe('Cliente removido');
    expect($futuro->telefone_cliente)->toBeNull();
    expect($futuro->observacoes)->toBeNull();
    expect($futuro->user_id)->toBeNull();

    $passado->refresh();
    expect($passado->status)->toBe('concluido');
    expect($passado->nome_cliente)->toBe('Cliente removido');
    expect($passado->telefone_cliente)->toBeNull();

    $avaliacao = Avaliacao::where('agendamento_id', $passado->id)->first();
    expect($avaliacao)->not->toBeNull();
    expect($avaliacao->nota)->toBe(4);
    expect($avaliacao->comentario)->toBeNull();
    expect($avaliacao->publicar)->toBeFalse();

    $espera->refresh();
    expect($espera->status)->toBe('cancelado');
    expect($espera->user_id)->toBeNull();
    expect($espera->cliente_id)->toBeNull();

    $this->assertDatabaseHas('fidelidade_pontos', [
        'cliente_id' => $this->cliente->id,
        'pontos' => 10,
    ]);
});

test('exclusão exige a senha correta', function () {
    $this->actingAs($this->user)
        ->delete(route('perfil.conta.destroy'), ['password' => 'errada'])
        ->assertSessionHasErrors('password');

    $this->assertDatabaseHas('users', ['id' => $this->user->id]);
});

test('dono não pode autoexcluir a conta', function () {
    $dono = User::factory()->create(['role' => 'dono', 'salao_id' => $this->salao->id, 'password' => bcrypt('senha123')]);

    $this->actingAs($dono)
        ->delete(route('perfil.conta.destroy'), ['password' => 'senha123'])
        ->assertSessionHasErrors('password');

    $this->assertDatabaseHas('users', ['id' => $dono->id]);
});
