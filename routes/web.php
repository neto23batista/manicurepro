<?php

use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\ManicureController as AdminManicure;
use App\Http\Controllers\Admin\RelatorioController as AdminRelatorio;
use App\Http\Controllers\Admin\SalaoController;
use App\Http\Controllers\Admin\SaudeController as AdminSaude;
use App\Http\Controllers\Admin\ServicoController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Cliente\AgendamentoController as ClienteAgendamento;
use App\Http\Controllers\Cliente\DashboardController as ClienteDashboard;
use App\Http\Controllers\Cliente\FidelidadeController as ClienteFidelidade;
use App\Http\Controllers\Cliente\ListaEsperaController as ClienteListaEspera;
use App\Http\Controllers\ConfirmacaoController;
use App\Http\Controllers\Dono\AgendamentoController as DonoAgendamento;
use App\Http\Controllers\Dono\AuditoriaController as DonoAuditoria;
use App\Http\Controllers\Dono\AvaliacaoController as DonoAvaliacao;
use App\Http\Controllers\Dono\CaixaController as DonoCaixa;
use App\Http\Controllers\Dono\ClienteController as DonoCliente;
use App\Http\Controllers\Dono\ConfiguracaoController as DonoConfig;
use App\Http\Controllers\Dono\CupomController as DonoCupom;
use App\Http\Controllers\Dono\DashboardController as DonoDashboard;
use App\Http\Controllers\Dono\DespesaController as DonoDespesa;
use App\Http\Controllers\Dono\DisponibilidadeController as DonoDisponibilidade;
use App\Http\Controllers\Dono\EstoqueRelatorioController as DonoEstoqueRelatorio;
use App\Http\Controllers\Dono\FinanceiroController as DonoFinanceiro;
use App\Http\Controllers\Dono\FolgaController as DonoFolga;
use App\Http\Controllers\Dono\FornecedorController as DonoFornecedor;
use App\Http\Controllers\Dono\GaleriaController as DonoGaleria;
use App\Http\Controllers\Dono\InventarioController as DonoInventario;
use App\Http\Controllers\Dono\NotaFiscalController as DonoNotaFiscal;
use App\Http\Controllers\Dono\OnboardingController as DonoOnboarding;
use App\Http\Controllers\Dono\PacoteController as DonoPacote;
use App\Http\Controllers\Dono\ProdutoController as DonoProduto;
use App\Http\Controllers\Dono\ValePresenteController as DonoVale;
use App\Http\Controllers\Manicure\AgendaController;
use App\Http\Controllers\Manicure\DashboardController as ManicureDashboard;
use App\Http\Controllers\Manicure\DisponibilidadeController as ManicureDisponibilidade;
use App\Http\Controllers\Manicure\FolgaController as ManicureFolga;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\PushSubscriptionController;
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
Route::post('/salao/{salao:slug}/agendar', [PublicController::class, 'storeAgendamento'])
    ->where('salao', '[a-zA-Z0-9-]+')
    ->middleware('throttle:8,1')
    ->name('public.agendar.store');
Route::get('/salao/{salao:slug}/agendar/sucesso', [PublicController::class, 'agendamentoSucesso'])
    ->where('salao', '[a-zA-Z0-9-]+')
    ->name('public.agendar.sucesso');
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/api/slots', [PublicController::class, 'getSlots'])->name('public.slots');
    Route::get('/api/datas-disponiveis', [PublicController::class, 'getDatasDisponiveis'])->name('public.datas');
    // Reserva temporária é mais sensível a abuso (bloqueia slots) → limite menor
    Route::post('/api/slots/hold', [PublicController::class, 'holdSlot'])
        ->middleware('throttle:20,1')->name('public.slots.hold');
});

// Confirmação de presença via link assinado (e-mail/WhatsApp), sem login
Route::get('/agendamento/{agendamento}/confirmar', [ConfirmacaoController::class, 'confirmar'])
    ->name('agendamento.confirmar')
    ->middleware('signed');

// Webhook Mercado Pago (sem CSRF — ver exceção em bootstrap/app.php)
Route::post('/webhooks/mercadopago', [MercadoPagoWebhookController::class, 'handle'])
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
    Route::post('/password/email', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.update');
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

    // Web Push — subscription do service worker (opcional; exige VAPID no .env)
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('push-subscriptions.destroy');
});

