<?php

namespace App\Providers;

use App\Http\Middleware\ProtectPublicForms;
use App\Models\Agendamento;
use App\Models\User;
use App\Observers\AgendamentoAuditObserver;
use App\Observers\UserRoleAuditObserver;
use Illuminate\Support\ServiceProvider;

class SecurityHardeningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Prefer path/route matching middleware over editing contested routes/web.php
        $this->app['router']->pushMiddlewareToGroup('web', ProtectPublicForms::class);

        Agendamento::observe(AgendamentoAuditObserver::class);
        User::observe(UserRoleAuditObserver::class);
    }
}
