<?php

use App\Models\Agendamento;
use App\Models\ConfiguracaoSalao;
use App\Models\Cupom;
use App\Models\DisponibilidadeManicure;
use App\Models\HorarioFuncionamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\User;
use App\Services\AgendaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->salao = Salao::factory()->create(['ativo' => true]);
    ConfiguracaoSalao::create([
        'salao_id'              => $this->salao->id,
        'intervalo_agendamento' => 30,
        'antecedencia_minima'   => 0,
        'antecedencia_maxima'   => 30,
    ]);
    $this->dono = User::factory()->create([
        'role'     => 'dono',
        'ativo'    => true,
        'salao_id' => $this->salao->id,
    ]);

    $userManicure = User::factory()->create(['role' => 'manicure', 'salao_id' => $this->salao->id]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $userManicure->id,
        'ativo'    => true,
    ]);

    for ($dia = 1; $dia <= 5; $dia++) {
        HorarioFuncionamento::create([
            'salao_id'        => $this->salao->id,
            'dia_semana'      => $dia,
            'hora_abertura'   => '08:00:00',
            'hora_fechamento' => '18:00:00',
            'ativo'           => true,
        ]);
        DisponibilidadeManicure::create([
            'manicure_id' => $this->manicure->id,
            'dia_semana'  => $dia,
            'hora_inicio' => '08:00:00',
            'hora_fim'    => '18:00:00',
            'ativo'       => true,
        ]);
    }

    $this->servico = Servico::factory()->create([
        'salao_id'          => $this->salao->id,
        'preco'             => 100.00,
        'duracao'           => 30,
        'ativo'             => true,
        'disponivel_online' => true,
    ]);

    $this->agendaService = app(AgendaService::class);
});

function slotLivre(Carbon $base, int $offsetHours = 0): Carbon
{
    return $base->copy()->next(Carbon::MONDAY)->setTime(9 + $offsetHours, 0);
}