// ========================
// ADMIN
// ========================
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'role:admin'])->group(function () {
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
    Route::get('/relatorios/csv', [AdminRelatorio::class, 'exportCsv'])->name('relatorios.csv');

    Route::get('/saude', [AdminSaude::class, 'index'])->name('saude');

    Route::resource('usuarios', UsuarioController::class);
});

// ========================
// DONO / ATENDENTE (operação)
// ========================
Route::prefix('dono')->name('dono.')->middleware(['auth', 'verified', 'role:dono,atendente', 'check.salao'])->group(function () {
    Route::get('/dashboard', [DonoDashboard::class, 'index'])->name('dashboard');

    Route::resource('agendamentos', DonoAgendamento::class)->except(['edit', 'update']);
    Route::get('agendamentos-semana', [DonoAgendamento::class, 'semana'])->name('agendamentos.semana');
    Route::get('agendamentos-ical', [DonoAgendamento::class, 'ical'])->name('agendamentos.ical');
    Route::patch('agendamentos/{agendamento}/status', [DonoAgendamento::class, 'updateStatus'])->name('agendamentos.status');
    Route::get('agendamentos/{agendamento}/reagendar', [DonoAgendamento::class, 'reagendarForm'])->name('agendamentos.reagendar.form');
    Route::post('agendamentos/{agendamento}/reagendar', [DonoAgendamento::class, 'reagendar'])->name('agendamentos.reagendar');
    Route::delete('agendamentos/{agendamento}', [DonoAgendamento::class, 'destroy'])->name('agendamentos.destroy');
    // Venda de produtos na comanda do atendimento
    Route::post('agendamentos/{agendamento}/produto', [DonoAgendamento::class, 'venderProduto'])->name('agendamentos.produto');
    Route::delete('agendamentos/{agendamento}/item/{item}', [DonoAgendamento::class, 'removerItem'])->name('agendamentos.item.remover');
    Route::post('agendamentos/{agendamento}/vale', [DonoAgendamento::class, 'aplicarVale'])->name('agendamentos.vale');

    // Clientes (CRM: filtros de segmento + cupom reativação)
    Route::resource('clientes', DonoCliente::class);
    Route::post('clientes/{cliente}/reativar', [DonoCliente::class, 'reativar'])
        ->name('clientes.reativar');

    // Cupons
    Route::resource('cupons', DonoCupom::class)->except(['show'])
        ->parameters(['cupons' => 'cupom']);

    // Pacotes / combos vendáveis
    Route::resource('pacotes', DonoPacote::class)->except(['show']);
    Route::post('pacotes/{pacote}/atribuir', [DonoPacote::class, 'atribuir'])->name('pacotes.atribuir');

    // Produtos / Estoque
    Route::resource('produtos', DonoProduto::class)->except(['show']);
    Route::post('produtos/{produto}/estoque', [DonoProduto::class, 'movimentar'])->name('produtos.estoque');
    Route::resource('fornecedores', DonoFornecedor::class)->except(['show']);
    Route::get('estoque/inventario', [DonoInventario::class, 'create'])->name('estoque.inventario.create');
    Route::post('estoque/inventario', [DonoInventario::class, 'store'])->name('estoque.inventario.store');
    Route::get('estoque/relatorio', [DonoEstoqueRelatorio::class, 'index'])->name('estoque.relatorio');
    Route::get('estoque/relatorio/csv', [DonoEstoqueRelatorio::class, 'csv'])->name('estoque.relatorio.csv');

    // Galeria / portfólio de trabalhos
    Route::resource('galeria', DonoGaleria::class)->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['galeria' => 'foto']);
    Route::patch('galeria/{foto}/publicar', [DonoGaleria::class, 'togglePublicar'])->name('galeria.publicar');

    Route::get('avaliacoes', [DonoAvaliacao::class, 'index'])->name('avaliacoes.index');
    Route::patch('avaliacoes/{avaliacao}/publicar', [DonoAvaliacao::class, 'togglePublicar'])->name('avaliacoes.publicar');

    // Folgas + feriados recorrentes anuais
    Route::get('/folgas', [DonoFolga::class, 'index'])->name('folgas.index');
    Route::post('/folgas', [DonoFolga::class, 'store'])->name('folgas.store');
    Route::delete('/folgas/{folga}', [DonoFolga::class, 'destroy'])->name('folgas.destroy');
    Route::post('/feriados', [DonoFolga::class, 'storeFeriado'])->name('feriados.store');
    Route::delete('/feriados/{feriado}', [DonoFolga::class, 'destroyFeriado'])->name('feriados.destroy');

    // Disponibilidade / pausas das manicures
    Route::get('/disponibilidades', [DonoDisponibilidade::class, 'index'])->name('disponibilidades.index');
    Route::put('/disponibilidades/{manicure}', [DonoDisponibilidade::class, 'update'])->name('disponibilidades.update');
});

