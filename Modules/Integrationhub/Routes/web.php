<?php

use Illuminate\Support\Facades\Route;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->prefix('integrationhub')->group(function () {
    Route::get('listado', [IntegrationhubController::class, 'index'])->name('integrationhub_listado');
    Route::middleware(['middleware' => 'permission:integrationhub_crear'])->get('create', [IntegrationhubController::class, 'create'])->name('integrationhub_create');
    Route::post('store', [IntegrationhubController::class, 'store'])->name('integrationhub_store');
    Route::middleware(['middleware' => 'permission:integrationhub_editar'])->get('editar/{id}', [IntegrationhubController::class, 'edit'])->name('integrationhub_editar');
    Route::put('update/{id}', [IntegrationhubController::class, 'update'])->name('integrationhub_update');
    Route::middleware(['middleware' => 'permission:integrationhub_eliminar'])->delete('destroy/{id}', [IntegrationhubController::class, 'destroy'])->name('integrationhub_destroy');
    Route::middleware(['middleware' => 'permission:integrationhub_ejecutar'])->post('execute/{id}', [IntegrationhubController::class, 'execute'])->name('integrationhub_execute');
    Route::put('update-auth/{id}', [IntegrationhubController::class, 'updateAuth'])->name('integrationhub_update_auth');
    Route::delete('destroy-auth/{id}', [IntegrationhubController::class, 'destroyAuth'])->name('integrationhub_destroy_auth');
    Route::put('update-status-auth/{id}', [IntegrationhubController::class, 'updateStatusAuth'])->name('integrationhub_update_status_auth');
    Route::put('update-fieldmap/{id}', [IntegrationhubController::class, 'updateFieldMap'])->name('integrationhub_update_fieldmap');
    Route::put('update-subitems/{id}', [IntegrationhubController::class, 'updateSubitems'])->name('integrationhub_update_subitems');
    Route::delete('destroy-fieldmap/{id}', [IntegrationhubController::class, 'destroyFieldMap'])->name('integrationhub_destroy_fieldmap');
    Route::put('update-status-fieldmap/{id}', [IntegrationhubController::class, 'updateStatusFieldMap'])->name('integrationhub_update_status_fieldmap');
    Route::get('get-tables', [IntegrationhubController::class, 'getTables'])->name('integrationhub_get_tables');
    Route::get('get-table-columns', [IntegrationhubController::class, 'getTableColumns'])->name('integrationhub_get_table_columns');
    Route::put('update-endpoints/{id}', [IntegrationhubController::class, 'updateEndpoints'])->name('integrationhub_update_endpoints');
    Route::delete('destroy-endpoints/{id}', [IntegrationhubController::class, 'destroyEndpoints'])->name('integrationhub_destroy_endpoints');
    Route::put('update-status-endpoints/{id}', [IntegrationhubController::class, 'updateStatusEndpoints'])->name('integrationhub_update_status_endpoints');
    Route::put('update-query/{id}', [IntegrationhubController::class, 'updateQuery'])->name('integrationhub_update_query');
    Route::delete('destroy-query/{id}', [IntegrationhubController::class, 'destroyQuery'])->name('integrationhub_destroy_query');
    Route::put('update-status-query/{id}', [IntegrationhubController::class, 'updateStatusQuery'])->name('integrationhub_update_status_query');
    Route::put('update-schedule/{id}', [IntegrationhubController::class, 'updateSchedule'])->name('integrationhub_update_schedule');
    Route::delete('destroy-schedule/{id}', [IntegrationhubController::class, 'destroySchedule'])->name('integrationhub_destroy_schedule');
});
