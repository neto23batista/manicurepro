<?php

use App\Models\Agendamento;
use App\Models\Avaliacao;
use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->dono = User::factory()->create([
        'role'     => 'dono',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
    $this->cliente = Cliente::factory()->create(['salao_id' => $this->salao->id, 'nome' => 'Ana Cliente']);
});

function avaliacaoPara(Salao $salao, Manicure $manicure, Cliente $cliente, array $attrs = []): Avaliacao
{
    $ag = Agendamento::factory()->create([
        'salao_id'         => $salao->id,
        'manicure_id'      => $manicure->id,
        'cliente_id'       => $cliente->id,
        'status'           => 'concluido',
        'data_hora_inicio' => now()->subDay(),
        'data_hora_fim'    => now()->subDay()->addMinutes(30),
        'nome_cliente'     => $cliente->nome,
    ]);

    return Avaliacao::create(array_merge([
        'agendamento_id' => $ag->id,
        'cliente_id'     => $cliente->id,
        'manicure_id'    => $manicure->id,
        'salao_id'       => $salao->id,
        'nota'           => 5,
        'comentario'     => 'Atendimento excelente',
        'publicar'       => true,
    ], $attrs));
}

test('média pública ignora avaliação ocultada', function () {
    avaliacaoPara($this->salao, $this->manicure, $this->cliente, [
        'nota'       => 5,
        'comentario' => 'Nota pública cinco',
        'publicar'   => true,
    ]);
    avaliacaoPara($this->salao, $this->manicure, $this->cliente, [
        'nota'       => 1,
        'comentario' => 'Comentário oculto péssimo',
        'publicar'   => false,
    ]);

    expect($this->salao->fresh()->nota_media)->toBe(5.0);
    expect($this->manicure->fresh()->nota_media)->toBe(5.0);

    $this->get('/salao/'.$this->salao->slug)
        ->assertOk()
        ->assertSee('Nota pública cinco')
        ->assertDontSee('Comentário oculto péssimo');
});

test('dono lista e oculta avaliação do próprio salão', function () {
    $avaliacao = avaliacaoPara($this->salao, $this->manicure, $this->cliente, [
        'comentario' => 'Quero ocultar isto',
    ]);

    $this->actingAs($this->dono)
        ->get('/dono/avaliacoes')
        ->assertOk()
        ->assertSee('Quero ocultar isto')
        ->assertSee('Ana Cliente');

    $this->actingAs($this->dono)
        ->patch("/dono/avaliacoes/{$avaliacao->id}/publicar")
        ->assertRedirect();

    expect($avaliacao->fresh()->publicar)->toBeFalse();
    expect($this->salao->fresh()->nota_media)->toBe(0.0);

    $this->get('/salao/'.$this->salao->slug)
        ->assertOk()
        ->assertDontSee('Quero ocultar isto');
});

test('dono não modera avaliação de outro salão', function () {
    $outro = Salao::factory()->create(['ativo' => true]);
    $outraManicure = Manicure::factory()->create(['salao_id' => $outro->id, 'ativo' => true]);
    $outroCliente = Cliente::factory()->create(['salao_id' => $outro->id]);
    $avaliacao = avaliacaoPara($outro, $outraManicure, $outroCliente, [
        'comentario' => 'De outro salão',
    ]);

    $this->actingAs($this->dono)
        ->patch("/dono/avaliacoes/{$avaliacao->id}/publicar")
        ->assertForbidden();

    expect($avaliacao->fresh()->publicar)->toBeTrue();
});

test('manicure não acessa moderação de avaliações', function () {
    $userManicure = User::factory()->create([
        'role'     => 'manicure',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);

    $this->actingAs($userManicure)
        ->get('/dono/avaliacoes')
        ->assertForbidden();
});
