<?php

use App\Http\Controllers\PersonController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Security\Http\Controllers\PermissionController;
use Modules\Security\Http\Controllers\RolesController;
use Modules\Security\Http\Controllers\SecurityController;
use Modules\Security\Http\Controllers\SuperEditorController;
use Modules\Security\Http\Controllers\UserActivityLogsController;

Route::middleware(['auth','user_activity_log'])->prefix('security')->group(function () {
    Route::get('dashboard', 'SecurityController@index')->name('security_dashboard');

    Route::get('profile', 'ProfileController@edit')->name('profile.edit');
    Route::post('profile', 'ProfileController@update')->name('profile.update');
    Route::delete('profile', 'ProfileController@destroy')->name('profile.destroy');

    Route::get('/config', function () {
        return Inertia::render('Security::Config');
    })->name('config');

    Route::resource('roles', RolesController::class);

    Route::resource('permissions', PermissionController::class);
    Route::get('destroy/permissions/{id}', 'PermissionController@destroy')->name('permissions_destroy');

    Route::get('table/permissions', 'PermissionController@getDataPermissions')->name('permissions_table');

    Route::post('person/information/update', [PersonController::class, 'createdOrUpdated'])->name('person_information_update');

    Route::get('dashboard/storage/indicator', [SecurityController::class, 'storageIndicador'])->name('security_storage_indicator');
    Route::post('dashboard/storage/recalculate', [SecurityController::class, 'storageRecalculate'])->name('security_storage_recalculate');

    Route::get('table/permissions', [PermissionController::class, 'getData'])->name('security_permissions_data');

    Route::get('user/activity/logs', [UserActivityLogsController::class, 'index'])->name('user_activity_logs');
    Route::get('user/activity/logs/data', [UserActivityLogsController::class, 'getData'])->name('user_activity_logs_data');

    /*
    |----------------------------------------------------------------------
    | Modo Super Editor
    |----------------------------------------------------------------------
    | 'enter' solo exige ser el rol autorizado; el resto de acciones exigen
    | además una sesión de edición abierta y vigente (middleware
    | super.editor). La contraseña se confirma en enter y en exit, con
    | throttle para frenar intentos por fuerza bruta.
    */
    Route::prefix('super-editor')->name('super_editor_')->group(function () {
        Route::middleware(['role:admin', 'throttle:10,1'])->group(function () {
            Route::post('enter', [SuperEditorController::class, 'enter'])->name('enter');
            Route::post('exit', [SuperEditorController::class, 'exit'])->name('exit');
        });

        Route::middleware('role:admin')->get('status', [SuperEditorController::class, 'status'])->name('status');
        Route::middleware('role:admin')->get('log', [SuperEditorController::class, 'log'])->name('log');
        Route::middleware('role:admin')->get('log/data', [SuperEditorController::class, 'logData'])->name('log_data');

        Route::middleware(['role:admin', 'super.editor'])->group(function () {
            Route::get('element', [SuperEditorController::class, 'element'])->name('element');
            Route::post('permission', [SuperEditorController::class, 'createPermission'])->name('permission');
            Route::post('stage', [SuperEditorController::class, 'stage'])->name('stage');
            Route::delete('stage', [SuperEditorController::class, 'revert'])->name('revert');
        });
    });
});
