<?php

use App\Enums\AgendamentoStatus;
use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ConfiguracaoSalao;
use App\Models\Cupom;
use App\Models\DisponibilidadeManicure;
use App\Models\FidelidadePonto;
use App\Models\HorarioFuncionamento;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\Servico;
use App\Models\ServicoVariacao;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\FidelidadeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->salao = Salao::factory()->create(['ativo' => true]);
    ConfiguracaoSalao::create([
        'salao_id'              => $this->salao->id,
        'intervalo_agendamento' => 30,
        'antecedencia_minima'   => 0,
        'antecedencia_maxima'   => 30,
        'fidelidade_ativo'      => true,
        'pontos_por_real'       => 1,
        'pontos_para_desconto'  => 100,
        'valor_desconto_pontos' => 10,
    ]);

    $userManicure = User::factory()->create(['role' => 'manicure', 'salao_id' => $this->salao->id]);
    $this->manicure = Manicure::factory()->create([
        'salao_id' => $this->salao->id,
        'user_id'  => $userManicure->id,
        'ativo'    => true,
    ]);

    for ($dia = 1; $dia <= 5; $dia++) {
        HorarioFuncionamento::create([
            'salao_id'        => $this->salao->id,
            'dia_semana'      => $dia,
            'hora_abertura'   => '08:00:00',
            'hora_fechamento' => '18:00:00',
            'ativo'           => true,
        ]);
        DisponibilidadeManicure::create([
            'manicure_id' => $this->manicure->id,
            'dia_semana'  => $dia,
            'hora_inicio' => '08:00:00',
            'hora_fim'    => '18:00:00',
            'ativo'       => true,
        ]);
    }

    $this->servico = Servico::factory()->create([
        'salao_id'          => $this->salao->id,
        'preco'             => 50.00,
        'duracao'           => 30,
        'ativo'             => true,
        'disponivel_online' => true,
        'custo_estimado'    => 12.50,
    ]);

    $userCli = User::factory()->create(['role' => 'cliente']);
    $this->cliente = Cliente::create([
        'user_id'  => $userCli->id,
        'salao_id' => $this->salao->id,
        'nome'     => 'Cliente Fase6',
        'email'    => 'fase6@test.com',
    ]);

    $this->agenda = app(AgendaService::class);
    $this->fidelidade = app(FidelidadeService::class);
});

function fase6Slot(Carbon $base, int $offsetHours = 0): Carbon
{
    return $base->copy()->next(Carbon::MONDAY)->setTime(9 + $offsetHours, 0);
}

test('sem variação: booking usa preço e duração base', function () {
    $inicio = fase6Slot(now());
    $ag = $this->agenda->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'cliente_id'       => $this->cliente->id,
        'origem'           => 'web',
        'status'           => 'aguardando',
    ]);

    expect((float) $ag->valor_total)->toBe(50.0);
    expect((int) $ag->data_hora_inicio->diffInMinutes($ag->data_hora_fim))->toBe(30);
    expect($ag->servicos->first()->pivot->servico_variacao_id)->toBeNull();
});

test('com variação: booking usa preço e duração da variação', function () {
    $gel = ServicoVariacao::factory()->create([
        'servico_id' => $this->servico->id,
        'nome'       => 'Gel',
        'preco'      => 80,
        'duracao'    => 60,
        'ativo'      => true,
    ]);

    $inicio = fase6Slot(now());
    $ag = $this->agenda->criarAgendamento([
        'salao_id'          => $this->salao->id,
        'manicure_id'       => $this->manicure->id,
        'servico_ids'       => [$this->servico->id],
        'servico_variacoes' => [$this->servico->id => $gel->id],
        'data_hora_inicio'  => $inicio->toDateTimeString(),
        'cliente_id'        => $this->cliente->id,
        'origem'            => 'web',
        'status'            => 'aguardando',
    ]);

    expect((float) $ag->valor_total)->toBe(80.0);
    expect((int) $ag->data_hora_inicio->diffInMinutes($ag->data_hora_fim))->toBe(60);
    expect((int) $ag->servicos->first()->pivot->servico_variacao_id)->toBe($gel->id);
    expect((float) $ag->servicos->first()->pivot->preco)->toBe(80.0);
});

