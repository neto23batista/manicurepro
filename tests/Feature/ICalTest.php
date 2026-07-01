<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->salao = Salao::factory()->create(['ativo' => true, 'nome' => 'Fernanda Silva Nails']);
    $this->user = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $this->cliente = Cliente::factory()->create(['salao_id' => $this->salao->id, 'user_id' => $this->user->id]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);

    $inicio = now()->addDays(2)->setTime(14, 0);
    $this->agendamento = Agendamento::factory()->create([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'cliente_id'       => $this->cliente->id,
        'user_id'          => $this->user->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(60),
        'status'           => 'confirmado',
    ]);
});

test('cliente baixa o .ics do próprio agendamento', function () {
    $resp = $this->actingAs($this->user)->get(route('cliente.agendamentos.ical', $this->agendamento));

    $resp->assertOk();
    $resp->assertHeader('content-type', 'text/calendar; charset=utf-8');
    expect($resp->headers->get('content-disposition'))->toContain('.ics');

    $body = $resp->getContent();
    expect($body)->toContain('BEGIN:VCALENDAR');
    expect($body)->toContain('BEGIN:VEVENT');
    expect($body)->toContain('SUMMARY:Fernanda Silva Nails');
    expect($body)->toContain('END:VCALENDAR');
});

test('o .ics usa horários em UTC com marcador Z', function () {
    $ical = app(\App\Services\ICalService::class)->paraAgendamento($this->agendamento);

    $inicioUtc = $this->agendamento->data_hora_inicio->copy()->utc()->format('Ymd\THis\Z');
    expect($ical)->toContain('DTSTART:' . $inicioUtc);
    expect($ical)->toContain("\r\n"); // quebras CRLF exigidas pelo padrão
});

test('cliente não baixa o .ics de agendamento de outro', function () {
    $outroUser = User::factory()->create(['role' => 'cliente', 'ativo' => true]);

    $this->actingAs($outroUser)
        ->get(route('cliente.agendamentos.ical', $this->agendamento))
        ->assertForbidden();
});

test('visitante não autenticado é redirecionado ao login', function () {
    $this->get(route('cliente.agendamentos.ical', $this->agendamento))
        ->assertRedirect(route('login'));
});
