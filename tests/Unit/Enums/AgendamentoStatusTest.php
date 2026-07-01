<?php

use App\Enums\AgendamentoStatus;

test('label retorna texto correto para cada status', function () {
    expect(AgendamentoStatus::Aguardando->label())->toBe('Aguardando');
    expect(AgendamentoStatus::Concluido->label())->toBe('Concluído');
    expect(AgendamentoStatus::NaoCompareceu->label())->toBe('Não Compareceu');
});

test('color retorna classe bootstrap', function () {
    expect(AgendamentoStatus::Concluido->color())->toBe('success');
    expect(AgendamentoStatus::Cancelado->color())->toBe('danger');
    expect(AgendamentoStatus::EmAndamento->color())->toBe('primary');
});

test('podeSerCancelado é verdadeiro só para Aguardando e Confirmado', function () {
    expect(AgendamentoStatus::Aguardando->podeSerCancelado())->toBeTrue();
    expect(AgendamentoStatus::Confirmado->podeSerCancelado())->toBeTrue();
    expect(AgendamentoStatus::EmAndamento->podeSerCancelado())->toBeFalse();
    expect(AgendamentoStatus::Concluido->podeSerCancelado())->toBeFalse();
    expect(AgendamentoStatus::Cancelado->podeSerCancelado())->toBeFalse();
});

test('ativosValues retorna apenas status ativos como strings', function () {
    $valores = AgendamentoStatus::ativosValues();
    expect($valores)->toBe(['aguardando', 'confirmado', 'em_andamento']);
});

test('isFinalizado identifica estados terminais', function () {
    expect(AgendamentoStatus::Concluido->isFinalizado())->toBeTrue();
    expect(AgendamentoStatus::Cancelado->isFinalizado())->toBeTrue();
    expect(AgendamentoStatus::NaoCompareceu->isFinalizado())->toBeTrue();
    expect(AgendamentoStatus::Aguardando->isFinalizado())->toBeFalse();
});