test('variação de outro serviço é rejeitada', function () {
    $outro = Servico::factory()->create(['salao_id' => $this->salao->id, 'preco' => 40, 'duracao' => 30]);
    $varOutro = ServicoVariacao::factory()->create([
        'servico_id' => $outro->id,
        'preco'      => 99,
        'duracao'    => 90,
    ]);

    $inicio = fase6Slot(now());

    expect(fn () => $this->agenda->criarAgendamento([
        'salao_id'          => $this->salao->id,
        'manicure_id'       => $this->manicure->id,
        'servico_ids'       => [$this->servico->id],
        'servico_variacoes' => [$this->servico->id => $varOutro->id],
        'data_hora_inicio'  => $inicio->toDateTimeString(),
        'origem'            => 'web',
        'status'            => 'aguardando',
    ]))->toThrow(ValidationException::class);
});

test('cupom primeira compra rejeita cliente com atendimento concluído', function () {
    Agendamento::factory()->create([
        'salao_id'    => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'cliente_id'  => $this->cliente->id,
        'status'      => AgendamentoStatus::Concluido->value,
    ]);

    $cupom = Cupom::factory()->create([
        'salao_id'        => $this->salao->id,
        'tipo'            => 'fixo',
        'valor'           => 10,
        'primeira_compra' => true,
        'ativo'           => true,
        'validade'        => now()->addMonth(),
    ]);

    $inicio = fase6Slot(now());

    expect(fn () => $this->agenda->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'cliente_id'       => $this->cliente->id,
        'cupom_id'         => $cupom->id,
        'origem'           => 'web',
        'status'           => 'aguardando',
    ]))->toThrow(ValidationException::class);
});

test('cupom de cliente específico rejeita outro cliente', function () {
    $outroUser = User::factory()->create(['role' => 'cliente']);
    $outro = Cliente::create([
        'user_id'  => $outroUser->id,
        'salao_id' => $this->salao->id,
        'nome'     => 'Outro',
        'email'    => 'outro@test.com',
    ]);

    $cupom = Cupom::factory()->create([
        'salao_id'   => $this->salao->id,
        'tipo'       => 'fixo',
        'valor'      => 15,
        'cliente_id' => $this->cliente->id,
        'ativo'      => true,
        'validade'   => now()->addMonth(),
    ]);

    $inicio = fase6Slot(now());

    expect(fn () => $this->agenda->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'cliente_id'       => $outro->id,
        'cupom_id'         => $cupom->id,
        'origem'           => 'web',
        'status'           => 'aguardando',
    ]))->toThrow(ValidationException::class);
});

test('cupom de serviço específico exige o serviço no booking', function () {
    $outro = Servico::factory()->create(['salao_id' => $this->salao->id, 'preco' => 40, 'duracao' => 30]);
    $cupom = Cupom::factory()->create([
        'salao_id'   => $this->salao->id,
        'tipo'       => 'fixo',
        'valor'      => 10,
        'servico_id' => $this->servico->id,
        'ativo'      => true,
        'validade'   => now()->addMonth(),
    ]);

    $inicio = fase6Slot(now());

    expect(fn () => $this->agenda->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$outro->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'cliente_id'       => $this->cliente->id,
        'cupom_id'         => $cupom->id,
        'origem'           => 'web',
        'status'           => 'aguardando',
    ]))->toThrow(ValidationException::class);
});

test('cupom respeita valor mínimo e uso por cliente', function () {
    $cupom = Cupom::factory()->create([
        'salao_id'               => $this->salao->id,
        'tipo'                   => 'fixo',
        'valor'                  => 10,
        'minimo_pedido'          => 100,
        'uso_maximo_por_cliente' => 1,
        'uso_maximo'             => 10,
        'ativo'                  => true,
        'validade'               => now()->addMonth(),
    ]);

    $inicio = fase6Slot(now());

    // Serviço R$50 < mínimo R$100
    expect(fn () => $this->agenda->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'cliente_id'       => $this->cliente->id,
        'cupom_id'         => $cupom->id,
        'origem'           => 'web',
        'status'           => 'aguardando',
    ]))->toThrow(ValidationException::class);

    $caro = Servico::factory()->create([
        'salao_id' => $this->salao->id,
        'preco'    => 120,
        'duracao'  => 30,
        'ativo'    => true,
    ]);

    $ag = $this->agenda->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$caro->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'cliente_id'       => $this->cliente->id,
        'cupom_id'         => $cupom->id,
        'origem'           => 'web',
        'status'           => 'aguardando',
    ]);
    expect((float) $ag->valor_desconto)->toBe(10.0);

    $segundo = fase6Slot(now(), 1);
    expect(fn () => $this->agenda->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$caro->id],
        'data_hora_inicio' => $segundo->toDateTimeString(),
        'cliente_id'       => $this->cliente->id,
        'cupom_id'         => $cupom->id,
        'origem'           => 'web',
        'status'           => 'aguardando',
    ]))->toThrow(ValidationException::class);
});

