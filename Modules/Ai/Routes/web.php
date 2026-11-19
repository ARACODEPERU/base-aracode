<?php

use Illuminate\Support\Facades\Route;
use Modules\Ai\Http\Controllers\AiConfiguracionController;
use Modules\Ai\Http\Controllers\AiConsumoController;
use Modules\Ai\Http\Controllers\AiController;
use Modules\Ai\Http\Controllers\AiDashboardController;
use Modules\Ai\Http\Controllers\AiPrecioController;
use Modules\Ai\Http\Controllers\AiPruebaController;
use Modules\Ai\Http\Controllers\AracoditoController;

/*
|--------------------------------------------------------------------------
| Rutas del módulo de IA
|--------------------------------------------------------------------------
|
| Cada pantalla exige el permiso que también usa el menú. La configuración y
| los precios se guardan por POST/PUT porque cambian datos del sistema.
|
*/

Route::prefix('ai')->middleware(['auth', 'verified'])->group(function () {
    // Redirección sin closure para que la aplicación pueda cachear rutas.
    Route::redirect('/', '/ai/dashboard')->name('ai.index');

    Route::middleware('permission:ia_dashboard')->group(function () {
        Route::get('dashboard', [AiDashboardController::class, 'index'])->name('ai_dashboard');
    });

    Route::middleware('permission:ia_configuracion')->group(function () {
        Route::get('configuracion', [AiConfiguracionController::class, 'index'])->name('ai_configuracion');
        Route::post('configuracion', [AiConfiguracionController::class, 'update'])->name('ai_configuracion_update');
        Route::post('configuracion/roles', [AiConfiguracionController::class, 'updateRoles'])->name('ai_configuracion_roles');
        Route::post('configuracion/aracodito-ia', [AiConfiguracionController::class, 'updateAracoditoIa'])->name('ai_configuracion_aracodito_ia');
    });

    Route::middleware('permission:ia_consumo')->group(function () {
        Route::get('consumo', [AiConsumoController::class, 'index'])->name('ai_consumo');
        Route::get('consumo/exportar', [AiConsumoController::class, 'exportarCsv'])->name('ai_consumo_exportar');
        Route::get('consumo/pdf', [AiConsumoController::class, 'exportarPdf'])->name('ai_consumo_pdf');
    });

    Route::middleware('permission:ia_precios')->group(function () {
        Route::get('precios', [AiPrecioController::class, 'index'])->name('ai_precios');
        Route::post('precios', [AiPrecioController::class, 'store'])->name('ai_precios_store');
        Route::put('precios/{price}', [AiPrecioController::class, 'update'])->name('ai_precios_update');
        Route::delete('precios/{price}', [AiPrecioController::class, 'destroy'])->name('ai_precios_destroy');
    });

    Route::middleware('permission:ia_probar')->group(function () {
        Route::get('probar', [AiPruebaController::class, 'index'])->name('ai_probar');
        Route::post('probar', [AiPruebaController::class, 'enviar'])
            ->middleware('throttle:20,1')
            ->name('ai_probar_enviar');
    });

    // Aracodito con IA: solo uso del sistema y solo con el asistente activo.
    Route::post('aracodito/preguntar', [AracoditoController::class, 'preguntar'])
        ->middleware(['permission:ia_aracodito', 'throttle:30,1'])
        ->name('ai_aracodito_preguntar');

    /*
     * Rutas del esqueleto anterior del módulo. Se conservan para no romper
     * llamadas existentes, pero el chat real usa los servicios de AiManager.
     */
    Route::post('/consulta', [AiController::class, 'consulta'])->name('ai.consulta');
    Route::post('/censurar', [AiController::class, 'censurar'])->name('ai.censurar');
    Route::post('/mejorar', [AiController::class, 'mejorar'])->name('ai.mejorar');
    Route::post('/recomendar', [AiController::class, 'recomendar'])->name('ai.recomendar');
});
