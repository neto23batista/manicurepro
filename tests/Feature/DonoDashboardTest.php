<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ConfiguracaoSalao;
use App\Models\Manicure;
use App\Models\Produto;
use App\Models\Salao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    ConfiguracaoSalao::create(['salao_id' => $this->salao->id]);

    $this->dono = User::factory()->dono()->create([
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);

    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
});

test('dashboard dono mostra ações rápidas alinhadas ao FAB e palette', function () {
    $this->actingAs($this->dono)
        ->get(route('dono.dashboard'))
        ->assertOk()
        ->assertSee('Ações rápidas', false)
        ->assertSee('Novo agendamento')
        ->assertSee('Novo cliente')
        ->assertSee('Novo cupom')
        ->assertSee('Busca rápida')
        ->assertSee(route('dono.agendamentos.create'), false);
});

test('dashboard destaca cliente com risco de no-show na agenda de hoje', function () {
    config(['manicure.no_show.limite_alerta' => 2]);

    $cliente = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'nome'     => 'Cliente Risco Dashboard',
    ]);

    // Duas faltas anteriores → risco
    foreach ([3, 2] as $dias) {
        Agendamento::factory()->create([
            'salao_id'         => $this->salao->id,
            'cliente_id'       => $cliente->id,
            'manicure_id'      => $this->manicure->id,
            'data_hora_inicio' => Carbon::now()->subDays($dias),
            'data_hora_fim'    => Carbon::now()->subDays($dias)->addMinutes(30),
            'status'           => 'nao_compareceu',
        ]);
    }

    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'cliente_id'       => $cliente->id,
        'manicure_id'      => $this->manicure->id,
        'data_hora_inicio' => Carbon::today()->setTime(14, 0),
        'data_hora_fim'    => Carbon::today()->setTime(14, 30),
        'status'           => 'confirmado',
    ]);

    $this->actingAs($this->dono)
        ->get(route('dono.dashboard'))
        ->assertOk()
        ->assertSee('Risco de no-show hoje')
        ->assertSee('Cliente Risco Dashboard')
        ->assertSee('Risco no-show')
        ->assertSee('is-risco-no-show', false);
});

test('dashboard empty state de agenda de hoje tem CTA de novo agendamento', function () {
    $this->actingAs($this->dono)
        ->get(route('dono.dashboard'))
        ->assertOk()
        ->assertSee('Nenhum agendamento hoje')
        ->assertSee('Novo agendamento');
});

test('dashboard alerta produtos com estoque baixo', function () {
    Produto::create([
        'salao_id'       => $this->salao->id,
        'nome'           => 'Removedor',
        'preco_venda'    => 15,
        'estoque_atual'  => 1,
        'estoque_minimo' => 5,
        'unidade'        => 'un',
        'ativo'          => true,
    ]);

    $this->actingAs($this->dono)
        ->get(route('dono.dashboard'))
        ->assertOk()
        ->assertSee('Estoque baixo')
        ->assertSee('Ver produtos')
        ->assertSee(route('dono.produtos.index'), false);
});

test('dashboard sem estoque baixo não mostra alerta de estoque', function () {
    $this->actingAs($this->dono)
        ->get(route('dono.dashboard'))
        ->assertOk()
        ->assertDontSee('Estoque baixo');
});

test('dashboard mostra KPIs com comparação vs mês anterior', function () {
    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'status'           => 'concluido',
        'valor_total'      => 200,
        'data_hora_inicio' => now()->startOfMonth()->addDay()->setTime(10, 0),
        'data_hora_fim'    => now()->startOfMonth()->addDay()->setTime(11, 0),
    ]);

    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'status'           => 'concluido',
        'valor_total'      => 100,
        'data_hora_inicio' => now()->subMonthNoOverflow()->startOfMonth()->addDay()->setTime(10, 0),
        'data_hora_fim'    => now()->subMonthNoOverflow()->startOfMonth()->addDay()->setTime(11, 0),
    ]);

    $this->actingAs($this->dono)
        ->get(route('dono.dashboard'))
        ->assertOk()
        ->assertSee('Faturamento do Mês')
        ->assertSee('vs mês anterior')
        ->assertSee('Novos Clientes no Mês');
});

test('dashboard alerta clientes inativos', function () {
    config(['manicure.crm.inativo_dias' => 60]);

    $cliente = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'nome'          => 'Cliente Inativo Dash',
        'ativo'         => true,
        'total_visitas' => 2,
    ]);

    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $cliente->id,
        'status'           => 'concluido',
        'data_hora_inicio' => now()->subDays(90),
        'data_hora_fim'    => now()->subDays(90)->addHour(),
    ]);

    $this->actingAs($this->dono)
        ->get(route('dono.dashboard'))
        ->assertOk()
        ->assertSee('Clientes inativos')
        ->assertSee('Ver inativos');
});
