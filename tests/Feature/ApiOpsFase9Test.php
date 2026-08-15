<?php

use App\Models\Agendamento;
use App\Models\Cliente;
use App\Models\ConfiguracaoSalao;
use App\Models\FidelidadePonto;
use App\Models\Manicure;
use App\Models\Salao;
use App\Models\User;
use App\Services\WebPushService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function apiTokenFor(User $user): string
{
    return $user->createToken('t')->plainTextToken;
}

test('api erros de validação usam shape message+code+errors', function () {
    $this->postJson('/api/v1/login', [])
        ->assertStatus(422)
        ->assertJsonPath('code', 'validation_error')
        ->assertJsonStructure(['message', 'code', 'errors']);
});

test('api 401 não autenticado usa code unauthenticated', function () {
    $this->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertJsonPath('code', 'unauthenticated')
        ->assertJsonPath('message', 'Não autenticado.');
});

test('api 404 de modelo usa code not_found', function () {
    $user = User::factory()->create(['role' => 'cliente', 'ativo' => true]);

    $this->withHeader('Authorization', 'Bearer '.apiTokenFor($user))
        ->getJson('/api/v1/agendamentos/999999')
        ->assertNotFound()
        ->assertJsonPath('code', 'not_found')
        ->assertJsonPath('message', 'Recurso não encontrado.');
});

test('GET me/fidelidade retorna pontos e historico do cliente', function () {
    $salao = Salao::factory()->create(['ativo' => true]);
    ConfiguracaoSalao::create([
        'salao_id'              => $salao->id,
        'fidelidade_ativo'      => true,
        'pontos_para_desconto'  => 100,
        'valor_desconto_pontos' => 10,
    ]);

    $user = User::factory()->create(['role' => 'cliente', 'ativo' => true, 'salao_id' => $salao->id]);
    $cliente = Cliente::factory()->create([
        'salao_id'          => $salao->id,
        'user_id'           => $user->id,
        'pontos_fidelidade' => 150,
    ]);

    FidelidadePonto::create([
        'cliente_id'     => $cliente->id,
        'salao_id'       => $salao->id,
        'agendamento_id' => null,
        'pontos'         => 50,
        'tipo'           => 'ganho',
        'descricao'      => 'Teste',
    ]);

    $this->withHeader('Authorization', 'Bearer '.apiTokenFor($user))
        ->getJson('/api/v1/me/fidelidade')
        ->assertOk()
        ->assertJsonPath('fidelidade.pontos', 150)
        ->assertJsonPath('fidelidade.pode_resgatar', true)
        ->assertJsonPath('fidelidade.blocos_disponiveis', 1)
        ->assertJsonPath('fidelidade.ativo', true)
        ->assertJsonCount(1, 'fidelidade.historico');
});

test('GET me/fidelidade sem cliente retorna 404 padronizado', function () {
    $user = User::factory()->create(['role' => 'cliente', 'ativo' => true]);

    $this->withHeader('Authorization', 'Bearer '.apiTokenFor($user))
        ->getJson('/api/v1/me/fidelidade')
        ->assertNotFound()
        ->assertJsonPath('code', 'cliente_nao_encontrado');
});

test('GET agendamentos api aceita filtros status de ate e per_page', function () {
    $salao = Salao::factory()->create(['ativo' => true]);
    $manicure = Manicure::factory()->create(['salao_id' => $salao->id, 'ativo' => true]);
    $user = User::factory()->create(['role' => 'cliente', 'ativo' => true]);
    $cliente = Cliente::factory()->create([
        'salao_id' => $salao->id,
        'user_id'  => $user->id,
    ]);

    $inicioA = Carbon::now()->addDays(2)->setTime(10, 0);
    $inicioB = Carbon::now()->addDays(10)->setTime(11, 0);

    $agA = Agendamento::factory()->create([
        'salao_id'         => $salao->id,
        'manicure_id'      => $manicure->id,
        'cliente_id'       => $cliente->id,
        'user_id'          => $user->id,
        'data_hora_inicio' => $inicioA,
        'data_hora_fim'    => $inicioA->copy()->addMinutes(30),
        'status'           => 'confirmado',
        'nome_cliente'     => $user->name,
    ]);

    Agendamento::factory()->create([
        'salao_id'         => $salao->id,
        'manicure_id'      => $manicure->id,
        'cliente_id'       => $cliente->id,
        'user_id'          => $user->id,
        'data_hora_inicio' => $inicioB,
        'data_hora_fim'    => $inicioB->copy()->addMinutes(30),
        'status'           => 'aguardando',
        'nome_cliente'     => $user->name,
    ]);

    $token = apiTokenFor($user);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/agendamentos?status=confirmado&per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $agA->id)
        ->assertJsonPath('meta.per_page', 1);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/agendamentos?de='.$inicioA->toDateString().'&ate='.$inicioA->toDateString())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $agA->id);
});

test('admin saude exibe checks e bloqueia nao-admin', function () {
    $admin = User::factory()->create(['role' => 'admin', 'ativo' => true]);
    $dono = User::factory()->create(['role' => 'dono', 'ativo' => true]);

    $this->actingAs($dono)->get(route('admin.saude'))->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.saude'))
        ->assertOk()
        ->assertSee('Saúde do sistema')
        ->assertSee('Banco de dados')
        ->assertSee('Cache')
        ->assertSee('Fila');
});

test('webpush envioDisponivel fica false sem subscribe_ui / minishlink', function () {
    config([
        'manicure.webpush.subscribe_ui'      => false,
        'manicure.webpush.vapid.public_key'  => 'pk',
        'manicure.webpush.vapid.private_key' => 'sk',
    ]);

    $svc = app(WebPushService::class);
    expect($svc->configurado())->toBeTrue()
        ->and($svc->envioDisponivel())->toBeFalse()
        ->and($svc->sendToUser(User::factory()->create(), 't', 'b'))->toBe(0);
});
