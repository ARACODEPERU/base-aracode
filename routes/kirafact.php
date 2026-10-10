<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| KiraFact — rutas de su sitio web independiente
|--------------------------------------------------------------------------
|
| Blade puro: cada ruta devuelve una vista, sin controladores ni base de datos.
| Todo el sitio vive en resources/views/kirafact.
|
| COPIA A SU PROPIO DOMINIO:
| El prefijo de abajo existe solo para convivir con el sitio ARACODE dentro de
| este repositorio. Cuando copies el proyecto a su dominio, cambia $prefix a ''
| y las rutas quedarán en la raíz (ej. https://kirafact.com/).
|
*/

$prefix = 'site/kirafact';

Route::prefix($prefix)->name('kirafact.')->group(function () {
    Route::view('/', 'kirafact.pages.home')->name('home');
    Route::view('/planes', 'kirafact.pages.planes')->name('planes');
    Route::view('/contacto', 'kirafact.pages.contacto')->name('contacto');
});
