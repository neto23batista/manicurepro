<?php

use App\DataTransferObjects\CriarAgendamentoData;
use App\Enums\AgendamentoStatus;

test('cria DTO a partir de array', function () {
    $dto = CriarAgendamentoData::fromArray([
        'salao_id'         => 1,
        'manicure_id'      => 2,
        'servico_ids'      => [3, 4],
        'data_hora_inicio' => '2026-12-01 10:00:00',
        'cliente_id'       => 5,
        'user_id'          => 6,
        'nome_cliente'     => 'Maria',
        'telefone_cliente' => '11999990000',
        'observacoes'      => 'sem alergia',
        'origem'           => 'app',
        'status'           => 'confirmado',
        'cupom_id'         => 10,
    ]);

    expect($dto->salaoId)->toBe(1);
    expect($dto->manicureId)->toBe(2);
    expect($dto->servicoIds)->toBe([3, 4]);
    expect($dto->clienteId)->toBe(5);
    expect($dto->origem)->toBe('app');
    expect($dto->status)->toBe(AgendamentoStatus::Confirmado);
    expect($dto->cupomId)->toBe(10);
});

test('status default é Aguardando quando ausente ou inválido', function () {
    $dto = CriarAgendamentoData::fromArray([
        'salao_id'         => 1,
        'manicure_id'      => 1,
        'servico_ids'      => [1],
        'data_hora_inicio' => '2026-12-01 10:00:00',
    ]);

    expect($dto->status)->toBe(AgendamentoStatus::Aguardando);

    $dtoInvalido = CriarAgendamentoData::fromArray([
        'salao_id'         => 1,
        'manicure_id'      => 1,
        'servico_ids'      => [1],
        'data_hora_inicio' => '2026-12-01 10:00:00',
        'status'           => 'xpto',
    ]);
    expect($dtoInvalido->status)->toBe(AgendamentoStatus::Aguardando);
});

test('toArray reconstrói a representação array', function () {
    $dto = new CriarAgendamentoData(
        salaoId: 1,
        manicureId: 2,
        servicoIds: [3],
        dataHoraInicio: '2026-12-01 10:00:00',
    );

    $arr = $dto->toArray();
    expect($arr)->toHaveKey('salao_id');
    expect($arr['servico_ids'])->toBe([3]);
    expect($arr['status'])->toBe(AgendamentoStatus::Aguardando);
});
