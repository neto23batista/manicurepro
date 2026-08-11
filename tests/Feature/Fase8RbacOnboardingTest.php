<?php

use App\Models\AuditLog;
use App\Models\ConfiguracaoSalao;
use App\Models\Pacote;
use App\Models\Salao;
use App\Models\User;
use App\Models\ValePresente;
use App\Policies\PacotePolicy;
use App\Policies\ValePresentePolicy;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->config = ConfiguracaoSalao::create([
        'salao_id'     => $this->salao->id,
        'cor_primaria' => '#e91e8c',
    ]);
    $this->dono = User::factory()->create([
        'role' => 'dono', 'salao_id' => $this->salao->id, 'ativo' => true,
    ]);
    $this->atendente = User::factory()->create([
        'role' => 'atendente', 'salao_id' => $this->salao->id, 'ativo' => true,
    ]);
});

// ---------- Policies ----------

test('PacotePolicy: dono e atendente do salão; IDOR bloqueado', function () {
    $pacote = Pacote::factory()->create(['salao_id' => $this->salao->id]);
    $outro = Pacote::factory()->create();

    $policy = new PacotePolicy();
    expect($policy->viewAny($this->dono))->toBeTrue();
    expect($policy->viewAny($this->atendente))->toBeTrue();
    expect($policy->update($this->dono, $pacote))->toBeTrue();
    expect($policy->update($this->dono, $outro))->toBeFalse();
});

test('ValePresentePolicy: atendente sem grant não acessa', function () {
    $vale = ValePresente::create([
        'salao_id' => $this->salao->id,
        'codigo'   => 'TESTE123',
        'valor'    => 50,
        'saldo'    => 50,
        'status'   => ValePresente::STATUS_ATIVO,
    ]);

    $policy = new ValePresentePolicy();
    expect($policy->viewAny($this->dono))->toBeTrue();
    expect($policy->viewAny($this->atendente))->toBeFalse();
    expect($policy->view($this->dono, $vale))->toBeTrue();
});

// ---------- Auditoria ----------

test('dono vê tela de auditoria read-only', function () {
    AuditLog::create([
        'user_id'    => $this->dono->id,
        'action'     => 'caixa.fechado',
        'ip'         => '127.0.0.1',
        'created_at' => now(),
    ]);

    $this->actingAs($this->dono)
        ->get('/dono/auditoria')
        ->assertOk()
        ->assertSee('caixa.fechado');
});

test('atendente sem grant não acessa auditoria', function () {
    $this->actingAs($this->atendente)->get('/dono/auditoria')->assertForbidden();
});

// ---------- Permissions JSON ----------

test('sem role_permissions, defaults intactos (atendente bloqueado em financeiro/vales/config)', function () {
    $this->actingAs($this->atendente);
    $this->get('/dono/financeiro')->assertForbidden();
    $this->get('/dono/vales')->assertForbidden();
    $this->get('/dono/configuracao')->assertForbidden();
});

test('grant financeiro.view libera atendente no financeiro', function () {
    $this->config->update([
        'role_permissions' => [
            'atendente' => ['grant' => ['financeiro.view'], 'revoke' => []],
        ],
    ]);
    ConfiguracaoSalao::esquecerCache($this->salao->id);

    $this->actingAs($this->atendente)->get('/dono/financeiro')->assertOk();
    // Outros sensíveis continuam bloqueados
    $this->actingAs($this->atendente)->get('/dono/vales')->assertForbidden();
});

test('revoke remove grant sem alterar defaults de outras roles', function () {
    $svc = app(PermissionService::class);
    $payload = $svc->sanitizePayload([
        'atendente' => [
            'grant'  => ['financeiro.view', 'vales.manage'],
            'revoke' => ['vales.manage'],
        ],
    ]);

    expect($payload['atendente']['grant'])->toBe(['financeiro.view']);
    expect($payload['atendente']['revoke'])->toBe(['vales.manage']);
});

test('dono salva permissões extras na configuração', function () {
    $this->actingAs($this->dono)->put('/dono/configuracao/permissoes', [
        'roles' => [
            'atendente' => [
                'grant' => ['auditoria.view'],
            ],
        ],
    ])->assertRedirect();

    $fresh = $this->config->fresh();
    expect($fresh->role_permissions['atendente']['grant'] ?? [])->toContain('auditoria.view');
});

// ---------- Onboarding ----------

test('dono vê checklist no dashboard quando onboarding incompleto', function () {
    $this->actingAs($this->dono)
        ->get('/dono/dashboard')
        ->assertOk()
        ->assertSee('Primeiros passos');
});

test('dono acessa wizard e pode dispensar', function () {
    $this->actingAs($this->dono)
        ->get('/dono/onboarding')
        ->assertOk()
        ->assertSee('Configuração inicial');

    $this->actingAs($this->dono)
        ->post('/dono/onboarding/dismiss')
        ->assertRedirect('/dono/dashboard');

    expect($this->config->fresh()->onboarding_dismissed_at)->not->toBeNull();

    $this->actingAs($this->dono)
        ->get('/dono/dashboard')
        ->assertOk()
        ->assertDontSee('Primeiros passos');
});

test('atendente não acessa onboarding', function () {
    $this->actingAs($this->atendente)->get('/dono/onboarding')->assertForbidden();
});

test('config tabs reorganizadas incluem Permissões', function () {
    $this->actingAs($this->dono)
        ->get('/dono/configuracao?tab=permissoes')
        ->assertOk()
        ->assertSee('Permissões extras por role')
        ->assertSee('Identidade')
        ->assertSee('Operação');
});
