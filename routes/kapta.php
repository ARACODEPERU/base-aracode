<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| KAPTA — rutas de su sitio web independiente
|--------------------------------------------------------------------------
|
| Blade puro: cada ruta devuelve una vista, sin controladores ni base de datos.
| Todo el sitio vive en resources/views/kapta.
|
| COPIA A SU PROPIO DOMINIO:
| El prefijo de abajo existe solo para convivir con el sitio ARACODE dentro de
| este repositorio. Cuando copies el proyecto a su dominio, cambia $prefix a ''
| y las rutas quedarán en la raíz (ej. https://kapta.pe/).
|
*/

$prefix = 'site/kapta';

Route::prefix($prefix)->name('kapta.')->group(function () {
    Route::view('/', 'kapta.pages.home')->name('home');
    Route::view('/planes', 'kapta.pages.planes')->name('planes');
    Route::view('/contacto', 'kapta.pages.contacto')->name('contacto');
});
