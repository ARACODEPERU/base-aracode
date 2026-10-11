<?php

use App\Support\CatalogoPlantillas;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Banco de Plantillas Web (/plantillas, /plantillas/{slug} y /demo/{slug})
|--------------------------------------------------------------------------
|
| El catálogo vive en config/plantillas.php: la sección no toca la base de
| datos. Estas pruebas fijan su contrato:
|
|  - Resolver el catálogo no debe morderse la cola. categorias() arma los
|    totales llamando a todas(), que rellena cada plantilla con
|    nombreCategoria(); cuando ese método volvía a entrar en categorias() la
|    petición agotaba los 512 MB de memoria y respondía 500 sin mensaje.
|  - Las tres URLs del plan responden como dice el documento: 200 el catálogo y
|    la ficha publicada, 404 la plantilla que no existe, 302 /demo/{slug} hacia
|    la carpeta de la demostración.
|  - La demostración se abre dentro de public/demos/ (o en su subdominio el día
|    que se mude), nunca en la raíz del sitio.
|
| Corren como "Unit" y no como "Feature" porque la suite Feature de este
| repositorio no arranca en sqlite (migraciones MySQL-only de otros módulos);
| el helper crearTablasCmsDelSitio() vive en tests/Pest.php, igual que en las
| pruebas del catálogo de productos.
|
*/

uses(TestCase::class);

beforeEach(fn () => crearTablasCmsDelSitio());

it('resuelve el catálogo y los nombres de categoría sin recursión', function () {
    $plantillas = CatalogoPlantillas::todas();
    $categorias = CatalogoPlantillas::categorias();

    expect($plantillas)->not->toBeEmpty();
    expect($categorias)->not->toBeEmpty();

    expect(CatalogoPlantillas::nombreCategoria('salud'))->toBe('Salud y bienestar');

    // Cada plantilla llega con el nombre comercial de su categoría, no con la
    // clave de la config: si la cadena de llamadas se rompe, aquí aparece 'salud'.
    foreach ($plantillas as $plantilla) {
        expect($plantilla['categoria_nombre'])->not->toBe($plantilla['categoria']);
        expect($plantilla['categoria_nombre'])->toBe(CatalogoPlantillas::nombreCategoria($plantilla['categoria']));
    }

    // El total por categoría cuenta lo mismo que la config publicada.
    $suma = array_sum(array_column($categorias, 'total'));

    expect($suma)->toBe(count($plantillas));
});

it('devuelve la ficha de una plantilla publicada y null para una que no existe', function () {
    $plantilla = CatalogoPlantillas::porSlug('clinica-dental');

    expect($plantilla)->not->toBeNull();
    expect($plantilla['slug'])->toBe('clinica-dental');
    expect($plantilla['incluye'])->not->toBeEmpty();

    expect(CatalogoPlantillas::porSlug('no-existe'))->toBeNull();
});

it('resuelve la demostración dentro de public/demos', function () {
    $conDemo = CatalogoPlantillas::porSlug('clinica-dental');
    $sinDemo = CatalogoPlantillas::porSlug('restaurante-carta');

    // La carpeta de la config es relativa a public/demos/: el enlace no puede
    // quedar en /clinica-dental/ (la raíz del sitio) sino en /demos/...
    expect(CatalogoPlantillas::urlDemo($conDemo))->toEndWith('/demos/clinica-dental/');
    expect(CatalogoPlantillas::tieneDemo($conDemo))->toBeTrue();

    // Una plantilla cuya carpeta todavía no está no publica enlace.
    expect(CatalogoPlantillas::urlDemo($sinDemo))->toBeNull();
});

it('publica el catálogo con una tarjeta por plantilla publicada', function () {
    $html = $this->get('/plantillas')->getContent();

    expect($html)->toContain('Banco de Plantillas');
    expect(substr_count($html, 'plt-card__nombre'))->toBe(count(CatalogoPlantillas::todas()));

    // Los estilos de la sección se cargan solo en estas páginas.
    expect($html)->toContain('themes/plantillas/css/plantillas.css');
});

