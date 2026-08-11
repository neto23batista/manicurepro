<?php

use App\Models\Agendamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true, 'nome' => 'Fernanda Silva Nails']);
    $this->admin = User::factory()->create(['role' => 'admin', 'ativo' => true]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'comissao' => 40,
        'ativo'    => true,
    ]);
});

test('admin exporta relatório PDF com content-type application/pdf', function () {
    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'status'           => 'concluido',
        'valor_total'      => 100,
        'valor_desconto'   => 0,
        'data_hora_inicio' => now(),
        'data_hora_fim'    => now()->addHour(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.relatorios.pdf', [
        'salao_id'    => $this->salao->id,
        'data_inicio' => now()->startOfMonth()->toDateString(),
        'data_fim'    => now()->endOfMonth()->toDateString(),
    ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toStartWith('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('.pdf');
});

test('admin exporta relatório CSV com agendamentos do período', function () {
    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'status'           => 'concluido',
        'valor_total'      => 120,
        'valor_desconto'   => 10,
        'data_hora_inicio' => now(),
        'data_hora_fim'    => now()->addHour(),
        'nome_cliente'     => 'Maria CSV',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.relatorios.csv', [
        'salao_id'    => $this->salao->id,
        'data_inicio' => now()->startOfMonth()->toDateString(),
        'data_fim'    => now()->endOfMonth()->toDateString(),
    ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toStartWith('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('.csv');
    expect($response->streamedContent())->toContain('Maria CSV');
    expect($response->streamedContent())->toContain('120.00');
});

test('visitante não autenticado é redirecionado ao login no PDF', function () {
    $this->get(route('admin.relatorios.pdf'))->assertRedirect(route('login'));
});

test('visitante não autenticado é redirecionado ao login no CSV', function () {
    $this->get(route('admin.relatorios.csv'))->assertRedirect(route('login'));
});
