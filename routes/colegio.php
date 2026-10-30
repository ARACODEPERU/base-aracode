<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\Http\Controllers\ColegioAulaController;
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
     * Aula virtual compartida de Inicial y Primaria. El login valida la
     * matrícula del año escolar vigente antes de abrir la sesión; el resto de
     * rutas vive detrás de esa misma validación y se adapta al nivel del
     * alumno (ver ColegioAulaController::guard()).
     */
    Route::prefix('aula')->name('aula.')->group(function () {
        Route::get('login', [ColegioAulaController::class, 'create'])->name('login');

        Route::post('login', [ColegioAulaController::class, 'store'])
            ->middleware('throttle:8,1')
            ->name('login.store');

        Route::post('salir', [ColegioAulaController::class, 'destroy'])->name('logout');

        Route::get('/', [ColegioAulaController::class, 'dashboard'])->name('dashboard');

        Route::post('perfil/clave', [ColegioAulaController::class, 'updatePassword'])->name('profile.password');
        Route::get('curso/{course}', [ColegioAulaController::class, 'course'])
            ->whereNumber('course')
            ->name('course');

        // Materiales de apoyo: se ven en el visor del modal y se descargan con
        // un nombre legible, solo si pertenecen a un curso del alumno.
        Route::get('material/{resource}', [ColegioAulaController::class, 'viewMaterial'])
            ->whereNumber('resource')
            ->name('material.view');

        Route::get('material/{resource}/descargar', [ColegioAulaController::class, 'downloadMaterial'])
            ->whereNumber('resource')
            ->name('material.download');

        // Evaluaciones en línea y entregas de tareas de Primaria.
        Route::get('evaluacion/{activity}', [ColegioAulaController::class, 'quiz'])
            ->whereNumber('activity')
            ->name('quiz');
        Route::post('evaluacion/{activity}/iniciar', [ColegioAulaController::class, 'startQuiz'])
            ->whereNumber('activity')
            ->name('quiz.start');
        Route::post('evaluacion/{activity}', [ColegioAulaController::class, 'submitQuiz'])
            ->whereNumber('activity')
            ->name('quiz.submit');
        Route::get('evaluacion/{activity}/resultado', [ColegioAulaController::class, 'quizResult'])
            ->whereNumber('activity')
            ->name('quiz.result');
        Route::get('evaluacion/{activity}/resultado/descargar', [ColegioAulaController::class, 'downloadQuizResult'])
            ->whereNumber('activity')
            ->name('quiz.download');
        Route::post('tarea/{activity}/entrega', [ColegioAulaController::class, 'submitTask'])
            ->whereNumber('activity')
            ->name('task.submit');

        Route::get('juegos', [ColegioAulaController::class, 'games'])->name('games');

        Route::get('juegos/rompecabezas', [ColegioAulaController::class, 'puzzle'])->name('puzzle');
        Route::get('juegos/contando-frutas', [ColegioAulaController::class, 'fruits'])->name('fruits');
        Route::get('juegos/reconoce-la-letra', [ColegioAulaController::class, 'letters'])->name('letters');
        Route::get('juegos/colores-y-formas', [ColegioAulaController::class, 'colors'])->name('colors');
        Route::get('juegos/memoria-de-animales', [ColegioAulaController::class, 'memory'])->name('memory');
    });

    /*
     * Aula virtual de Secundaria. Tiene su propio acceso (que solo admite
     * matrículas de Secundaria) y sirve las vistas juveniles; el login del
     * aula también reconoce el nivel y redirige aquí cuando corresponde.
     */
    Route::prefix('secundaria')->name('secundaria.')->group(function () {
        Route::get('login', [ColegioAulaController::class, 'create'])->name('login');

        Route::post('login', [ColegioAulaController::class, 'store'])
            ->middleware('throttle:8,1')
            ->name('login.store');

        Route::get('/', [ColegioAulaController::class, 'dashboard'])->name('dashboard');

        Route::get('curso/{course}', [ColegioAulaController::class, 'course'])
            ->whereNumber('course')
            ->name('course');

        Route::get('material/{resource}', [ColegioAulaController::class, 'viewMaterial'])
            ->whereNumber('resource')
            ->name('material.view');

        Route::get('material/{resource}/descargar', [ColegioAulaController::class, 'downloadMaterial'])
            ->whereNumber('resource')
            ->name('material.download');

        // Evaluaciones en línea y entregas de tareas de Secundaria.
        Route::get('evaluacion/{activity}', [ColegioAulaController::class, 'quiz'])
            ->whereNumber('activity')
            ->name('quiz');
        Route::post('evaluacion/{activity}/iniciar', [ColegioAulaController::class, 'startQuiz'])
            ->whereNumber('activity')
            ->name('quiz.start');
        Route::post('evaluacion/{activity}', [ColegioAulaController::class, 'submitQuiz'])
            ->whereNumber('activity')
            ->name('quiz.submit');
        Route::get('evaluacion/{activity}/resultado', [ColegioAulaController::class, 'quizResult'])
            ->whereNumber('activity')
            ->name('quiz.result');
        Route::get('evaluacion/{activity}/resultado/descargar', [ColegioAulaController::class, 'downloadQuizResult'])
            ->whereNumber('activity')
            ->name('quiz.download');
        Route::post('tarea/{activity}/entrega', [ColegioAulaController::class, 'submitTask'])
            ->whereNumber('activity')
            ->name('task.submit');

        Route::post('salir', [ColegioAulaController::class, 'destroy'])->name('logout');
    });
});
