<?php

use App\Enums\AgendamentoStatus;
use App\Events\AgendamentoFinalizado;
use App\Listeners\PedirAvaliacaoPosAtendimento;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Comanda;
use App\Models\Cupom;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use App\Notifications\PedirAvaliacao;
use App\Notifications\ReativarCliente;
use App\Notifications\SugerirRetorno;
use App\Services\AgendaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    config([
        'manicure.marketing.enabled'                => true,
        'manicure.marketing.reativar.cooldown_dias' => 30,
        'manicure.marketing.reativar.com_cupom'     => true,
        'manicure.marketing.retorno.cadencia_dias'  => 28,
        'manicure.marketing.retorno.janela_dias'    => 3,
        'manicure.marketing.retorno.cooldown_dias'  => 25,
        'manicure.crm.inativo_dias'                 => 60,
    ]);

    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
});

test('reativar-inativos respeita gate marketing.enabled', function () {
    config(['manicure.marketing.enabled' => false]);

    Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'ativo'         => true,
        'email'         => 'inativo@example.com',
        'total_visitas' => 2,
        'created_at'    => now()->subMonths(6),
    ]);

    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => Cliente::first()->id,
        'status'           => AgendamentoStatus::Concluido->value,
        'data_hora_inicio' => now()->subDays(90),
        'data_hora_fim'    => now()->subDays(90)->addHour(),
    ]);

    $this->artisan('manicure:reativar-inativos')->assertSuccessful();

    Notification::assertNothingSent();
});

test('reativar-inativos notifica inativo com cupom e respeita cooldown', function () {
    $cliente = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'ativo'         => true,
        'email'         => 'volta@example.com',
        'total_visitas' => 3,
        'total_gasto'   => 150,
    ]);

    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $cliente->id,
        'status'           => AgendamentoStatus::Concluido->value,
        'data_hora_inicio' => now()->subDays(90),
        'data_hora_fim'    => now()->subDays(90)->addHour(),
    ]);

    $this->artisan('manicure:reativar-inativos')->assertSuccessful();

    Notification::assertSentOnDemand(ReativarCliente::class);
    expect(Cupom::where('codigo', 'REATIVA-'.$cliente->id.'-'.now()->format('Ym'))->exists())->toBeTrue();
    expect($cliente->fresh()->reativacao_enviada_em)->not->toBeNull();

    $this->artisan('manicure:reativar-inativos')->assertSuccessful();
    Notification::assertSentOnDemandTimes(ReativarCliente::class, 1);
});

test('sugerir-retorno notifica cliente na janela de cadência', function () {
    $cadencia = (int) config('manicure.marketing.retorno.cadencia_dias');

    $cliente = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'ativo'         => true,
        'email'         => 'retorno@example.com',
        'total_visitas' => 2,
    ]);

    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $cliente->id,
        'status'           => AgendamentoStatus::Concluido->value,
        'data_hora_inicio' => now()->subDays($cadencia)->setTime(10, 0),
        'data_hora_fim'    => now()->subDays($cadencia)->setTime(11, 0),
    ]);

    $this->artisan('manicure:sugerir-retorno')->assertSuccessful();

    Notification::assertSentOnDemand(SugerirRetorno::class);
    expect($cliente->fresh()->retorno_sugerido_em)->not->toBeNull();

    $this->artisan('manicure:sugerir-retorno')->assertSuccessful();
    Notification::assertSentOnDemandTimes(SugerirRetorno::class, 1);
});

test('sugerir-retorno não envia se há agendamento futuro', function () {
    $cadencia = (int) config('manicure.marketing.retorno.cadencia_dias');

    $cliente = Cliente::factory()->create([
        'salao_id'      => $this->salao->id,
        'ativo'         => true,
        'email'         => 'ja-agendou@example.com',
        'total_visitas' => 2,
    ]);

    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $cliente->id,
        'status'           => AgendamentoStatus::Concluido->value,
        'data_hora_inicio' => now()->subDays($cadencia)->setTime(10, 0),
        'data_hora_fim'    => now()->subDays($cadencia)->setTime(11, 0),
    ]);

    Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $cliente->id,
        'status'           => 'confirmado',
        'data_hora_inicio' => now()->addDays(2),
        'data_hora_fim'    => now()->addDays(2)->addHour(),
    ]);

    $this->artisan('manicure:sugerir-retorno')->assertSuccessful();

    Notification::assertNothingSent();
});

test('listener pede avaliação após finalizar atendimento', function () {
    $user = User::factory()->create([
        'role'  => 'cliente',
        'email' => 'cliente@example.com',
        'ativo' => true,
    ]);
    $cliente = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $user->id,
        'email'    => $user->email,
    ]);

    $ag = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $cliente->id,
        'user_id'          => $user->id,
        'status'           => 'em_andamento',
        'valor_total'      => 80,
        'valor_desconto'   => 0,
        'data_hora_inicio' => now()->subHour(),
        'data_hora_fim'    => now()->subMinutes(30),
    ]);

    app(AgendaService::class)->finalizarAtendimento($ag, ['forma' => 'pix']);

    Notification::assertSentTo($user, PedirAvaliacao::class);
});

test('listener de avaliação respeita gate marketing.enabled', function () {
    config(['manicure.marketing.enabled' => false]);

    $user = User::factory()->create([
        'role'  => 'cliente',
        'email' => 'off@example.com',
        'ativo' => true,
    ]);
    $cliente = Cliente::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $user->id,
        'email'    => $user->email,
    ]);

    $ag = Agendamento::factory()->create([
        'salao_id'    => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'cliente_id'  => $cliente->id,
        'user_id'     => $user->id,
        'status'      => 'concluido',
    ]);

    $comanda = new Comanda([
        'agendamento_id' => $ag->id,
        'salao_id'       => $this->salao->id,
        'cliente_id'     => $cliente->id,
        'status'         => 'fechada',
        'total'          => 50,
    ]);

    (new PedirAvaliacaoPosAtendimento)->handle(new AgendamentoFinalizado($ag, $comanda));

    Notification::assertNothingSent();
});
