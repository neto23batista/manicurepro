<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ConfiguracaoSalao;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->salao = Salao::factory()->create();
    ConfiguracaoSalao::create([
        'salao_id' => $this->salao->id,
        'limite_alerta_no_show' => 2,
    ]);

    $this->dono = User::factory()->create([
        'role' => 'dono', 'salao_id' => $this->salao->id, 'ativo' => true,
    ]);

    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
    $this->cliente = Cliente::factory()->create(['salao_id' => $this->salao->id, 'total_faltas' => 0]);
});

function novoAgendamento($self, string $status, ?Carbon $inicio = null): Agendamento
{
    $inicio ??= Carbon::now()->subHours(2);
    return Agendamento::factory()->create([
        'salao_id' => $self->salao->id,
        'cliente_id' => $self->cliente->id,
        'manicure_id' => $self->manicure->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim' => $inicio->copy()->addMinutes(30),
        'status' => $status,
    ]);
}

test('dono marca cliente como falta e a contagem reflete', function () {
    $ag = novoAgendamento($this, 'confirmado');

    $this->actingAs($this->dono)
        ->patch(route('dono.agendamentos.status', $ag), ['status' => 'nao_compareceu'])
        ->assertRedirect();

    $ag->refresh();
    expect($ag->status)->toBe('nao_compareceu');
    expect($this->cliente->fresh()->total_faltas)->toBe(1);
});

test('nao_compareceu sem cliente vinculado nao quebra', function () {
    $inicio = Carbon::now()->subHours(2);
    $ag = Agendamento::factory()->create([
        'salao_id' => $this->salao->id,
        'cliente_id' => null,
        'nome_cliente' => 'Walk-in',
        'manicure_id' => $this->manicure->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim' => $inicio->copy()->addMinutes(30),
        'status' => 'confirmado',
    ]);

    $this->actingAs($this->dono)
        ->patch(route('dono.agendamentos.status', $ag), ['status' => 'nao_compareceu'])
        ->assertRedirect();

    expect($ag->fresh()->status)->toBe('nao_compareceu');
    expect($this->cliente->fresh()->total_faltas)->toBe(0);
});

test('cliente vira risco de no-show ao atingir o limite do salão', function () {
    $this->salao->configuracao->update(['limite_alerta_no_show' => 2]);
    ConfiguracaoSalao::esquecerCache($this->salao->id);

    novoAgendamento($this, 'nao_compareceu');
    expect($this->cliente->fresh()->eh_risco_no_show)->toBeFalse();

    novoAgendamento($this, 'nao_compareceu');
    expect($this->cliente->fresh()->total_faltas)->toBe(2);
    expect($this->cliente->fresh()->eh_risco_no_show)->toBeTrue();
});

test('limite_alerta_no_show do salão sobrescreve config global', function () {
    config(['manicure.no_show.limite_alerta' => 5]);
    $this->salao->configuracao->update(['limite_alerta_no_show' => 1]);
    ConfiguracaoSalao::esquecerCache($this->salao->id);

    novoAgendamento($this, 'nao_compareceu');

    expect($this->cliente->fresh()->total_faltas)->toBe(1);
    expect($this->cliente->fresh()->eh_risco_no_show)->toBeTrue();
});

test('badge de risco aparece na listagem de clientes e agendamentos', function () {
    $this->salao->configuracao->update(['limite_alerta_no_show' => 1]);
    ConfiguracaoSalao::esquecerCache($this->salao->id);

    $this->cliente->update(['nome' => 'Cliente Com Falta']);
    novoAgendamento($this, 'nao_compareceu');

    $this->actingAs($this->dono)
        ->get(route('dono.clientes.index'))
        ->assertOk()
        ->assertSee('Cliente Com Falta')
        ->assertSee('1 falta');

    $this->actingAs($this->dono)
        ->get(route('dono.clientes.show', $this->cliente))
        ->assertOk()
        ->assertSee('1 falta');

    $this->actingAs($this->dono)
        ->get(route('dono.agendamentos.index'))
        ->assertOk()
        ->assertSee('Cliente Com Falta')
        ->assertSee('1 falta');
});

test('dono persiste limite_alerta_no_show nas configurações', function () {
    $this->actingAs($this->dono)->from('/dono/configuracao')->put('/dono/configuracao/config', [
        'cor_primaria'          => '#e91e8c',
        'intervalo_agendamento' => 30,
        'antecedencia_minima'   => 1,
        'antecedencia_maxima'   => 30,
        'cancelamento_prazo'    => 24,
        'pontos_por_real'       => 1,
        'pontos_para_desconto'  => 100,
        'valor_desconto_pontos' => 10,
        'lembrete_horas'        => 24,
        'limite_alerta_no_show' => 3,
    ])->assertRedirect();

    expect($this->salao->fresh()->configuracao->limite_alerta_no_show)->toBe(3);
});

test('comando limpar expirados incrementa faltas do cliente', function () {
    $inicio = Carbon::now()->subHours(5);
    $ag = Agendamento::factory()->create([
        'salao_id' => $this->salao->id,
        'cliente_id' => $this->cliente->id,
        'manicure_id' => $this->manicure->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim' => $inicio->copy()->addMinutes(30),
        'status' => 'confirmado',
    ]);

    Artisan::call('manicure:limpar-expirados');

    expect($ag->fresh()->status)->toBe('nao_compareceu');
    expect($this->cliente->fresh()->total_faltas)->toBe(1);
});

test('scope naoCompareceu filtra corretamente', function () {
    novoAgendamento($this, 'nao_compareceu');
    novoAgendamento($this, 'concluido');
    novoAgendamento($this, 'confirmado', Carbon::now()->addDay());

    expect(Agendamento::naoCompareceu()->count())->toBe(1);
});

test('sair de nao_compareceu decrementa contador', function () {
    $ag = novoAgendamento($this, 'nao_compareceu');
    expect($this->cliente->fresh()->total_faltas)->toBe(1);

    $ag->update(['status' => 'confirmado']);

    expect($this->cliente->fresh()->total_faltas)->toBe(0);
});
