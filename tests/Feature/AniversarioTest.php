<?php

use App\Models\Cliente;
use App\Models\Cupom;
use App\Models\Salao;
use App\Notifications\AniversarioCliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    config(['manicure.aniversario.enabled' => true, 'manicure.aniversario.cupom_presente' => true]);
    $this->salao = Salao::factory()->create(['ativo' => true]);
});

test('felicita cliente que faz aniversário hoje', function () {
    Cliente::factory()->create([
        'salao_id'        => $this->salao->id,
        'ativo'           => true,
        'email'           => 'niver@example.com',
        'data_nascimento' => now()->subYears(28),
    ]);

    $this->artisan('manicure:enviar-aniversarios')->assertSuccessful();

    Notification::assertSentOnDemand(AniversarioCliente::class);
});

test('gera um cupom-presente de aniversário', function () {
    $cliente = Cliente::factory()->create([
        'salao_id'        => $this->salao->id,
        'ativo'           => true,
        'email'           => 'niver@example.com',
        'data_nascimento' => now()->subYears(40),
    ]);

    $this->artisan('manicure:enviar-aniversarios')->assertSuccessful();

    $cupom = Cupom::where('salao_id', $this->salao->id)
        ->where('codigo', 'NIVER-' . $cliente->id . '-' . now()->year)
        ->first();

    expect($cupom)->not->toBeNull();
    expect($cupom->ativo)->toBeTrue();
    expect((int) $cupom->uso_maximo)->toBe(1);
});

test('não felicita quem não faz aniversário hoje', function () {
    Cliente::factory()->create([
        'salao_id'        => $this->salao->id,
        'ativo'           => true,
        'email'           => 'outro@example.com',
        'data_nascimento' => now()->addDays(3)->subYears(25),
    ]);

    $this->artisan('manicure:enviar-aniversarios')->assertSuccessful();

    Notification::assertNothingSent();
});

test('é idempotente: rodar duas vezes não felicita em duplicidade', function () {
    Cliente::factory()->create([
        'salao_id'        => $this->salao->id,
        'ativo'           => true,
        'email'           => 'niver@example.com',
        'data_nascimento' => now()->subYears(33),
    ]);

    $this->artisan('manicure:enviar-aniversarios')->assertSuccessful();
    $this->artisan('manicure:enviar-aniversarios')->assertSuccessful();

    Notification::assertSentOnDemandTimes(AniversarioCliente::class, 1);
    expect(Cupom::count())->toBe(1);
});

test('nascidos em 29/02 são felicitados em 28/02 de ano não bissexto', function () {
    $this->travelTo('2026-02-28 09:00:00'); // 2026 não é bissexto

    Cliente::factory()->create([
        'salao_id'        => $this->salao->id,
        'ativo'           => true,
        'email'           => 'bissexto@example.com',
        'data_nascimento' => '2000-02-29',
    ]);

    $this->artisan('manicure:enviar-aniversarios')->assertSuccessful();

    Notification::assertSentOnDemand(AniversarioCliente::class);
});

test('sem cupom-presente continua idempotente no mesmo ano', function () {
    config(['manicure.aniversario.cupom_presente' => false]);

    Cliente::factory()->create([
        'salao_id'        => $this->salao->id,
        'ativo'           => true,
        'email'           => 'niver@example.com',
        'data_nascimento' => now()->subYears(30),
    ]);

    $this->artisan('manicure:enviar-aniversarios')->assertSuccessful();
    $this->artisan('manicure:enviar-aniversarios')->assertSuccessful();

    Notification::assertSentOnDemandTimes(AniversarioCliente::class, 1);
    expect(Cupom::count())->toBe(0); // cupom desativado não gera cupom
});

test('cliente só com telefone não quebra o comando nem duplica', function () {
    // Com WhatsApp habilitado, o canal de whatsapp é usado (o fake intercepta);
    // o canal de e-mail fica de fora pois não há destinatário roteado.
    config(['manicure.whatsapp.enabled' => true]);

    $cliente = Cliente::factory()->create([
        'salao_id'        => $this->salao->id,
        'ativo'           => true,
        'email'           => null,
        'telefone'        => '(11) 99999-0000',
        'data_nascimento' => now()->subYears(27),
    ]);

    $this->artisan('manicure:enviar-aniversarios')->assertSuccessful();

    // Marcador gravado → segunda execução não reprocessa.
    expect($cliente->fresh()->aniversario_enviado_em)->not->toBeNull();
    $this->artisan('manicure:enviar-aniversarios')->assertSuccessful();
    Notification::assertSentOnDemandTimes(AniversarioCliente::class, 1);
});

test('respeita a flag de desativado', function () {
    config(['manicure.aniversario.enabled' => false]);

    Cliente::factory()->create([
        'salao_id'        => $this->salao->id,
        'ativo'           => true,
        'email'           => 'niver@example.com',
        'data_nascimento' => now()->subYears(30),
    ]);

    $this->artisan('manicure:enviar-aniversarios')->assertSuccessful();

    Notification::assertNothingSent();
});
