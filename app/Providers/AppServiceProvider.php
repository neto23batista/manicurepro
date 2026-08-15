<?php

namespace App\Providers;

use App\Contracts\NotaFiscalProvider;
use App\Events\AgendamentoCanceladoEvent;
use App\Events\AgendamentoCriado;
use App\Events\AgendamentoFinalizado;
use App\Events\AgendamentoReagendado;
use App\Events\EstoqueZerado;
use App\Listeners\CancelarPagamentoMercadoPago;
use App\Listeners\InvalidarCacheSlotsAgendamento;
use App\Listeners\NotificarAgendamentoCancelado;
use App\Listeners\NotificarAgendamentoCriado;
use App\Listeners\NotificarAgendamentoReagendado;
use App\Listeners\NotificarEstoqueZerado;
use App\Listeners\NotificarListaEspera;
use App\Listeners\PedirAvaliacaoPosAtendimento;
use App\Listeners\SincronizarCalendarioAgendamento;
use App\Models\User;
use App\Observers\UserObserver;
use App\Services\Fiscal\HttpNotaFiscalProvider;
use App\Services\Fiscal\StubNotaFiscalProvider;
use App\View\Composers\NotificacoesComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StubNotaFiscalProvider::class);

        $this->app->bind(NotaFiscalProvider::class, function ($app) {
            $driver = (string) config('manicure.fiscal.driver', 'stub');

            return $driver === 'http'
                ? $app->make(HttpNotaFiscalProvider::class)
                : $app->make(StubNotaFiscalProvider::class);
        });
    }

    public function boot(): void
    {
        Model::preventLazyLoading(app()->isLocal());
        Paginator::useBootstrapFive();

        // Em produção: força HTTPS na geração de URLs e marca o cookie de sessão como seguro.
        // Mantém o ambiente local (http://...:8000) funcionando sem ajuste de .env.
        if (app()->environment('production')) {
            URL::forceScheme('https');
            Config::set('session.secure', true);
        }

        Blade::directive('money', function ($expression) {
            return "<?php echo 'R$ ' . number_format({$expression}, 2, ',', '.'); ?>";
        });

        // Injeta notificações do topbar em todas as views que estendem o layout principal
        View::composer('layouts.app', NotificacoesComposer::class);

        // Event listeners para agendamentos
        Event::listen(AgendamentoCriado::class, NotificarAgendamentoCriado::class);
        Event::listen(AgendamentoCriado::class, SincronizarCalendarioAgendamento::class);
        Event::listen(AgendamentoFinalizado::class, PedirAvaliacaoPosAtendimento::class);
        Event::listen(AgendamentoCanceladoEvent::class, NotificarAgendamentoCancelado::class);
        Event::listen(AgendamentoCanceladoEvent::class, NotificarListaEspera::class);
        Event::listen(AgendamentoCanceladoEvent::class, InvalidarCacheSlotsAgendamento::class);
        Event::listen(AgendamentoCanceladoEvent::class, CancelarPagamentoMercadoPago::class);
        Event::listen(AgendamentoReagendado::class, NotificarAgendamentoReagendado::class);
        Event::listen(AgendamentoReagendado::class, SincronizarCalendarioAgendamento::class);
        Event::listen(EstoqueZerado::class, NotificarEstoqueZerado::class);

        // Mantém Manicure/Cliente sincronizados com o User
        User::observe(UserObserver::class);
    }
}
