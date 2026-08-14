<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// withoutOverlapping: evita corrida se um run atrasar e o cron disparar de novo.
Schedule::command('manicure:enviar-lembretes 24h')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::command('manicure:enviar-lembretes 2h')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('manicure:limpar-expirados')
    ->dailyAt('03:00')
    ->withoutOverlapping();

Schedule::command('manicure:enviar-aniversarios')
    ->dailyAt('09:00')
    ->withoutOverlapping();

Schedule::command('manicure:expirar-pontos-fidelidade')
    ->dailyAt('03:30')
    ->withoutOverlapping();

Schedule::command('manicure:reativar-inativos')
    ->weeklyOn(1, '10:00')
    ->withoutOverlapping();

Schedule::command('manicure:sugerir-retorno')
    ->dailyAt('10:30')
    ->withoutOverlapping();

Schedule::command('manicure:backup --keep=14')
    ->dailyAt('02:30')
    ->withoutOverlapping();
