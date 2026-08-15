<?php

use App\Http\Controllers\Api\AgendamentoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FidelidadeController;
use App\Http\Controllers\Api\SalaoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ========================
    // Públicas (60 req/min por IP)
    // ========================
    Route::middleware('throttle:60,1')->group(function () {
        // Login mais restritivo: 5 tentativas por minuto
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/login', [AuthController::class, 'login'])->name('api.login');
        });

        Route::get('/saloes', [SalaoController::class, 'index']);
        Route::get('/saloes/{slug}', [SalaoController::class, 'show']);
        Route::get('/saloes/{slug}/servicos', [SalaoController::class, 'servicos']);
        Route::get('/saloes/{slug}/manicures', [SalaoController::class, 'manicures']);
        Route::get('/saloes/{slug}/slots', [SalaoController::class, 'slots']);
    });

    // ========================
    // Autenticadas (120 req/min por usuário)
    // ========================
    Route::middleware(['auth:sanctum', 'user.active', 'throttle:120,1'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/me/fidelidade', [FidelidadeController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::prefix('agendamentos')->group(function () {
            Route::get('/', [AgendamentoController::class, 'index']);
            Route::post('/', [AgendamentoController::class, 'store']);
            Route::get('/slots', [AgendamentoController::class, 'slots']);
            Route::get('/{agendamento}', [AgendamentoController::class, 'show']);
            Route::post('/{agendamento}/cancelar', [AgendamentoController::class, 'cancelar']);
            Route::post('/{agendamento}/avaliar', [AgendamentoController::class, 'avaliar']);
        });
    });
});
