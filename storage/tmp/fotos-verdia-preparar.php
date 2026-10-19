<?php

/*
 * Prepara las fotos de la demo «Supermercado Online — VERDIA Market»: toma las
 * candidatas elegidas de los lotes de Commons, las recorta a las proporciones
 * de la plantilla y las guarda en WebP dentro de
 * public/demos/supermercado-online-verdia/img/.
 *
 * Proporciones:
 *   - portada: 16/10  (el hero comercial)
 *   - banda:   3/2    (las tres franjas promocionales y el bloque de confianza)
 *   - rubro:   4/3    (las nueve tarjetas de categoría)
 *   - pieza:   1/1    (las tarjetas de producto)
 *   - ancha:   16/9   (el banner de bienvenida)
 *
 * Uso: php storage/tmp/fotos-verdia-preparar.php
 */

const DESTINO = __DIR__.'/../../public/demos/supermercado-online-verdia/img';
const CALIDAD = 82;

// n (de la búsqueda) => [destino, ancho, alto, sesgo del recorte (0 arriba, 1 abajo)]
$recetas = [
    // Portada y bandas promocionales
    21 => ['portada-cesta.webp', 1280, 800, 0.5],      // cesta con verduras
    10 => ['portada-mercado.webp', 1280, 800, 0.5],    // puesto de hortalizas
    46 => ['portada-pan.webp', 1280, 800, 0.45],       // pan en manos del panadero
    28 => ['banda-frescos.webp', 1200, 800, 0.5],      // surtido de frutas y verduras
    5 => ['banda-abarrotes.webp', 1200, 800, 0.5],     // tarros de miel
    35 => ['banda-saludable.webp', 1200, 800, 0.5, 72], // plato saludable

    // Tarjetas de categoría (9)
    13 => ['rubro-frutas.webp', 800, 600, 0.5],        // puesto de frutas
    127 => ['rubro-lacteos.webp', 800, 600, 0.5],      // huevos en cuenco
    103 => ['rubro-carnes.webp', 800, 600, 0.5],       // carnicería
    45 => ['rubro-panaderia.webp', 800, 600, 0.5],     // pan rebanado
    22 => ['rubro-abarrotes.webp', 800, 600, 0.5],     // estantes de despensa
    58 => ['rubro-bebidas.webp', 800, 600, 0.5],       // mesa con zumos
    63 => ['rubro-snacks.webp', 800, 600, 0.5],        // galletas
    206 => ['rubro-limpieza.webp', 800, 600, 0.5],     // productos de limpieza
    110 => ['rubro-cuidado.webp', 800, 600, 0.5],      // jabones artesanales

    // Productos (12)
    3 => ['p-naranjas.webp', 700, 700, 0.45],          // naranjas sanguinas
    6 => ['p-arandanos.webp', 700, 700, 0.45],         // arándanos
    16 => ['p-pepinos.webp', 700, 700, 0.5],           // pepinos
    43 => ['p-zanahorias.webp', 700, 700, 0.5],        // zanahorias
    148 => ['p-pan.webp', 700, 700, 0.45],             // hogaza de pan
    123 => ['p-huevos.webp', 700, 700, 0.5],           // huevos de colores
    52 => ['p-yogur.webp', 700, 700, 0.4],             // vasos de yogur con fruta
    54 => ['p-queso.webp', 700, 700, 0.5],             // queso con crackers
    118 => ['p-cafe.webp', 700, 700, 0.5],             // café en grano
    105 => ['p-pescado.webp', 700, 700, 0.5],          // pescados frescos sobre hielo
    60 => ['p-galletas.webp', 700, 700, 0.4],          // galletas con especias
    27 => ['p-platanos.webp', 700, 700, 0.45],         // plátanos verdes
    146 => ['p-tomates.webp', 700, 700, 0.45],         // tomates en cuenco

    402 => ['p-cereal.webp', 700, 700, 0.45],          // cereal con leche
    413 => ['p-croissant.webp', 700, 700, 0.5],        // croissant y café
    417 => ['p-hogaza.webp', 700, 700, 0.45],          // hogazas de pan
    454 => ['p-pasta.webp', 700, 700, 0.45],           // pasta fresca

    // Ofertas del día (4)
    18 => ['o-judias.webp', 800, 800, 0.5],            // judías verdes
    435 => ['o-fresas.webp', 800, 800, 0.5],           // fresas en cuenco
    133 => ['o-filete.webp', 800, 800, 0.5],           // filete con hierbas
    37 => ['o-tomate.webp', 800, 800, 0.5],            // salsa de tomate

    // Bloque editorial de confianza y banner de bienvenida
    15 => ['confianza-puesto.webp', 1000, 667, 0.5],   // manos eligiendo fruta
    8 => ['confianza-mercado.webp', 1000, 667, 0.5],   // mercado de barrio
    26 => ['bienvenida.webp', 1280, 720, 0.5],         // desayuno con frutos rojos
];

