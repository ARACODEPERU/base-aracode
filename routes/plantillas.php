<?php

use App\Http\Controllers\PlantillasController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Banco de Plantillas Web — rutas de la sección
|--------------------------------------------------------------------------
|
| Se monta en RouteServiceProvider con el middleware web y SIN prefijo, igual
| que routes/aracode.php, para que las URLs sean las del plan:
|
|   /plantillas            catálogo: categorías, buscador y filtros por GET
|   /plantillas/{slug}     ficha de la plantilla
|   /demo/{slug}           abre la demostración (302 a /demos/<slug>/)
|   /paginas-web           el servicio: los planes con que se publica una
|                          plantilla (mensual o anual), con su propia URL
|                          para poder posicionarla y recibir enlaces
|
| /paginas-web va a nivel de raíz y no colgando de /plantillas: el comodín
| `/plantillas/{slug}` se registra antes y se comería cualquier página hija
| (respondería 404 «no existe esa plantilla»).
|
| Vive en su propio archivo, y no dentro de routes/aracode.php, para no tocar
| las rutas del sitio corporativo ni arriesgar un choque de nombres.
|
| Comprobado antes de crearlas (docs/BANCO_PLANTILLAS_FASE1.md): de las 1383
| rutas registradas ninguna era /plantillas ni /demo, ninguna empezaba por un
| parámetro y no hay catch-all, así que estas tres no chocan con nada.
|
| El slug se acota a minúsculas, números y guiones: así /plantillas/loquesea.php
| no llega ni al controlador.
|
*/

Route::get('/plantillas', [PlantillasController::class, 'index'])->name('plantillas.catalogo');

Route::get('/plantillas/{slug}', [PlantillasController::class, 'ficha'])
    ->where('slug', '[a-z0-9-]+')
    ->name('plantillas.ficha');

Route::get('/demo/{slug}', [PlantillasController::class, 'demo'])
    ->where('slug', '[a-z0-9-]+')
    ->name('plantillas.demo');

// El nombre empieza por plantillas.* a propósito: el enlace del navbar se marca
// activo con routeIs('plantillas.*') y el servicio es parte de esa sección.
Route::get('/paginas-web', [PlantillasController::class, 'paginasWeb'])->name('plantillas.paginas_web');
