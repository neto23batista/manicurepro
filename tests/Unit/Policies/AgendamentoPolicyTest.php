<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use App\Policies\AgendamentoPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new AgendamentoPolicy;
    $this->salaoA = Salao::factory()->create();
    $this->salaoB = Salao::factory()->create();

    $this->dono = User::factory()->create(['role' => 'dono', 'salao_id' => $this->salaoA->id]);
    $this->admin = User::factory()->create(['role' => 'admin']);

    $userManicure = User::factory()->create(['role' => 'manicure', 'salao_id' => $this->salaoA->id]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salaoA->id, 'user_id' => $userManicure->id]);
    $this->userManicure = $userManicure;

    $this->userCliente = User::factory()->create(['role' => 'cliente']);
    $this->cliente = Cliente::create([
        'user_id'  => $this->userCliente->id,
        'salao_id' => $this->salaoA->id,
        'nome'     => 'X', 'email' => 'x@x.com',
    ]);

    $this->agSalaoA = Agendamento::factory()->create([
        'salao_id'    => $this->salaoA->id,
        'manicure_id' => $this->manicure->id,
        'cliente_id'  => $this->cliente->id,
    ]);
});

test('admin pode visualizar qualquer agendamento (via Gate)', function () {
    // before() só dispara via Gate/$user->can, não via call direta na policy
    expect($this->admin->can('view', $this->agSalaoA))->toBeTrue();
});

test('dono pode visualizar agendamentos do próprio salão', function () {
    expect($this->policy->view($this->dono, $this->agSalaoA))->toBeTrue();
});

test('dono NÃO pode visualizar agendamentos de outro salão', function () {
    $agOutroSalao = Agendamento::factory()->create(['salao_id' => $this->salaoB->id, 'manicure_id' => $this->manicure->id]);
    expect($this->policy->view($this->dono, $agOutroSalao))->toBeFalse();
});

test('manicure só vê os próprios agendamentos', function () {
    expect($this->policy->view($this->userManicure, $this->agSalaoA))->toBeTrue();
});

test('cliente vê seu próprio agendamento', function () {
    expect($this->policy->view($this->userCliente, $this->agSalaoA))->toBeTrue();
});

test('cliente NÃO vê agendamento de outro cliente', function () {
    $outroUser = User::factory()->create(['role' => 'cliente']);
    $outroCliente = Cliente::create([
        'user_id' => $outroUser->id, 'salao_id' => $this->salaoA->id,
        'nome'    => 'Y', 'email' => 'y@y.com',
    ]);
    $agOutro = Agendamento::factory()->create([
        'salao_id' => $this->salaoA->id, 'manicure_id' => $this->manicure->id, 'cliente_id' => $outroCliente->id,
    ]);
    expect($this->policy->view($this->userCliente, $agOutro))->toBeFalse();
});

test('cliente sem cadastro NÃO vê agendamento de balcão (cliente_id null)', function () {
    $intruso = User::factory()->create(['role' => 'cliente']);
    $balcao = Agendamento::factory()->create([
        'salao_id'    => $this->salaoA->id,
        'manicure_id' => $this->manicure->id,
        'cliente_id'  => null,
        'user_id'     => $this->dono->id,
    ]);

    expect($this->policy->view($intruso, $balcao))->toBeFalse();
    expect($intruso->can('view', $balcao))->toBeFalse();
});

test('cancel retorna false se status não permite cancelamento', function () {
    $ag = Agendamento::factory()->create([
        'salao_id' => $this->salaoA->id, 'manicure_id' => $this->manicure->id, 'cliente_id' => $this->cliente->id,
        'status'   => 'concluido',
    ]);
    expect($this->policy->cancel($this->userCliente, $ag))->toBeFalse();
});

test('dono pode atualizar agendamento do próprio salão', function () {
    expect($this->policy->update($this->dono, $this->agSalaoA))->toBeTrue();
});

test('manicure pode atualizar o próprio agendamento', function () {
    expect($this->policy->update($this->userManicure, $this->agSalaoA))->toBeTrue();
});

test('cliente e staff podem criar agendamento; manicure não', function () {
    expect($this->policy->create($this->userCliente))->toBeTrue();
    expect($this->policy->create($this->dono))->toBeTrue();
    expect($this->policy->create($this->userManicure))->toBeFalse();
    expect($this->admin->can('create', Agendamento::class))->toBeTrue();
});

test('só o cliente dono pode avaliar; manicure e dono não', function () {
    expect($this->policy->review($this->userCliente, $this->agSalaoA))->toBeTrue();
    expect($this->policy->review($this->userManicure, $this->agSalaoA))->toBeFalse();
    expect($this->policy->review($this->dono, $this->agSalaoA))->toBeFalse();
    expect($this->userManicure->can('review', $this->agSalaoA))->toBeFalse();
});