$busqueda = [];

foreach (['candidatas-verdia', 'candidatas-verdia2', 'candidatas-verdia3', 'candidatas-verdia5'] as $carpeta) {
    $ruta = __DIR__.'/'.$carpeta.'/datos.json';

    if (is_file($ruta)) {
        foreach (json_decode(file_get_contents($ruta), true) as $fila) {
            $busqueda[$fila['n']] = $fila;
        }
    }
}

if (! is_dir(DESTINO)) {
    mkdir(DESTINO, 0777, true);
}

$informe = [];
$problemas = [];

foreach ($recetas as $n => $receta) {
    [$destino, $ancho, $alto, $sesgo] = $receta;
    $calidad = $receta[4] ?? CALIDAD;
    $fila = $busqueda[$n] ?? null;

    if ($fila === null || ! is_file($fila['archivo'])) {
        $problemas[] = "#{$n}: falta la candidata";
        continue;
    }

    $origen = imagecreatefromstring(file_get_contents($fila['archivo']));

    if ($origen === false) {
        $problemas[] = "#{$n}: la candidata no se pudo leer";
        continue;
    }

    $anchoOrigen = imagesx($origen);
    $altoOrigen = imagesy($origen);

    // Recorte «cover»: se toma la mayor ventana con la proporción pedida y se
    // deja el resto fuera. `sesgo` decide de dónde se corta en vertical.
    $proporcion = $ancho / $alto;
    $anchoRecorte = $anchoOrigen;
    $altoRecorte = (int) round($anchoOrigen / $proporcion);

    if ($altoRecorte > $altoOrigen) {
        $altoRecorte = $altoOrigen;
        $anchoRecorte = (int) round($altoOrigen * $proporcion);
    }

    $x = (int) round(($anchoOrigen - $anchoRecorte) / 2);
    $y = (int) round(($altoOrigen - $altoRecorte) * $sesgo);

    $recorte = imagecrop($origen, ['x' => $x, 'y' => $y, 'width' => $anchoRecorte, 'height' => $altoRecorte]);
    imagedestroy($origen);

    $lienzo = imagecreatetruecolor($ancho, $alto);
    imagecopyresampled($lienzo, $recorte, 0, 0, 0, 0, $ancho, $alto, $anchoRecorte, $altoRecorte);
    imagedestroy($recorte);

    imagewebp($lienzo, DESTINO.'/'.$destino, $calidad);
    imagedestroy($lienzo);

    $kb = (int) round(filesize(DESTINO.'/'.$destino) / 1024);

    // La regla del banco es que ninguna imagen pase de unos 250 KB.
    if ($kb > 250) {
        $problemas[] = sprintf('%s pesa %d KB', $destino, $kb);
    }

    $informe[] = [
        'archivo' => $destino,
        'medidas' => $ancho.'x'.$alto,
        'kb' => $kb,
        'candidata' => $n,
        'titulo' => $fila['titulo'],
        'autor' => $fila['autor'],
        'licencia' => $fila['licencia'],
        'ficha' => $fila['ficha'],
        'original' => $fila['original'],
    ];
}

foreach ($informe as $fila) {
    printf(
        "%-24s %-9s %4d KB  ← #%03d %s\n",
        $fila['archivo'],
        $fila['medidas'],
        $fila['kb'],
        $fila['candidata'],
        mb_substr($fila['titulo'], 5, 44)
    );
}

file_put_contents(__DIR__.'/verdia-fotos.json', json_encode($informe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

if ($problemas) {
    fwrite(STDERR, "\nNo se pudo preparar todo:\n - ".implode("\n - ", $problemas)."\n");
    exit(1);
}

printf("\n%d imágenes en %s\n", count($informe), realpath(DESTINO));
