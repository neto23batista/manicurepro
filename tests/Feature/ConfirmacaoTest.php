<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ConfiguracaoSalao;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use App\Notifications\AgendamentoLembrete;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true]);
    ConfiguracaoSalao::create([
        'salao_id'        => $this->salao->id,
        'notificar_email' => true,
    ]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
    $this->userCliente = User::factory()->create(['role' => 'cliente']);
});

function agendamentoConfirmavel($self, Carbon $inicio, string $status = 'aguardando'): Agendamento
{
    return Agendamento::factory()->create([
        'salao_id'         => $self->salao->id,
        'manicure_id'      => $self->manicure->id,
        'user_id'          => $self->userCliente->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => $status,
    ]);
}

test('link assinado confirma a presença do cliente', function () {
    $ag = agendamentoConfirmavel($this, Carbon::now()->addDay()->setTime(10, 0));

    $url = URL::signedRoute('agendamento.confirmar', ['agendamento' => $ag->id]);

    $this->get($url)->assertOk()->assertSee('Presença confirmada');

    $ag->refresh();
    expect($ag->status)->toBe('confirmado');
    expect($ag->confirmado_em)->not->toBeNull();
});

test('link de confirmação sem assinatura é rejeitado', function () {
    $ag = agendamentoConfirmavel($this, Carbon::now()->addDay());

    $this->get(route('agendamento.confirmar', $ag))->assertForbidden();
});

test('lembrete 2h é enviado e marcado, sem reenvio', function () {
    Notification::fake();

    // Começa daqui ~1h → cai na janela de 2h
    agendamentoConfirmavel($this, Carbon::now()->addMinutes(60), 'confirmado');

    $this->artisan('manicure:enviar-lembretes 2h')->assertSuccessful();

    Notification::assertSentTo($this->userCliente, AgendamentoLembrete::class);

    expect(Agendamento::whereNotNull('lembrete_2h_em')->count())->toBe(1);

    // Segunda execução não deve reenviar
    Notification::fake();
    $this->artisan('manicure:enviar-lembretes 2h')->assertSuccessful();
    Notification::assertNothingSent();
});

test('lembrete 24h ignora agendamentos que não são de amanhã', function () {
    Notification::fake();

    agendamentoConfirmavel($this, Carbon::now()->addDays(5), 'confirmado');

    $this->artisan('manicure:enviar-lembretes 24h')->assertSuccessful();

    Notification::assertNothingSent();
});

test('lembrete 24h envia para agendamentos de amanhã e é idempotente', function () {
    Notification::fake();

    $ag = agendamentoConfirmavel($this, Carbon::tomorrow()->setTime(14, 0), 'confirmado');

    $this->artisan('manicure:enviar-lembretes 24h')->assertSuccessful();

    Notification::assertSentTo($this->userCliente, AgendamentoLembrete::class);
    expect($ag->fresh()->lembrete_24h_em)->not->toBeNull();

    Notification::fake();
    $this->artisan('manicure:enviar-lembretes 24h')->assertSuccessful();
    Notification::assertNothingSent();
});

test('lembrete 2h ignora fora da janela e status cancelado', function () {
    Notification::fake();

    agendamentoConfirmavel($this, Carbon::now()->addHours(5), 'confirmado');
    agendamentoConfirmavel($this, Carbon::now()->addMinutes(45), 'cancelado');

    $this->artisan('manicure:enviar-lembretes 2h')->assertSuccessful();

    Notification::assertNothingSent();
});

test('lembrete não envia quando notificar_email está desligado', function () {
    Notification::fake();
    $this->salao->configuracao->update(['notificar_email' => false]);

    agendamentoConfirmavel($this, Carbon::now()->addMinutes(60), 'confirmado');

    $this->artisan('manicure:enviar-lembretes 2h')->assertSuccessful();

    Notification::assertNothingSent();
    expect(Agendamento::whereNotNull('lembrete_2h_em')->count())->toBe(0);
});

test('lembrete alcança guest com e-mail no Cliente', function () {
    Notification::fake();

    $cliente = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'email'    => 'guest-lembrete@example.com',
        'ativo'    => true,
    ]);

    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'user_id'          => null,
        'cliente_id'       => $cliente->id,
        'data_hora_inicio' => Carbon::now()->addMinutes(50),
        'data_hora_fim'    => Carbon::now()->addMinutes(80),
        'status'           => 'confirmado',
    ]);

    $this->artisan('manicure:enviar-lembretes 2h')->assertSuccessful();

    Notification::assertSentOnDemand(AgendamentoLembrete::class);
    expect(Agendamento::whereNotNull('lembrete_2h_em')->count())->toBe(1);
});

test('janela inválida de lembrete falha', function () {
    $this->artisan('manicure:enviar-lembretes', ['janela' => '12h'])->assertFailed();
});
