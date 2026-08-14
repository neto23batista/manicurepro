<?php

/**
 * Sentry (opcional). O pacote sentry/sentry-laravel lê estas chaves.
 * Sem SENTRY_LARAVEL_DSN o SDK não envia eventos.
 *
 * @see https://docs.sentry.io/platforms/php/guides/laravel/
 */
return [

    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // Capture release as git sha if available
    'release' => env('SENTRY_RELEASE'),

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    // Sample rate for performance monitoring (0.0 – 1.0)
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.0),

    'send_default_pii' => false,

];
