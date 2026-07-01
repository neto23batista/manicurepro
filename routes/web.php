<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\SalaoController;
use App\Http\Controllers\Admin\ManicureController as AdminManicure;
use App\Http\Controllers\Admin\ServicoController;
use App\Http\Controllers\Admin\RelatorioController as AdminRelatorio;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Dono\DashboardController as DonoDashboard;
use App\Http\Controllers\Dono\AgendamentoController as DonoAgendamento;
use App\Http\Controllers\Dono\ClienteController as DonoCliente;
use App\Http\Controllers\Dono\ConfiguracaoController as DonoConfig;
use App\Http\Controllers\Dono\CupomController as DonoCupom;
use App\Http\Controllers\Dono\FolgaController as DonoFolga;
use App\Http\Controllers\Dono\ProdutoController as DonoProduto;
use App\Http\Controllers\Dono\GaleriaController as DonoGaleria;
use App\Http\Controllers\Dono\FinanceiroController as DonoFinanceiro;
use App\Http\Controllers\Dono\ValePresenteController as DonoVale;
use App\Http\Controllers\Manicure\DashboardController as ManicureDashboard;
use App\Http\Controllers\Manicure\AgendaController;
use App\Http\Controllers\Manicure\FolgaController as ManicureFolga;
use App\Http\Controllers\Cliente\DashboardController as ClienteDashboard;
use App\Http\Controllers\Cliente\AgendamentoController as ClienteAgendamento;
use App\Http\Controllers\Cliente\ListaEsperaController as ClienteListaEspera;
use App\Http\Controllers\Cliente\FidelidadeController as ClienteFidelidade;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

// ========================
// ROTAS PÚBLICAS
// ========================
Route::get('/', [PublicController::class, 'index'])->name('public.index');
Route::get('/salao/{salao:slug}', [PublicController::class, 'salao'])
    ->where('salao', '[a-zA-Z0-9-]+')
    ->name('public.salao');
Route::get('/salao/{salao:slug}/agendar', [PublicController::class, 'agendar'])
    ->where('salao', '[a-zA-Z0-9-]+')
    ->name('public.agendar');
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/api/slots', [PublicController::class, 'getSlots'])->name('public.slots');
    Route::get('/api/datas-disponiveis', [PublicController::class, 'getDatasDisponiveis'])->name('public.datas');
    // Reserva temporária é mais sensível a abuso (bloqueia slots) → limite menor
    Route::post('/api/slots/hold', [PublicController::class, 'holdSlot'])
        ->middleware('throttle:20,1')->name('public.slots.hold');
});

// Confirmação de presença via link assinado (e-mail/WhatsApp), sem login
Route::get('/agendamento/{agendamento}/confirmar', [\App\Http\Controllers\ConfirmacaoController::class, 'confirmar'])
    ->name('agendamento.confirmar')
    ->middleware('signed');