it('filtra por categoría y por búsqueda sin romper la página', function () {
    $salud = CatalogoPlantillas::filtrar('salud');
    $todas = CatalogoPlantillas::todas();

    expect(count($salud))->toBeLessThan(count($todas));
    expect($salud)->each(fn ($plantilla) => $plantilla->categoria->toBe('salud'));

    // La búsqueda ignora mayúsculas y tildes: «clinica» encuentra «Clínica».
    expect(CatalogoPlantillas::filtrar(null, 'clinica'))->toHaveCount(count($salud));

    // Una categoría retirada devuelve el catálogo completo, no una página vacía.
    expect(CatalogoPlantillas::filtrar('retirada'))->toHaveCount(count($todas));

    // Un `?categoria[]=x` llega como array y tampoco debe romper la página.
    $this->get('/plantillas?categoria[]=salud&q[]=dental')->assertOk();
});

it('responde 200 en la ficha publicada y 404 en la que no existe', function () {
    $this->get('/plantillas/clinica-dental')->assertOk();
    $this->get('/plantillas/clinica-dental')->assertSee('Clínica Dental', false);

    $this->get('/plantillas/no-existe')->assertNotFound();
});

it('marca el formato de cada plantilla y lo pinta en la tarjeta', function () {
    $plantillas = CatalogoPlantillas::todas();
    $tipos = CatalogoPlantillas::tipos();

    expect($tipos)->not->toBeEmpty();

    // Un formato sin nombre, hashtag o explicación no sirve para lo que existe:
    // que el visitante reconozca de un vistazo si le toca landing o sitio web.
    foreach ($tipos as $tipo) {
        expect($tipo['nombre'])->not->toBe('');
        expect($tipo['hashtag'])->toStartWith('#');
        expect($tipo['descripcion'])->not->toBe('');
    }

    foreach ($plantillas as $plantilla) {
        expect(array_keys($tipos))->toContain($plantilla['tipo']);
        expect($plantilla['tipo_hashtag'])->not->toBe('');
    }

    // Los totales por formato cubren el catálogo completo: nadie se queda sin
    // etiqueta cuando se agregue una plantilla nueva.
    expect(array_sum(array_column($tipos, 'total')))->toBe(count($plantillas));

    $html = $this->get('/plantillas')->getContent();

    // Una etiqueta de formato por tarjeta.
    expect(substr_count($html, 'plt-tipo'))->toBe(count($plantillas));

    foreach ($plantillas as $plantilla) {
        expect($html)->toContain($plantilla['tipo_hashtag']);
    }
});

it('explica el formato en la ficha, no solo lo nombra', function () {
    $plantilla = CatalogoPlantillas::porSlug('clinica-dental');
    $html = $this->get('/plantillas/clinica-dental')->getContent();

    expect($html)->toContain($plantilla['tipo_hashtag']);
    expect($html)->toContain($plantilla['tipo_nombre']);
    expect($html)->toContain($plantilla['tipo_descripcion']);
});

it('encuentra las plantillas por su formato desde el buscador', function () {
    $porTipo = fn (string $tipo) => array_column(array_filter(
        CatalogoPlantillas::todas(),
        fn (array $plantilla) => $plantilla['tipo'] === $tipo
    ), 'slug');

    $landings = $porTipo('landing');
    $sitios = $porTipo('sitio');

    expect($landings)->not->toBeEmpty();
    expect($sitios)->not->toBeEmpty();

    // El cliente que ya sabe qué plan quiere escribe «landing» o «sitio web» en
    // el buscador: tiene que encontrar todas las de ese formato.
    expect(array_column(CatalogoPlantillas::filtrar(null, 'landing'), 'slug'))->toEqualCanonicalizing($landings);
    expect(array_column(CatalogoPlantillas::filtrar(null, '#SitioWeb'), 'slug'))->toEqualCanonicalizing($sitios);
});

