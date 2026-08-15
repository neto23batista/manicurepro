<?php

use App\Providers\AppServiceProvider;
use App\Providers\SecurityHardeningServiceProvider;
use Barryvdh\DomPDF\ServiceProvider;

return [
    AppServiceProvider::class,
    SecurityHardeningServiceProvider::class,
    ServiceProvider::class,
];
