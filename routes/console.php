<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('manicure:enviar-lembretes 24h')->dailyAt('08:00');
Schedule::command('manicure:enviar-lembretes 2h')->hourly();
Schedule::command('manicure:limpar-expirados')->dailyAt('03:00');
Schedule::command('manicure:enviar-aniversarios')->dailyAt('09:00');
