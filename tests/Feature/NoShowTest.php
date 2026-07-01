<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ConfiguracaoSalao;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->salao = Salao::factory()->create();
    ConfiguracaoSalao::create(['salao_id' => $this->salao->id]);

    $this->dono = User::factory()->create([
        'role' => 'dono', 'salao_id' => $this->salao->id, 'ativo' => true,
    ]);

    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
    $this->cliente = Cliente::factory()->create(['salao_id' => $this->salao->id]);
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

test('cliente vira risco de no-show ao atingir o limite', function () {
    config(['manicure.no_show.limite_alerta' => 2]);

    novoAgendamento($this, 'nao_compareceu');
    expect($this->cliente->fresh()->eh_risco_no_show)->toBeFalse();

    novoAgendamento($this, 'nao_compareceu');
    expect($this->cliente->fresh()->eh_risco_no_show)->toBeTrue();
});

test('scope naoCompareceu filtra corretamente', function () {
    novoAgendamento($this, 'nao_compareceu');
    novoAgendamento($this, 'concluido');
    novoAgendamento($this, 'confirmado', Carbon::now()->addDay());

    expect(Agendamento::naoCompareceu()->count())->toBe(1);
});