test('anti-stacking: cupom promocional não credita pontos de fidelidade', function () {
    $cupom = Cupom::factory()->create([
        'salao_id'                 => $this->salao->id,
        'tipo'                     => 'fixo',
        'valor'                    => 5,
        'anti_stacking_fidelidade' => true,
        'origem'                   => 'manual',
        'ativo'                    => true,
        'validade'                 => now()->addMonth(),
    ]);

    $inicio = fase6Slot(now());
    $ag = $this->agenda->criarAgendamento([
        'salao_id'         => $this->salao->id,
        'manicure_id'      => $this->manicure->id,
        'servico_ids'      => [$this->servico->id],
        'data_hora_inicio' => $inicio->toDateTimeString(),
        'cliente_id'       => $this->cliente->id,
        'cupom_id'         => $cupom->id,
        'origem'           => 'web',
        'status'           => AgendamentoStatus::Concluido->value,
    ]);

    $this->fidelidade->creditarPorAtendimento($ag->fresh(['cliente', 'salao.configuracao']), 45.0);

    $this->cliente->refresh();
    expect($this->cliente->pontos_fidelidade)->toBe(0);
    expect((int) $this->cliente->total_visitas)->toBe(1);
});

test('níveis de fidelidade aplicam multiplicador no crédito', function () {
    config([
        'manicure.fidelidade.niveis' => [
            ['chave' => 'bronze', 'nome' => 'Bronze', 'pontos_min' => 0, 'multiplicador' => 1.0],
            ['chave' => 'prata', 'nome' => 'Prata', 'pontos_min' => 50, 'multiplicador' => 2.0],
        ],
    ]);

    FidelidadePonto::create([
        'cliente_id' => $this->cliente->id,
        'salao_id'   => $this->salao->id,
        'pontos'     => 50,
        'tipo'       => 'ganho',
        'descricao'  => 'seed nível',
    ]);

    $nivel = $this->fidelidade->nivelPara($this->cliente);
    expect($nivel['chave'])->toBe('prata');
    expect($nivel['multiplicador'])->toBe(2.0);

    $ag = Agendamento::factory()->create([
        'salao_id'    => $this->salao->id,
        'manicure_id' => $this->manicure->id,
        'cliente_id'  => $this->cliente->id,
        'status'      => AgendamentoStatus::Concluido->value,
    ]);

    $this->fidelidade->creditarPorAtendimento($ag->fresh(['cliente', 'salao.configuracao']), 10.0);

    $this->cliente->refresh();
    // 10 * 1 * 2.0 = 20
    expect($this->cliente->pontos_fidelidade)->toBe(20);
});

test('expira pontos vencidos e debita saldo', function () {
    config(['manicure.fidelidade.expiracao_dias' => 30]);

    $this->cliente->update(['pontos_fidelidade' => 40]);
    FidelidadePonto::create([
        'cliente_id' => $this->cliente->id,
        'salao_id'   => $this->salao->id,
        'pontos'     => 40,
        'tipo'       => 'ganho',
        'descricao'  => 'antigo',
        'expires_at' => now()->subDay(),
    ]);

    $n = $this->fidelidade->expirarPontosVencidos();
    expect($n)->toBe(1);

    $this->cliente->refresh();
    expect($this->cliente->pontos_fidelidade)->toBe(0);
    expect(FidelidadePonto::where('tipo', 'expirado')->count())->toBe(1);

    // Idempotente
    expect($this->fidelidade->expirarPontosVencidos())->toBe(0);
});
