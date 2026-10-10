<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pichanguero — rutas de su sitio web independiente
|--------------------------------------------------------------------------
|
| Blade puro: cada ruta devuelve una vista, sin controladores ni base de datos.
| Todo el sitio vive en resources/views/pichanguero.
|
| COPIA A SU PROPIO DOMINIO:
| El prefijo de abajo existe solo para convivir con el sitio ARACODE dentro de
| este repositorio. Cuando copies el proyecto a su dominio, cambia $prefix a ''
| y las rutas quedarán en la raíz (ej. https://pichanguero.pe/).
|
*/

$prefix = 'site/pichanguero';

Route::prefix($prefix)->name('pichanguero.')->group(function () {
    Route::view('/', 'pichanguero.pages.home')->name('home');
    Route::view('/descargas', 'pichanguero.pages.descargas')->name('descargas');
    Route::view('/contacto', 'pichanguero.pages.contacto')->name('contacto');
});