it('compila su hoja de estilos sin declarar clases del sitio', function () {
    $css = file_get_contents(public_path('themes/plantillas/css/plantillas.css'));

    expect($css)->not->toBeEmpty();

    // La sección se sirve con el layout corporativo, que ya carga
    // public/css/website.css con el preflight y todas las utilidades del sitio.
    // Si esta hoja vuelve a declarar una utilidad, gana por orden de carga
    // —misma especificidad y más al final— y rompe el comportamiento responsivo
    // del navbar y del pie: así desapareció el menú de escritorio cuando el
    // archivo reemitía `.hidden` y le ganaba al `.lg\:flex` corporativo.
    preg_match_all('/([^{}]+)\{/', $css, $bloques);

    $ajenas = [];

    foreach ($bloques[1] as $bloque) {
        $bloque = trim($bloque);

        // Las at-rules (@media, @keyframes) y los pasos de un keyframe no son
        // selectores: se ignoran.
        if ($bloque === '' || str_starts_with($bloque, '@') || preg_match('/^(\d+%|from|to)$/', $bloque)) {
            continue;
        }

        foreach (explode(',', $bloque) as $selector) {
            if (! str_contains($selector, 'plt-')) {
                $ajenas[] = trim($selector);
            }
        }
    }

    expect($ajenas)->toBe([]);

    // Y nada de Tailwind: lo propio de la sección es CSS propio.
    expect($css)->not->toContain('@tailwind');
});

it('solo publica la demostración de las plantillas que la tienen de verdad', function () {
    foreach (CatalogoPlantillas::todas() as $plantilla) {
        if (! CatalogoPlantillas::tieneDemo($plantilla)) {
            // Sin demo la ficha avisa de que está en preparación: nunca un enlace roto.
            expect(CatalogoPlantillas::urlDemo($plantilla))->toBeNull();

            continue;
        }

        // La carpeta de la config es relativa a public/demos/ y ahí está su entrada.
        expect(CatalogoPlantillas::urlDemo($plantilla))->toEndWith('/demos/'.$plantilla['demo'].'/');
        expect(is_file(public_path('demos/'.$plantilla['demo'].'/index.html')))->toBeTrue();
    }

    // Las dos demostraciones publicadas se resuelven: la landing y el sitio web.
    expect(CatalogoPlantillas::urlDemo(CatalogoPlantillas::porSlug('academia-cursos')))
        ->toEndWith('/demos/academia-cursos/');
});

it('no deja enlaces internos rotos en una demo de varias páginas', function () {
    // La academia es la primera demo de tipo sitio: sus páginas se enlazan entre
    // sí y con rutas relativas, así que una página mal nombrada se lleva al
    // visitante a un 404 dentro de la demostración.
    $demo = public_path('demos/academia-cursos');
    $archivos = glob($demo.'/*.html');

    expect($archivos)->toHaveCount(4);

    $paginas = array_map(fn (string $ruta) => basename($ruta), $archivos);
    $enlaces = [];

    foreach ($archivos as $archivo) {
        preg_match_all('/href="([^":]+?\.html)(?:[?#][^"]*)?"/i', file_get_contents($archivo), $coincidencias);

        foreach ($coincidencias[1] as $destino) {
            $enlaces[basename($archivo).' → '.$destino] = $destino;
        }
    }

    expect($enlaces)->not->toBeEmpty();

    // Cada página enlazada existe y es una de las cuatro de la demo. Si una se
    // rompe, el mensaje de la prueba dice desde qué página se enlazaba.
    $rotos = [];

    foreach ($enlaces as $descripcion => $destino) {
        if (! in_array($destino, $paginas, true) || ! is_file($demo.'/'.$destino)) {
            $rotos[] = $descripcion;
        }
    }

    expect($rotos)->toBe([], 'Enlaces internos rotos: '.implode(', ', $rotos));

    // Y el menú lleva a las tres secciones: inicio, catálogo y matrícula.
    expect(array_values($enlaces))->toContain('cursos.html');
    expect(array_values($enlaces))->toContain('matricula.html');
    expect(array_values($enlaces))->toContain('curso-desarrollo-web.html');
});

it('abre la demostración con un 302 a su carpeta y 404 si todavía no está', function () {
    $respuesta = $this->get('/demo/clinica-dental');

    $respuesta->assertRedirect();

    // La barra final importa: el destino es el índice de la carpeta de la demo
    // y sin ella el servidor tendría que redirigir otra vez.
    expect($respuesta->headers->get('Location'))->toEndWith('/demos/clinica-dental/');

    $this->get('/demo/restaurante-carta')->assertNotFound();
    $this->get('/demo/no-existe')->assertNotFound();
});
