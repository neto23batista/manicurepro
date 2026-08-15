<?php

use App\Models\ConfiguracaoSalao;
use App\Models\Folga;
use App\Models\FolgaManicure;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    ConfiguracaoSalao::create(['salao_id' => $this->salao->id]);
    $this->dono = User::factory()->create([
        'role'     => 'dono',
        'ativo'    => true,
        'salao_id' => $this->salao->id,
    ]);

    $this->manicureUser = User::factory()->create([
        'role'     => 'manicure',
        'ativo'    => true,
        'salao_id' => $this->salao->id,
    ]);
    $this->manicure = Manicure::factory()->create([
        'user_id'  => $this->manicureUser->id,
        'salao_id' => $this->salao->id,
    ]);
});

// ===== FOLGAS DO SALÃO (DONO) =====

test('dono lista folgas do salão', function () {
    Folga::factory()->count(2)->create(['salao_id' => $this->salao->id]);

    $r = $this->actingAs($this->dono)->get('/dono/folgas');
    $r->assertOk();
});

test('dono cria folga dia inteiro', function () {
    $data = now()->addDays(15)->toDateString();

    $this->actingAs($this->dono)->from('/dono/folgas')->post('/dono/folgas', [
        'data'     => $data,
        'motivo'   => 'Natal',
        'dia_todo' => '1',
    ])->assertRedirect('/dono/folgas');

    expect(Folga::where('salao_id', $this->salao->id)
        ->whereDate('data', $data)
        ->where('dia_todo', true)
        ->exists(),
    )->toBeTrue();
});

test('dono cria folga parcial com hora_inicio/fim', function () {
    $data = now()->addDays(20)->toDateString();

    $this->actingAs($this->dono)->from('/dono/folgas')->post('/dono/folgas', [
        'data'        => $data,
        'motivo'      => 'Reforma',
        'dia_todo'    => '0',
        'hora_inicio' => '14:00',
        'hora_fim'    => '17:00',
    ])->assertRedirect();

    expect(Folga::where('salao_id', $this->salao->id)
        ->whereDate('data', $data)
        ->where('dia_todo', false)
        ->exists(),
    )->toBeTrue();
});

test('dono não pode cadastrar folga sem hora_inicio se não for dia_todo', function () {
    $this->actingAs($this->dono)->from('/dono/folgas')->post('/dono/folgas', [
        'data'     => now()->addDays(5)->toDateString(),
        'dia_todo' => '0',
    ])->assertSessionHasErrors(['hora_inicio']);
});

test('folga duplicada na mesma data é rejeitada', function () {
    $data = now()->addDays(7)->toDateString();
    Folga::factory()->create(['salao_id' => $this->salao->id, 'data' => $data]);

    $this->actingAs($this->dono)->from('/dono/folgas')->post('/dono/folgas', [
        'data'     => $data,
        'dia_todo' => '1',
    ])->assertSessionHasErrors(['data']);
});

test('dono remove folga', function () {
    $folga = Folga::factory()->create(['salao_id' => $this->salao->id]);

    $this->actingAs($this->dono)->from('/dono/folgas')->delete("/dono/folgas/{$folga->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('folgas', ['id' => $folga->id]);
});

test('dono A não remove folga do salão B', function () {
    $outroSalao = Salao::factory()->create();
    $folgaOutra = Folga::factory()->create(['salao_id' => $outroSalao->id]);

    $this->actingAs($this->dono)->from('/dono/folgas')->delete("/dono/folgas/{$folgaOutra->id}")
        ->assertStatus(403);
});

// ===== FOLGAS DA MANICURE =====

test('manicure lista próprias folgas', function () {
    FolgaManicure::factory()->count(2)->create(['manicure_id' => $this->manicure->id]);

    $r = $this->actingAs($this->manicureUser)->get('/manicure/folgas');
    $r->assertOk();
});

test('manicure cria folga', function () {
    $data = now()->addDays(10)->toDateString();

    $this->actingAs($this->manicureUser)->from('/manicure/folgas')->post('/manicure/folgas', [
        'data'     => $data,
        'motivo'   => 'Pessoal',
        'dia_todo' => '1',
    ])->assertRedirect('/manicure/folgas');

    expect(FolgaManicure::where('manicure_id', $this->manicure->id)
        ->whereDate('data', $data)
        ->exists(),
    )->toBeTrue();
});

test('manicure remove própria folga', function () {
    $folga = FolgaManicure::factory()->create(['manicure_id' => $this->manicure->id]);

    $this->actingAs($this->manicureUser)->from('/manicure/folgas')->delete("/manicure/folgas/{$folga->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('folgas_manicure', ['id' => $folga->id]);
});

test('manicure A não remove folga da manicure B', function () {
    $outroMan = Manicure::factory()->create(['salao_id' => $this->salao->id]);
    $folgaOutra = FolgaManicure::factory()->create(['manicure_id' => $outroMan->id]);

    $this->actingAs($this->manicureUser)->from('/manicure/folgas')->delete("/manicure/folgas/{$folgaOutra->id}")
        ->assertStatus(403);
});

test('manicure não pode criar folga parcial sem hora_inicio', function () {
    $this->actingAs($this->manicureUser)->from('/manicure/folgas')->post('/manicure/folgas', [
        'data'     => now()->addDays(5)->toDateString(),
        'dia_todo' => '0',
    ])->assertSessionHasErrors(['hora_inicio']);
});

test('manicure não pode criar folga duplicada na mesma data', function () {
    $data = now()->addDays(8)->toDateString();
    FolgaManicure::factory()->create(['manicure_id' => $this->manicure->id, 'data' => $data]);

    $this->actingAs($this->manicureUser)->from('/manicure/folgas')->post('/manicure/folgas', [
        'data'     => $data,
        'dia_todo' => '1',
    ])->assertSessionHasErrors(['data']);
});

test('cliente comum não pode criar folga de manicure (403)', function () {
    $cliente = User::factory()->create(['role' => 'cliente', 'ativo' => true]);

    $this->actingAs($cliente)->from('/manicure/folgas')->post('/manicure/folgas', [
        'data'     => now()->addDays(2)->toDateString(),
        'dia_todo' => '1',
    ])->assertStatus(403);
});