// Áreas sensíveis: default = role dono; grants extras via role_or_perm (JSON).
// Sem grants, atendente continua 403 (AtendenteAcessoTest).
// role_or_perm ANTES de check.salao → cliente/manicure recebem 403 (não 302 de salão).
Route::prefix('dono')->name('dono.')->middleware(['auth', 'verified'])->group(function () {
    Route::middleware(['role_or_perm:dono,financeiro.view', 'check.salao'])->group(function () {
        Route::get('/financeiro', [DonoFinanceiro::class, 'index'])->name('financeiro.index');
        Route::post('/financeiro/comissoes', [DonoFinanceiro::class, 'storePagamento'])->name('financeiro.comissoes.store');
        Route::delete('/financeiro/comissoes/{pagamento}', [DonoFinanceiro::class, 'destroyPagamento'])->name('financeiro.comissoes.destroy');
        Route::post('/financeiro/comissoes/ajustes', [DonoFinanceiro::class, 'storeAjuste'])->name('financeiro.comissoes.ajustes.store');
        Route::delete('/financeiro/comissoes/ajustes/{ajuste}', [DonoFinanceiro::class, 'destroyAjuste'])->name('financeiro.comissoes.ajustes.destroy');
        Route::post('agendamentos/{agendamento}/estorno-pix', [DonoAgendamento::class, 'estornarPix'])
            ->name('agendamentos.estorno-pix');
    });

    Route::middleware(['role_or_perm:dono,financeiro.caixa', 'check.salao'])->group(function () {
        Route::get('/financeiro/caixa', [DonoCaixa::class, 'index'])->name('financeiro.caixa.index');
        Route::post('/financeiro/caixa/abrir', [DonoCaixa::class, 'abrir'])->name('financeiro.caixa.abrir');
        Route::get('/financeiro/caixa/{caixa}', [DonoCaixa::class, 'show'])->name('financeiro.caixa.show');
        Route::post('/financeiro/caixa/{caixa}/movimentar', [DonoCaixa::class, 'movimentar'])->name('financeiro.caixa.movimentar');
        Route::post('/financeiro/caixa/{caixa}/fechar', [DonoCaixa::class, 'fechar'])->name('financeiro.caixa.fechar');
    });

    Route::middleware(['role_or_perm:dono,financeiro.despesas', 'check.salao'])->group(function () {
        Route::get('/financeiro/despesas', [DonoDespesa::class, 'index'])->name('financeiro.despesas.index');
        Route::post('/financeiro/despesas', [DonoDespesa::class, 'store'])->name('financeiro.despesas.store');
        Route::put('/financeiro/despesas/{despesa}', [DonoDespesa::class, 'update'])->name('financeiro.despesas.update');
        Route::post('/financeiro/despesas/{despesa}/pagar', [DonoDespesa::class, 'marcarPaga'])->name('financeiro.despesas.pagar');
        Route::delete('/financeiro/despesas/{despesa}', [DonoDespesa::class, 'destroy'])->name('financeiro.despesas.destroy');
    });

    // Stub NF-e — NÃO emite SEFAZ (gate: manicure.fiscal.enabled)
    Route::middleware(['role_or_perm:dono,notas_fiscais.manage', 'check.salao'])->group(function () {
        Route::get('notas-fiscais', [DonoNotaFiscal::class, 'index'])->name('notas-fiscais.index');
        Route::post('notas-fiscais', [DonoNotaFiscal::class, 'store'])->name('notas-fiscais.store');
        Route::get('notas-fiscais/{notaFiscal}', [DonoNotaFiscal::class, 'show'])->name('notas-fiscais.show');
    });

    Route::middleware(['role_or_perm:dono,vales.manage', 'check.salao'])->group(function () {
        Route::get('vales', [DonoVale::class, 'index'])->name('vales.index');
        Route::post('vales', [DonoVale::class, 'store'])->name('vales.store');
        Route::get('vales/{vale}', [DonoVale::class, 'show'])->name('vales.show');
        Route::delete('vales/{vale}', [DonoVale::class, 'cancelar'])->name('vales.cancelar');
    });

    Route::middleware(['role_or_perm:dono,config.manage', 'check.salao'])->group(function () {
        Route::get('/configuracao', [DonoConfig::class, 'edit'])->name('config.edit');
        Route::put('/configuracao/dados', [DonoConfig::class, 'updateDados'])->name('config.dados');
        Route::put('/configuracao/horarios', [DonoConfig::class, 'updateHorarios'])->name('config.horarios');
        Route::put('/configuracao/config', [DonoConfig::class, 'updateConfig'])->name('config.config');
        Route::put('/configuracao/permissoes', [DonoConfig::class, 'updatePermissoes'])->name('config.permissoes');
        Route::delete('/configuracao/logo', [DonoConfig::class, 'destroyLogo'])->name('config.logo.destroy');
        Route::delete('/configuracao/capa', [DonoConfig::class, 'destroyCapa'])->name('config.capa.destroy');
    });

    Route::middleware(['role_or_perm:dono,auditoria.view', 'check.salao'])->group(function () {
        Route::get('/auditoria', [DonoAuditoria::class, 'index'])->name('auditoria.index');
    });

    // Onboarding — só dono/admin (sem grant para atendente)
    Route::middleware(['role:dono', 'check.salao'])->group(function () {
        Route::get('/onboarding', [DonoOnboarding::class, 'show'])->name('onboarding.show');
        Route::post('/onboarding/complete', [DonoOnboarding::class, 'complete'])->name('onboarding.complete');
        Route::post('/onboarding/dismiss', [DonoOnboarding::class, 'dismiss'])->name('onboarding.dismiss');
    });
});

