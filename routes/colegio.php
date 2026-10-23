<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\Http\Controllers\ColegioInicialController;
use Modules\Academic\Http\Controllers\ColegioPublicController;

/*
|--------------------------------------------------------------------------
| Colegio — sitio público y acceso al aula virtual
|--------------------------------------------------------------------------
|
| Rutas abiertas (sin autenticación) para mostrar la página institucional del
| colegio y la pantalla de bienvenida del aula virtual de cada nivel.
|
| El prefijo «colegio» existe solo para convivir con el resto de sitios de
| este repositorio; el sitio puede publicarse en su propio dominio cambiando
| la línea del prefix por ''.
|
*/

Route::prefix('colegio')->name('colegio.')->group(function () {
    Route::get('/', [ColegioPublicController::class, 'index'])->name('home');

    /*
     * Aula virtual de Inicial. El login valida la matrícula del año escolar
     * vigente antes de abrir la sesión; el resto de rutas vive detrás de esa
     * misma validación (ver ColegioInicialController::guard()).
     */
    Route::prefix('inicial')->name('inicial.')->group(function () {
        Route::get('login', [ColegioInicialController::class, 'create'])->name('login');

        Route::post('login', [ColegioInicialController::class, 'store'])
            ->middleware('throttle:8,1')
            ->name('login.store');

        Route::post('salir', [ColegioInicialController::class, 'destroy'])->name('logout');

        Route::get('aula', [ColegioInicialController::class, 'dashboard'])->name('dashboard');

        Route::post('perfil/clave', [ColegioInicialController::class, 'updatePassword'])->name('profile.password');
        Route::get('aula/curso/{course}', [ColegioInicialController::class, 'course'])
            ->whereNumber('course')
            ->name('course');

        // Materiales de apoyo: se ven en el visor del modal y se descargan con
        // un nombre legible, solo si pertenecen a un curso del alumno.
        Route::get('aula/material/{resource}', [ColegioInicialController::class, 'viewMaterial'])
            ->whereNumber('resource')
            ->name('material.view');

        Route::get('aula/material/{resource}/descargar', [ColegioInicialController::class, 'downloadMaterial'])
            ->whereNumber('resource')
            ->name('material.download');
        Route::get('juegos', [ColegioInicialController::class, 'games'])->name('games');

        Route::get('juegos/rompecabezas', [ColegioInicialController::class, 'puzzle'])->name('puzzle');
        Route::get('juegos/contando-frutas', [ColegioInicialController::class, 'fruits'])->name('fruits');
        Route::get('juegos/reconoce-la-letra', [ColegioInicialController::class, 'letters'])->name('letters');
        Route::get('juegos/colores-y-formas', [ColegioInicialController::class, 'colors'])->name('colors');
        Route::get('juegos/memoria-de-animales', [ColegioInicialController::class, 'memory'])->name('memory');
    });
});
