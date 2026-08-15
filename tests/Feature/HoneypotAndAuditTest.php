<?php

use App\Models\Agendamento;
use App\Models\AuditLog;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('honeypot rejeita agendamento convidado quando o campo bot está preenchido', function () {
    $salao = Salao::factory()->create(['ativo' => true]);

    $this->from(route('public.agendar', $salao))
        ->post(route('public.agendar.store', $salao), [
            'manicure_id'      => 1,
            'servico_ids'      => [1],
            'data_hora_inicio' => now()->addDay()->toDateTimeString(),
            'nome'             => 'Bot',
            'telefone'         => '11999999999',
            'website'          => 'http://spam.test',
        ])
        ->assertSessionHasErrors('website');

    expect(Agendamento::count())->toBe(0);
});

test('honeypot rejeita registro quando o campo bot está preenchido', function () {
    Salao::factory()->create(['ativo' => true]);

    $this->from(route('register'))
        ->post(route('register.post'), [
            'name'                  => 'Bot Spam',
            'email'                 => 'bot@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'website'               => 'https://spam.example',
        ])
        ->assertSessionHasErrors('website');

    $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
    $this->assertGuest();
});

test('honeypot aceita registro com campo bot vazio', function () {
    Salao::factory()->create(['ativo' => true]);

    $this->post(route('register.post'), [
        'name'                  => 'Maria Silva',
        'email'                 => 'maria@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'website'               => '',
    ])->assertRedirect();

    $this->assertDatabaseHas('users', ['email' => 'maria@example.com']);
    $this->assertAuthenticated();
});

test('cancelamento de agendamento grava audit_log', function () {
    $salao = Salao::factory()->create(['ativo' => true]);
    $manicure = Manicure::factory()->create(['salao_id' => $salao->id, 'ativo' => true]);
    $inicio = Carbon::now()->addDay()->setTime(10, 0);

    $agendamento = Agendamento::factory()->create([
        'salao_id'         => $salao->id,
        'manicure_id'      => $manicure->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => 'confirmado',
    ]);

    $agendamento->update(['status' => 'cancelado']);

    expect(AuditLog::where('action', 'agendamento.canceled')->count())->toBe(1);

    $log = AuditLog::first();
    expect($log->auditable_id)->toBe($agendamento->id)
        ->and($log->auditable_type)->toBe(Agendamento::class)
        ->and($log->meta['from'])->toBe('confirmado')
        ->and($log->meta['to'])->toBe('cancelado');
});

test('mudança de role grava audit_log', function () {
    $user = User::factory()->create(['role' => 'cliente']);

    $user->update(['role' => 'dono']);

    expect(AuditLog::where('action', 'user.role_changed')->count())->toBe(1);

    $log = AuditLog::first();
    expect($log->auditable_id)->toBe($user->id)
        ->and($log->auditable_type)->toBe(User::class)
        ->and($log->meta['from'])->toBe('cliente')
        ->and($log->meta['to'])->toBe('dono');
});
