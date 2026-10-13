<?php

/*
|--------------------------------------------------------------------------
| Página principal del dominio
|--------------------------------------------------------------------------
|
| Registro de las portadas que se pueden publicar en la raíz del dominio («/»).
| El valor elegido se guarda en el parámetro PW00001 (Parámetros → «Pagina web
| principal»): la opción seleccionada ahí decide qué portada responde en /.
|
| Lo lee App\Support\PaginaPrincipal, que es lo único que consume este archivo:
| WebPageController para saber qué vista renderizar en la raíz y la lista de
| Parámetros para publicar las opciones del desplegable. Las vistas nunca leen
| este archivo directo.
|
| Cada portada declara:
|
|  - clave     Valor que se guarda en PW00001. Se conservan el 1 y el 2 que ya
|              existían (portada corporativa y portada de torneos) y se siguen
|              agregando números: un valor nuevo NUNCA reutiliza uno viejo.
|  - nombre    Cómo se llama la portada en el desplegable de Parámetros.
|  - vista     Blade que se renderiza en /. Tiene que existir: hay una prueba
|              (tests/Unit/PaginaPrincipalTest.php) que lo exige.
|  - producto  Clave de config/productos.php cuando la portada es la de un sitio
|              de producto (KAPTA, KIRAFACT, Pichanguero). La portada se publica
|              en el dominio, pero el sitio completo del producto sigue viviendo
|              en /site/<producto> y esa URL es la que se enlaza para verlo
|              entero, sin publicarlo en la raíz.
|
| El nombre de los sitios de producto NO se repite aquí dos veces por gusto: se
| escribe igual que en config/productos.php y hay una prueba que lo comprueba,
| para que renombrar un producto no deje el desplegable mintiendo.
|
| Alternar la portada NO despublica el resto del sitio: las demás páginas
| (corporativas y de producto) siguen respondiendo en sus URLs.
|
| Recuerda ejecutar `php artisan config:clear` (o `config:cache` en producción)
| después de editar este archivo.
|
*/

return [

    'paginas' => [

        '1' => [
            'nombre' => 'Aracode Principal',
            'vista' => 'aracode.pages.home',
            'producto' => null,
        ],

        '2' => [
            'nombre' => 'Aracode torneos',
            'vista' => 'aracode.pages.torneos',
            'producto' => null,
        ],

        '3' => [
            'nombre' => 'KAPTA LMS',
            'vista' => 'kapta.pages.home',
            'producto' => 'kapta',
        ],

        '4' => [
            'nombre' => 'KIRAFACT',
            'vista' => 'kirafact.pages.home',
            'producto' => 'kirafact',
        ],

        '5' => [
            'nombre' => 'Pichanguero',
            'vista' => 'pichanguero.pages.home',
            'producto' => 'pichanguero',
        ],

    ],

];