function payloadAgendamento($self, ?int $cupomId, Carbon $inicio): array
{
    return [
        'salao_id'         => $self->salao->id,
        'manicure_id'      => $self->manicure->id,
        'servico_ids'      => [$self->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'origem'           => 'web',
        'status'           => 'aguardando',
        'cupom_id'         => $cupomId,
    ];
}

test('dono lista cupons do próprio salão', function () {
    Cupom::factory()->count(3)->create(['salao_id' => $this->salao->id]);
    Cupom::factory()->create(); // outro salão

    $r = $this->actingAs($this->dono)->get('/dono/cupons');
    $r->assertOk();
    $cupons = $r->viewData('cupons');
    expect($cupons->total())->toBe(3);
});

test('dono cria cupom percentual', function () {
    $this->actingAs($this->dono)->from('/dono/cupons/create')->post('/dono/cupons', [
        'codigo'   => 'natal2026',
        'tipo'     => 'percentual',
        'valor'    => 15,
        'validade' => now()->addMonth()->toDateString(),
        'ativo'    => '1',
    ])->assertRedirect('/dono/cupons');

    $this->assertDatabaseHas('cupons', [
        'codigo'   => 'NATAL2026',
        'tipo'     => 'percentual',
        'salao_id' => $this->salao->id,
    ]);
});

test('cupom com mesmo código no mesmo salão é rejeitado', function () {
    Cupom::factory()->create(['salao_id' => $this->salao->id, 'codigo' => 'DUPLA']);

    $this->actingAs($this->dono)->from('/dono/cupons/create')->post('/dono/cupons', [
        'codigo' => 'DUPLA',
        'tipo'   => 'fixo',
        'valor'  => 10,
    ])->assertSessionHasErrors(['codigo']);
});

test('cupom com validade no passado é rejeitado', function () {
    $this->actingAs($this->dono)->from('/dono/cupons/create')->post('/dono/cupons', [
        'codigo'   => 'EXPIRADO',
        'tipo'     => 'fixo',
        'valor'    => 10,
        'validade' => '2020-01-01',
    ])->assertSessionHasErrors(['validade']);
});

test('dono atualiza cupom', function () {
    $cupom = Cupom::factory()->create(['salao_id' => $this->salao->id, 'valor' => 10]);

    $this->actingAs($this->dono);
    $cupom->refresh();
    $dono = $this->dono->fresh();

    expect((int) $cupom->salao_id)->toBe((int) $dono->salao_id);
    expect((int) $dono->salao_id)->toBe((int) $this->salao->id);

    $r = $this->from('/dono/cupons')->put("/dono/cupons/{$cupom->id}", [
        'codigo' => $cupom->codigo,
        'tipo'   => 'fixo',
        'valor'  => 25,
        'ativo'  => '1',
    ]);

    $r->assertRedirect('/dono/cupons');
    expect((float) $cupom->fresh()->valor)->toEqual(25.0);
});

test('dono exclui cupom', function () {
    $cupom = Cupom::factory()->create(['salao_id' => $this->salao->id]);

    $this->actingAs($this->dono)->from('/dono/cupons')->delete("/dono/cupons/{$cupom->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('cupons', ['id' => $cupom->id]);
});

test('dono A não pode editar cupom do salão B', function () {
    $outroSalao = Salao::factory()->create();
    ConfiguracaoSalao::create(['salao_id' => $outroSalao->id]);
    $cupomOutro = Cupom::factory()->create(['salao_id' => $outroSalao->id]);

    $this->actingAs($this->dono)->get("/dono/cupons/{$cupomOutro->id}/edit")
        ->assertStatus(403);
});

test('cliente não pode acessar área de cupons', function () {
    $cli = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->actingAs($cli)->get('/dono/cupons')->assertStatus(403);
});

test('aplicar cupom válido concede desconto e consome uso', function () {
    $cupom = Cupom::factory()->create([
        'salao_id'   => $this->salao->id,
        'tipo'       => 'fixo',
        'valor'      => 20,
        'uso_maximo' => 5,
        'uso_atual'  => 0,
        'validade'   => now()->addMonth(),
        'ativo'      => true,
    ]);

    $inicio = slotLivre(now());
    $ag = $this->agendaService->criarAgendamento(payloadAgendamento($this, $cupom->id, $inicio));

    expect((float) $ag->valor_desconto)->toBe(20.0);
    expect($ag->cupom_id)->toBe($cupom->id);
    expect((int) $cupom->fresh()->uso_atual)->toBe(1);
});

test('cupom expirado não pode ser aplicado', function () {
    $cupom = Cupom::factory()->create([
        'salao_id'   => $this->salao->id,
        'tipo'       => 'fixo',
        'valor'      => 20,
        'uso_maximo' => 5,
        'uso_atual'  => 0,
        'validade'   => now()->subDay(),
        'ativo'      => true,
    ]);

    $inicio = slotLivre(now());

    expect(fn () => $this->agendaService->criarAgendamento(payloadAgendamento($this, $cupom->id, $inicio)))
        ->toThrow(ValidationException::class);

    expect((int) $cupom->fresh()->uso_atual)->toBe(0);
    expect(Agendamento::count())->toBe(0);
});

test('cupom esgotado não pode ser aplicado', function () {
    $cupom = Cupom::factory()->create([
        'salao_id'   => $this->salao->id,
        'tipo'       => 'fixo',
        'valor'      => 20,
        'uso_maximo' => 2,
        'uso_atual'  => 2,
        'validade'   => now()->addMonth(),
        'ativo'      => true,
    ]);

    $inicio = slotLivre(now());

    expect(fn () => $this->agendaService->criarAgendamento(payloadAgendamento($this, $cupom->id, $inicio)))
        ->toThrow(ValidationException::class);

    expect((int) $cupom->fresh()->uso_atual)->toBe(2);
    expect(Agendamento::count())->toBe(0);
});

test('cupom de outro salão não pode ser aplicado', function () {
    $outroSalao = Salao::factory()->create(['ativo' => true]);
    $cupomOutro = Cupom::factory()->create([
        'salao_id'   => $outroSalao->id,
        'tipo'       => 'fixo',
        'valor'      => 50,
        'uso_maximo' => 10,
        'uso_atual'  => 0,
        'validade'   => now()->addMonth(),
        'ativo'      => true,
    ]);

    $inicio = slotLivre(now());

    expect(fn () => $this->agendaService->criarAgendamento(payloadAgendamento($this, $cupomOutro->id, $inicio)))
        ->toThrow(ValidationException::class);

    expect((int) $cupomOutro->fresh()->uso_atual)->toBe(0);
    expect(Agendamento::count())->toBe(0);
});

test('uso_maximo=1: segundo apply é rejeitado (race-safe consume)', function () {
    $cupom = Cupom::factory()->create([
        'salao_id'   => $this->salao->id,
        'tipo'       => 'fixo',
        'valor'      => 10,
        'uso_maximo' => 1,
        'uso_atual'  => 0,
        'validade'   => now()->addMonth(),
        'ativo'      => true,
    ]);

    $primeiro = slotLivre(now());
    $segundo = slotLivre(now(), 1);

    $ag = $this->agendaService->criarAgendamento(payloadAgendamento($this, $cupom->id, $primeiro));
    expect((float) $ag->valor_desconto)->toBe(10.0);
    expect((int) $cupom->fresh()->uso_atual)->toBe(1);

    expect(fn () => $this->agendaService->criarAgendamento(payloadAgendamento($this, $cupom->id, $segundo)))
        ->toThrow(ValidationException::class);

    expect((int) $cupom->fresh()->uso_atual)->toBe(1);
    expect(Agendamento::count())->toBe(1);
});

test('isValido rejeita salão errado, expirado e esgotado', function () {
    $cupom = Cupom::factory()->create([
        'salao_id'   => $this->salao->id,
        'uso_maximo' => 1,
        'uso_atual'  => 0,
        'validade'   => now()->addDay(),
        'ativo'      => true,
    ]);

    expect($cupom->isValido($this->salao->id))->toBeTrue();
    expect($cupom->isValido($this->salao->id + 999))->toBeFalse();

    $expirado = Cupom::factory()->create([
        'salao_id' => $this->salao->id,
        'validade' => now()->subDay(),
        'ativo'    => true,
    ]);
    expect($expirado->isValido($this->salao->id))->toBeFalse();

    $esgotado = Cupom::factory()->create([
        'salao_id'   => $this->salao->id,
        'uso_maximo' => 3,
        'uso_atual'  => 3,
        'validade'   => now()->addMonth(),
        'ativo'      => true,
    ]);
    expect($esgotado->isValido($this->salao->id))->toBeFalse();
});
