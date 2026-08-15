<?php

use App\Contracts\NotaFiscalProvider;
use App\Enums\NotaFiscalStatus;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\Comanda;
use App\Models\Manicure;
use App\Models\NotaFiscal;
use App\Models\Salao;
use App\Models\User;
use App\Services\Fiscal\HttpNotaFiscalProvider;
use App\Services\Fiscal\StubNotaFiscalProvider;
use App\Services\NotaFiscalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    $this->salao = Salao::factory()->create(['ativo' => true]);
    $this->dono = User::factory()->create([
        'role'     => 'dono',
        'salao_id' => $this->salao->id,
        'ativo'    => true,
    ]);
    $this->manicure = Manicure::factory()->create(['salao_id' => $this->salao->id, 'ativo' => true]);
    $this->cliente = Cliente::factory()->create(['salao_id' => $this->salao->id]);
    $this->fiscais = app(NotaFiscalService::class);
});

function agendamentoComComanda(int $salaoId, int $manicureId, int $clienteId, string $status = 'concluido'): Agendamento
{
    $inicio = now()->subHour();
    $ag = Agendamento::factory()->create([
        'salao_id'         => $salaoId,
        'manicure_id'      => $manicureId,
        'cliente_id'       => $clienteId,
        'status'           => $status,
        'valor_total'      => 120,
        'valor_desconto'   => 20,
        'data_hora_inicio' => $inicio,
        'data_hora_fim'    => $inicio->copy()->addHour(),
    ]);

    Comanda::create([
        'agendamento_id' => $ag->id,
        'salao_id'       => $salaoId,
        'cliente_id'     => $clienteId,
        'valor_servicos' => 100,
        'valor_produtos' => 0,
        'desconto'       => 0,
        'total'          => 100,
        'status'         => 'fechada',
    ]);

    return $ag->fresh(['comanda']);
}

test('fiscal fica desabilitado por padrão', function () {
    expect(config('manicure.fiscal.enabled'))->toBeFalse();
    expect(config('manicure.fiscal.driver'))->toBe('stub');
});

test('binding padrão usa StubNotaFiscalProvider', function () {
    expect(app(NotaFiscalProvider::class))->toBeInstanceOf(StubNotaFiscalProvider::class);
});

test('emitRascunho cria stub local sem chave SEFAZ', function () {
    $ag = agendamentoComComanda($this->salao->id, $this->manicure->id, $this->cliente->id);

    $nota = $this->fiscais->emitRascunho($this->salao->id, $ag, $ag->comanda);

    expect($nota)->toBeInstanceOf(NotaFiscal::class);
    expect($nota->salao_id)->toBe($this->salao->id);
    expect($nota->agendamento_id)->toBe($ag->id);
    expect($nota->comanda_id)->toBe($ag->comanda->id);
    expect($nota->status)->toBe(NotaFiscalStatus::Rascunho);
    expect($nota->numero)->toBeNull();
    expect($nota->chave)->toBeNull();
    expect($nota->payload['stub'])->toBeTrue();
    expect($nota->payload['sefaz'])->toBeFalse();
    expect($nota->payload['aviso'])->toContain('NÃO emitir SEFAZ');
    expect((float) $nota->payload['total'])->toBe(100.0);
    expect($nota->ehStub())->toBeTrue();
});

test('list retorna rascunhos do salão', function () {
    $ag = agendamentoComComanda($this->salao->id, $this->manicure->id, $this->cliente->id);
    $this->fiscais->emitRascunho($this->salao->id, $ag);

    $outro = Salao::factory()->create(['ativo' => true]);
    $agOutro = agendamentoComComanda(
        $outro->id,
        Manicure::factory()->create(['salao_id' => $outro->id])->id,
        Cliente::factory()->create(['salao_id' => $outro->id])->id,
    );
    $this->fiscais->emitRascunho($outro->id, $agOutro);

    $lista = $this->fiscais->list($this->salao->id, 0);

    expect($lista)->toHaveCount(1);
    expect($lista->first()->salao_id)->toBe($this->salao->id);
});

test('dono cria rascunho via HTTP quando módulo ativo', function () {
    config(['manicure.fiscal.enabled' => true]);

    $ag = agendamentoComComanda($this->salao->id, $this->manicure->id, $this->cliente->id);

    $this->actingAs($this->dono)
        ->post(route('dono.notas-fiscais.store'), ['agendamento_id' => $ag->id])
        ->assertRedirect();

    $nota = NotaFiscal::first();
    expect($nota)->not->toBeNull();
    expect($nota->status)->toBe(NotaFiscalStatus::Rascunho);
    expect($nota->payload['stub'])->toBeTrue();
});

test('rota fiscal aborta com módulo desligado', function () {
    config(['manicure.fiscal.enabled' => false]);

    $this->withoutExceptionHandling();

    $this->actingAs($this->dono)
        ->get(route('dono.notas-fiscais.index'));
})->throws(NotFoundHttpException::class);

test('driver http sem token cai no stub com erro no payload', function () {
    config([
        'manicure.fiscal.driver'   => 'http',
        'manicure.fiscal.base_url' => 'https://api.fiscal.test',
        'manicure.fiscal.token'    => '',
    ]);

    Http::fake();

    $ag = agendamentoComComanda($this->salao->id, $this->manicure->id, $this->cliente->id);
    $nota = app(NotaFiscalService::class)->emitRascunho($this->salao->id, $ag, $ag->comanda);

    expect(app(NotaFiscalProvider::class))->toBeInstanceOf(HttpNotaFiscalProvider::class);
    expect($nota->payload['stub'])->toBeTrue();
    expect($nota->payload['sefaz'])->toBeFalse();
    expect($nota->payload['erro'] ?? '')->toContain('incompleta');
    expect($nota->chave)->toBeNull();
    Http::assertNothingSent();
});
