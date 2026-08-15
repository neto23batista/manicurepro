<?php

use App\Models\Agendamento;
use App\Models\CalendarConnection;
use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use App\Services\CalendarOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->calendario = app(CalendarOAuthService::class);
});

test('authorizationUrl retorna null sem client_id', function () {
    config([
        'manicure.calendar.google.enabled'   => true,
        'manicure.calendar.google.client_id' => null,
    ]);

    expect($this->calendario->authorizationUrl('google', $this->user))->toBeNull();
    expect($this->calendario->configurado('google'))->toBeFalse();
});

test('callback google troca code por tokens e persiste conexão', function () {
    config([
        'manicure.calendar.google.enabled'       => true,
        'manicure.calendar.google.client_id'     => 'google-client',
        'manicure.calendar.google.client_secret' => 'google-secret',
        'manicure.calendar.google.redirect'      => 'http://localhost/perfil/calendario/google/callback',
    ]);

    Http::fake([
        'oauth2.googleapis.com/token' => Http::response([
            'access_token'  => 'access-xyz',
            'refresh_token' => 'refresh-xyz',
            'expires_in'    => 3600,
        ], 200),
        'www.googleapis.com/oauth2/v2/userinfo' => Http::response([
            'email' => 'cliente@gmail.com',
        ], 200),
    ]);

    $this->actingAs($this->user);
    session(['calendar_oauth.google.state' => 'state-ok']);

    $conexao = $this->calendario->handleCallback('google', 'auth-code', 'state-ok', $this->user);

    expect($conexao)->not->toBeNull();
    expect($conexao->provider)->toBe('google');
    expect($conexao->email)->toBe('cliente@gmail.com');
    expect($conexao->access_token)->toBe('access-xyz');
    expect($conexao->refresh_token)->toBe('refresh-xyz');
    expect(CalendarConnection::where('user_id', $this->user->id)->count())->toBe(1);
});

test('syncAgendamento cria evento no Google Calendar', function () {
    config([
        'manicure.calendar.google.enabled'       => true,
        'manicure.calendar.google.client_id'     => 'google-client',
        'manicure.calendar.google.client_secret' => 'google-secret',
    ]);

    CalendarConnection::create([
        'user_id'       => $this->user->id,
        'provider'      => 'google',
        'access_token'  => 'tok',
        'refresh_token' => 'ref',
        'expires_at'    => now()->addHour(),
        'email'         => 'cliente@gmail.com',
        'meta'          => [],
    ]);

    Http::fake([
        'www.googleapis.com/calendar/v3/calendars/primary/events' => Http::response([
            'id' => 'evt-123',
        ], 200),
    ]);

    $salao = Salao::factory()->create(['ativo' => true]);
    $manicure = Manicure::factory()->create(['salao_id' => $salao->id]);
    $cliente = Cliente::factory()->create(['salao_id' => $salao->id]);
    $inicio = now()->addDay()->setTime(10, 0);

    $ag = Agendamento::factory()->create([
        'salao_id'         => $salao->id,
        'manicure_id'      => $manicure->id,
        'cliente_id'       => $cliente->id,
        'user_id'          => $this->user->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addHour(),
        'status'           => 'confirmado',
    ]);

    $ok = $this->calendario->syncAgendamento($ag);

    expect($ok)->toBeTrue();
    $meta = CalendarConnection::where('user_id', $this->user->id)->first()->meta;
    expect($meta['events'][(string) $ag->id])->toBe('evt-123');
});

test('rota de conexão redireciona quando google configurado', function () {
    config([
        'manicure.calendar.google.enabled'       => true,
        'manicure.calendar.google.client_id'     => 'google-client',
        'manicure.calendar.google.client_secret' => 'google-secret',
    ]);

    $this->actingAs($this->user)
        ->get(route('calendar.oauth.redirect', 'google'))
        ->assertRedirect();
});

test('perfil mostra seção Calendário', function () {
    $this->actingAs($this->user)
        ->get(route('perfil.edit'))
        ->assertOk()
        ->assertSee('Calendário');
});

test('disconnect remove conexão', function () {
    CalendarConnection::create([
        'user_id'      => $this->user->id,
        'provider'     => 'outlook',
        'access_token' => 'tok',
        'email'        => 'a@b.com',
    ]);

    expect($this->calendario->disconnect($this->user, 'outlook'))->toBeTrue();
    expect(CalendarConnection::where('user_id', $this->user->id)->exists())->toBeFalse();
});