// Webhook Mercado Pago (sem CSRF — ver exceção em bootstrap/app.php)
Route::post('/webhooks/mercadopago', [\App\Http\Controllers\MercadoPagoWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('webhooks.mercadopago');

// ========================
// AUTENTICAÇÃO
// ========================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

    // Recuperação de senha
    Route::get('/password/forgot', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email',  [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset',  [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Desafio 2FA (usuário ainda não autenticado — sessão pendente)
Route::get('/2fa/challenge', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
Route::post('/2fa/challenge', [TwoFactorController::class, 'challengeVerify'])
    ->name('2fa.challenge.verify')->middleware('throttle:6,1');

// Verificação de e-mail
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [VerifyEmailController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [VerifyEmailController::class, 'send'])
        ->middleware('throttle:6,1')->name('verification.send');
});

// ========================
// PERFIL (todos autenticados)
// ========================
Route::middleware('auth')->group(function () {
    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::delete('/perfil/avatar', [PerfilController::class, 'destroyAvatar'])->name('perfil.avatar.destroy');
    // LGPD
    Route::get('/perfil/exportar-dados', [PerfilController::class, 'exportarDados'])->name('perfil.exportar');
    Route::delete('/perfil/conta', [PerfilController::class, 'excluirConta'])->name('perfil.conta.destroy');
    // 2FA (configuração)
    Route::get('/perfil/2fa', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/perfil/2fa', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::delete('/perfil/2fa', [TwoFactorController::class, 'disable'])->name('2fa.disable');
});

// ========================
// ADMIN
// ========================
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

    // Single-tenant: não se cria nem exclui salão — apenas visualiza/edita o único.
    Route::resource('saloes', SalaoController::class)
        ->only(['index', 'show', 'edit', 'update'])
        ->parameters(['saloes' => 'salao']);

    Route::resource('manicures', AdminManicure::class)->except(['show', 'destroy']);
    Route::delete('manicures/{manicure}', [AdminManicure::class, 'destroy'])->name('manicures.destroy');

    Route::resource('servicos', ServicoController::class);

    // Categorias
    Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
    Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
    Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
    Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');

    Route::get('/relatorios', [AdminRelatorio::class, 'index'])->name('relatorios.index');
    Route::get('/relatorios/pdf', [AdminRelatorio::class, 'exportPdf'])->name('relatorios.pdf');

    Route::resource('usuarios', UsuarioController::class);
});

// ========================
// DONO / ATENDENTE
// ========================
Route::prefix('dono')->name('dono.')->middleware(['auth', 'role:dono,atendente', 'check.salao'])->group(function () {
    Route::get('/dashboard', [DonoDashboard::class, 'index'])->name('dashboard');

    Route::resource('agendamentos', DonoAgendamento::class)->except(['edit', 'update']);
    Route::patch('agendamentos/{agendamento}/status', [DonoAgendamento::class, 'updateStatus'])->name('agendamentos.status');
    Route::get('agendamentos/{agendamento}/reagendar', [DonoAgendamento::class, 'reagendarForm'])->name('agendamentos.reagendar.form');
    Route::post('agendamentos/{agendamento}/reagendar', [DonoAgendamento::class, 'reagendar'])->name('agendamentos.reagendar');
    Route::delete('agendamentos/{agendamento}', [DonoAgendamento::class, 'destroy'])->name('agendamentos.destroy');
    // Venda de produtos na comanda do atendimento
    Route::post('agendamentos/{agendamento}/produto', [DonoAgendamento::class, 'venderProduto'])->name('agendamentos.produto');
    Route::delete('agendamentos/{agendamento}/item/{item}', [DonoAgendamento::class, 'removerItem'])->name('agendamentos.item.remover');
    Route::post('agendamentos/{agendamento}/vale', [DonoAgendamento::class, 'aplicarVale'])->name('agendamentos.vale');

    // Clientes
    Route::resource('clientes', DonoCliente::class);

    // Cupons
    Route::resource('cupons', DonoCupom::class)->except(['show'])
        ->parameters(['cupons' => 'cupom']);

    // Produtos / Estoque
    Route::resource('produtos', DonoProduto::class)->except(['show']);
    Route::post('produtos/{produto}/estoque', [DonoProduto::class, 'movimentar'])->name('produtos.estoque');

    // Galeria / portfólio de trabalhos
    Route::resource('galeria', DonoGaleria::class)->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['galeria' => 'foto']);
    Route::patch('galeria/{foto}/publicar', [DonoGaleria::class, 'togglePublicar'])->name('galeria.publicar');

    // Caixa / financeiro + comissões
    Route::get('/financeiro', [DonoFinanceiro::class, 'index'])->name('financeiro.index');

    // Vales-presente (gift cards)
    Route::get('vales', [DonoVale::class, 'index'])->name('vales.index');
    Route::post('vales', [DonoVale::class, 'store'])->name('vales.store');
    Route::get('vales/{vale}', [DonoVale::class, 'show'])->name('vales.show');
    Route::delete('vales/{vale}', [DonoVale::class, 'cancelar'])->name('vales.cancelar');

    // Folgas
    Route::get('/folgas', [DonoFolga::class, 'index'])->name('folgas.index');
    Route::post('/folgas', [DonoFolga::class, 'store'])->name('folgas.store');
    Route::delete('/folgas/{folga}', [DonoFolga::class, 'destroy'])->name('folgas.destroy');

    // Configurações
    Route::get('/configuracao',          [DonoConfig::class, 'edit'])->name('config.edit');
    Route::put('/configuracao/dados',    [DonoConfig::class, 'updateDados'])->name('config.dados');
    Route::put('/configuracao/horarios', [DonoConfig::class, 'updateHorarios'])->name('config.horarios');
    Route::put('/configuracao/config',   [DonoConfig::class, 'updateConfig'])->name('config.config');
    Route::delete('/configuracao/logo',  [DonoConfig::class, 'destroyLogo'])->name('config.logo.destroy');
    Route::delete('/configuracao/capa',  [DonoConfig::class, 'destroyCapa'])->name('config.capa.destroy');
});

// ========================
// MANICURE
// ========================
Route::prefix('manicure')->name('manicure.')->middleware(['auth', 'role:manicure', 'check.salao'])->group(function () {
    Route::get('/dashboard', [ManicureDashboard::class, 'index'])->name('dashboard');

    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
    Route::get('/agenda/{agendamento}', [AgendaController::class, 'show'])->name('agenda.show');
    Route::patch('/agenda/{agendamento}/status', [AgendaController::class, 'updateStatus'])->name('agenda.status');

    // Folgas próprias
    Route::get('/folgas', [ManicureFolga::class, 'index'])->name('folgas.index');
    Route::post('/folgas', [ManicureFolga::class, 'store'])->name('folgas.store');
    Route::delete('/folgas/{folga}', [ManicureFolga::class, 'destroy'])->name('folgas.destroy');
});

// ========================
// CLIENTE
// ========================
Route::prefix('cliente')->name('cliente.')->middleware(['auth', 'role:cliente'])->group(function () {
    Route::get('/dashboard', [ClienteDashboard::class, 'index'])->name('dashboard');

    Route::get('/agendamentos', [ClienteAgendamento::class, 'index'])->name('agendamentos.index');
    Route::get('/agendamentos/novo', [ClienteAgendamento::class, 'create'])->name('agendamentos.create');
    Route::post('/agendamentos', [ClienteAgendamento::class, 'store'])->name('agendamentos.store');
    Route::get('/agendamentos/{agendamento}', [ClienteAgendamento::class, 'show'])->name('agendamentos.show');
    Route::get('/agendamentos/{agendamento}/ical', [ClienteAgendamento::class, 'ical'])->name('agendamentos.ical');
    Route::get('/agendamentos/{agendamento}/reagendar', [ClienteAgendamento::class, 'reagendarForm'])->name('agendamentos.reagendar.form');
    Route::post('/agendamentos/{agendamento}/reagendar', [ClienteAgendamento::class, 'reagendar'])->name('agendamentos.reagendar');
    Route::post('/agendamentos/{agendamento}/cancelar', [ClienteAgendamento::class, 'cancelar'])->name('agendamentos.cancelar');
    Route::post('/agendamentos/{agendamento}/avaliar', [ClienteAgendamento::class, 'avaliar'])->name('agendamentos.avaliar');
    Route::get('/agendamentos/{agendamento}/sinal', [ClienteAgendamento::class, 'sinal'])->name('agendamentos.sinal');
    Route::post('/agendamentos/{agendamento}/sinal/status', [ClienteAgendamento::class, 'sinalStatus'])
        ->middleware('throttle:30,1')->name('agendamentos.sinal.status');

    // Lista de espera
    Route::get('/lista-espera', [ClienteListaEspera::class, 'index'])->name('lista-espera.index');
    Route::post('/lista-espera', [ClienteListaEspera::class, 'store'])->name('lista-espera.store');
    Route::delete('/lista-espera/{lista}', [ClienteListaEspera::class, 'destroy'])->name('lista-espera.destroy');

    // Fidelidade
    Route::get('/fidelidade', [ClienteFidelidade::class, 'index'])->name('fidelidade.index');
    Route::post('/fidelidade/resgatar', [ClienteFidelidade::class, 'resgatar'])->name('fidelidade.resgatar');
});
