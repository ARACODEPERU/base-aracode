<?php

/*
|--------------------------------------------------------------------------
| Web Routes del módulo Performance
|--------------------------------------------------------------------------
|
| Todo el módulo es exclusivo de administración: el middleware role exige
| 'admin' o 'Administrador' y el controlador vuelve a comprobar el permiso.
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Performance\Http\Controllers\PerformanceImageController;
use Modules\Performance\Http\Controllers\PerformanceQueueController;

Route::middleware(['auth', 'verified', 'user_activity_log', 'role:admin|Administrador'])
    ->prefix('performance')
    ->group(function () {
        Route::get('images', [PerformanceImageController::class, 'index'])
            ->name('performance_images_index');

        Route::post('images/optimize', [PerformanceImageController::class, 'optimize'])
            ->name('performance_images_optimize');

        Route::post('images/replace', [PerformanceImageController::class, 'replace'])
            ->name('performance_images_replace');

        Route::post('images/webp', [PerformanceImageController::class, 'webp'])
            ->name('performance_images_webp');

        Route::post('images/gif', [PerformanceImageController::class, 'convertGif'])
            ->name('performance_images_gif');

        Route::post('images/quarantine', [PerformanceImageController::class, 'quarantine'])
            ->name('performance_images_quarantine');

        Route::post('images/quarantine/restore', [PerformanceImageController::class, 'restoreQuarantine'])
            ->name('performance_images_quarantine_restore');

        Route::get('images/quarantine/file', [PerformanceImageController::class, 'quarantineFile'])
            ->name('performance_images_quarantine_file');

        Route::post('images/quarantine/delete', [PerformanceImageController::class, 'deleteQuarantine'])
            ->name('performance_images_quarantine_delete');

        Route::post('images/backups/purge', [PerformanceImageController::class, 'purgeBackups'])
            ->name('performance_backups_purge');

        Route::post('images/restore', [PerformanceImageController::class, 'restore'])
            ->name('performance_images_restore');

        Route::post('images/alerts/review', [PerformanceImageController::class, 'reviewAlerts'])
            ->name('performance_alerts_review');

        Route::get('weight', [PerformanceImageController::class, 'weight'])
            ->name('performance_weight_index');

        Route::get('queues', [PerformanceQueueController::class, 'index'])
            ->name('performance_queue_index');
    });