// ========================
// MANICURE
// ========================
Route::prefix('manicure')->name('manicure.')->middleware(['auth', 'verified', 'role:manicure', 'check.salao'])->group(function () {
    Route::get('/dashboard', [ManicureDashboard::class, 'index'])->name('dashboard');

    Route::get('/agenda', [AgendaController::class, 'index'])->name('agenda.index');
    Route::get('/agenda/ical', [AgendaController::class, 'ical'])->name('agenda.ical');
    Route::get('/agenda/{agendamento}', [AgendaController::class, 'show'])->name('agenda.show');
    Route::patch('/agenda/{agendamento}/status', [AgendaController::class, 'updateStatus'])->name('agenda.status');
    Route::patch('/agenda/{agendamento}/ficha', [AgendaController::class, 'updateFicha'])->name('agenda.ficha');

    // Folgas próprias
    Route::get('/folgas', [ManicureFolga::class, 'index'])->name('folgas.index');
    Route::post('/folgas', [ManicureFolga::class, 'store'])->name('folgas.store');
    Route::delete('/folgas/{folga}', [ManicureFolga::class, 'destroy'])->name('folgas.destroy');

    // Disponibilidade / pausa (almoço)
    Route::get('/disponibilidade', [ManicureDisponibilidade::class, 'edit'])->name('disponibilidade.edit');
    Route::put('/disponibilidade', [ManicureDisponibilidade::class, 'update'])->name('disponibilidade.update');
});

// ========================
// CLIENTE
// ========================
Route::prefix('cliente')->name('cliente.')->middleware(['auth', 'verified', 'role:cliente'])->group(function () {
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
    Route::get('/agendamentos/{agendamento}/pagamento', [ClienteAgendamento::class, 'pagamento'])->name('agendamentos.pagamento');
    Route::post('/agendamentos/{agendamento}/pagamento/status', [ClienteAgendamento::class, 'pagamentoStatus'])
        ->middleware('throttle:30,1')->name('agendamentos.pagamento.status');

    // Lista de espera
    Route::get('/lista-espera', [ClienteListaEspera::class, 'index'])->name('lista-espera.index');
    Route::post('/lista-espera', [ClienteListaEspera::class, 'store'])->name('lista-espera.store');
    Route::delete('/lista-espera/{lista}', [ClienteListaEspera::class, 'destroy'])->name('lista-espera.destroy');

    // Fidelidade
    Route::get('/fidelidade', [ClienteFidelidade::class, 'index'])->name('fidelidade.index');
    Route::post('/fidelidade/resgatar', [ClienteFidelidade::class, 'resgatar'])->name('fidelidade.resgatar');
});
