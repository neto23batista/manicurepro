<?php

use App\Models\ConfiguracaoSalao;
use App\Models\Cupom;
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
});

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

    // Sanidade: cupom e dono no mesmo salão
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

    // Debug: se 403, dump cupom + user
    if ($r->status() === 403) {
        dump([
            'cupom_id'       => $cupom->id,
            'cupom_salao_id' => $cupom->salao_id,
            'user_salao_id'  => auth()->user()->salao_id,
            'authed_role'    => auth()->user()->role,
        ]);
    }
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
