<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ConfiguracaoSalao;
use App\Models\DisponibilidadeManicure;
use App\Models\HorarioFuncionamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\User;
use App\Notifications\AgendamentoConfirmado;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->salao = Salao::factory()->create(['ativo' => true]);

    ConfiguracaoSalao::create([
        'salao_id' => $this->salao->id,
        'permitir_agendamento_online' => true,
        'intervalo_agendamento' => 30,
        'antecedencia_minima' => 0,
        'antecedencia_maxima' => 30,
        'notificar_email' => true,
    ]);

    $userManicure = User::factory()->create(['role' => 'manicure', 'salao_id' => $this->salao->id]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id' => $userManicure->id,
        'ativo' => true,
    ]);

    for ($dia = 1; $dia <= 5; $dia++) {
        HorarioFuncionamento::create([
            'salao_id' => $this->salao->id,
            'dia_semana' => $dia,
            'hora_abertura' => '08:00:00',
            'hora_fechamento' => '18:00:00',
            'ativo' => true,
        ]);
        DisponibilidadeManicure::create([
            'manicure_id' => $this->manicure->id,
            'dia_semana' => $dia,
            'hora_inicio' => '08:00:00',
            'hora_fim' => '18:00:00',
            'ativo' => true,
        ]);
    }

    $this->servico = Servico::factory()->create([
        'salao_id' => $this->salao->id,
        'preco' => 40.00,
        'duracao' => 30,
        'ativo' => true,
        'disponivel_online' => true,
    ]);

    $this->inicio = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
});

function payloadGuest($self, array $overrides = []): array
{
    return array_merge([
        'manicure_id' => $self->manicure->id,
        'servico_ids' => [$self->servico->id],
        'data_hora_inicio' => $self->inicio->toDateTimeString(),
        'nome' => 'Ana Guest',
        'telefone' => '(11) 98888-7777',
        'email' => 'ana.guest@example.com',
        'observacoes' => 'Preferência de cor nude',
    ], $overrides);
}

test('página de agendar permite guest sem forçar cadastro', function () {
    $this->get(route('public.agendar', $this->salao))
        ->assertOk()
        ->assertSee('Agende sem criar conta')
        ->assertSee('name="nome"', false)
        ->assertSee('name="telefone"', false)
        ->assertDontSee('Para agendar você precisa de uma conta');
});

test('guest agenda com nome e telefone e cria cliente', function () {
    $this->followingRedirects()
        ->post(route('public.agendar.store', $this->salao), payloadGuest($this))
        ->assertOk()
        ->assertSee('Agendamento recebido')
        ->assertSee('Confirmar presença')
        ->assertSee('Ana Guest');

    $cliente = Cliente::where('salao_id', $this->salao->id)->where('nome', 'Ana Guest')->first();
    expect($cliente)->not->toBeNull();
    expect(preg_replace('/\D/', '', $cliente->telefone))->toContain('11988887777');

    $ag = Agendamento::where('cliente_id', $cliente->id)->first();
    expect($ag)->not->toBeNull();
    expect($ag->origem)->toBe('guest');
    expect($ag->user_id)->toBeNull();
    expect($ag->nome_cliente)->toBe('Ana Guest');
    expect($ag->status)->toBe('aguardando');

    Notification::assertSentOnDemand(AgendamentoConfirmado::class);
});

test('guest reutiliza cliente existente pelo telefone', function () {
    $existente = Cliente::create([
        'salao_id' => $this->salao->id,
        'nome' => 'Ana Antiga',
        'telefone' => '(11) 98888-7777',
        'email' => 'antiga@example.com',
        'ativo' => true,
    ]);

    $this->post(route('public.agendar.store', $this->salao), payloadGuest($this, [
        'nome' => 'Ana Nova',
        'telefone' => '11988887777',
        'email' => null,
    ]))->assertRedirect(route('public.agendar.sucesso', $this->salao));

    expect(Cliente::where('salao_id', $this->salao->id)->count())->toBe(1);

    $existente->refresh();
    expect($existente->nome)->toBe('Ana Nova');
    expect(Agendamento::where('cliente_id', $existente->id)->count())->toBe(1);
});

test('guest booking valida telefone inválido', function () {
    $this->from(route('public.agendar', $this->salao))
        ->post(route('public.agendar.store', $this->salao), payloadGuest($this, [
            'telefone' => '123',
        ]))
        ->assertRedirect(route('public.agendar', $this->salao))
        ->assertSessionHasErrors('telefone');

    expect(Agendamento::count())->toBe(0);
});

test('guest booking exige nome e telefone', function () {
    $this->from(route('public.agendar', $this->salao))
        ->post(route('public.agendar.store', $this->salao), payloadGuest($this, [
            'nome' => '',
            'telefone' => '',
        ]))
        ->assertSessionHasErrors(['nome', 'telefone']);

    expect(Agendamento::count())->toBe(0);
});

test('rota guest store tem rate limit', function () {
    $route = collect(app('router')->getRoutes())->first(
        fn ($r) => $r->getName() === 'public.agendar.store'
    );

    expect($route)->not->toBeNull();
    expect(implode(',', $route->middleware()))->toContain('throttle:8,1');
});

test('guest booking rejeita e-mail inválido', function () {
    $this->from(route('public.agendar', $this->salao))
        ->post(route('public.agendar.store', $this->salao), payloadGuest($this, [
            'email' => 'nao-e-email',
        ]))
        ->assertSessionHasErrors('email');
});

test('guest agenda sem e-mail opcional', function () {
    $this->post(route('public.agendar.store', $this->salao), payloadGuest($this, [
        'email' => null,
    ]))->assertRedirect(route('public.agendar.sucesso', $this->salao));

    $ag = Agendamento::first();
    expect($ag)->not->toBeNull();
    expect($ag->origem)->toBe('guest');
    expect($ag->cliente->email)->toBeNull();
});

test('guest booking bloqueia quando online desabilitado', function () {
    $this->salao->configuracao->update(['permitir_agendamento_online' => false]);

    $this->post(route('public.agendar.store', $this->salao), payloadGuest($this))
        ->assertRedirect(route('public.salao', $this->salao->slug));

    expect(Agendamento::count())->toBe(0);
});

test('cliente logado continua agendando pelo fluxo autenticado', function () {
    $user = User::factory()->create([
        'role' => 'cliente',
        'ativo' => true,
        'name' => 'Cliente Logada',
        'phone' => '(11) 97777-6666',
    ]);

    $this->actingAs($user)
        ->post(route('cliente.agendamentos.store'), [
            'manicure_id' => $this->manicure->id,
            'servico_ids' => [$this->servico->id],
            'data_hora_inicio' => $this->inicio->toDateTimeString(),
            'observacoes' => null,
        ])
        ->assertRedirect();

    $ag = Agendamento::first();
    expect($ag)->not->toBeNull();
    expect($ag->user_id)->toBe($user->id);
    expect($ag->origem)->toBe('web');
});
