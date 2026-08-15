<?php

use App\Models\Agendamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\User;
use App\Notifications\AgendamentoConfirmado;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('canal database persiste a notificação na tabela notifications', function () {
    $salao = Salao::factory()->create(['ativo' => true]);
    $manicure = Manicure::factory()->create(['salao_id' => $salao->id, 'ativo' => true]);
    $servico = Servico::factory()->create(['salao_id' => $salao->id, 'preco' => 30, 'duracao' => 30]);
    $user = User::factory()->create(['role' => 'cliente']);

    $inicio = Carbon::now()->addDay()->setTime(10, 0);
    $ag = Agendamento::factory()->create([
        'salao_id'         => $salao->id,
        'manicure_id'      => $manicure->id,
        'user_id'          => $user->id,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addMinutes(30),
        'status'           => 'confirmado',
        'valor_total'      => 30,
    ]);
    $ag->servicos()->attach($servico->id, ['preco' => 30, 'duracao' => 30]);

    $user->notify(new AgendamentoConfirmado($ag));

    $this->assertDatabaseHas('notifications', [
        'notifiable_id'   => $user->id,
        'notifiable_type' => User::class,
        'type'            => AgendamentoConfirmado::class,
    ]);

    expect($user->notifications()->count())->toBe(1);
});
