<?php

/*
 * Prepara las fotos de la demo «Alameda Propiedades»: toma las candidatas
 * elegidas de Wikimedia Commons, las recorta a las proporciones del sitio y las
 * guarda en WebP dentro de public/demos/inmobiliaria-propiedades/img/.
 *
 * Proporciones:
 *   - portada: 16/11 (tres fotos horizontales distintas)
 *   - vertical de la sección de confianza: 4/5
 *   - galería de la ficha: 3/2 a 1200 px de ancho
 *   - fotos de tarjeta (listado): 3/2 a 900 px de ancho, que es como se ven
 *   - banda de «vende tu propiedad»: 3/2 a 1200 px
 *
 * Uso: php storage/tmp/fotos-inmobiliaria-preparar.php
 */

const DESTINO = __DIR__.'/../../public/demos/inmobiliaria-propiedades/img';
const CALIDAD = 78;

// n (de la búsqueda) => [archivo destino, ancho, alto, sesgo del recorte (0 arriba, 1 abajo)]
$recetas = [
    // Portada (16/11)
    913 => ['ciudad.webp', 1024, 704, 0.42],             // rascacielos al atardecer
    439 => ['sala.webp', 1024, 704, 0.5],                // sala con sofás azules
    732 => ['casa.webp', 1024, 704, 0.5],                // casa blanca
    // Sección de confianza (4/5)
    614 => ['sala-vertical.webp', 819, 1024, 0.5],       // rincón de sala con plantas
    // Galería de la ficha (3/2)
    616 => ['comedor.webp', 1200, 800, 0.5],             // mesa de centro junto al ventanal
    440 => ['cocina.webp', 1200, 800, 0.5],              // cocina con isla
    447 => ['dormitorio.webp', 1200, 800, 0.5],          // dormitorio del loft
    456 => ['balcon.webp', 1200, 800, 0.5],              // balcón
    // Ficha: bloque «el edificio» (16/11)
    402 => ['edificio.webp', 1024, 704, 0.45],           // edificio de departamentos de ladrillo
    // Tarjetas del listado (3/2)
    474 => ['comedor-luminoso.webp', 900, 600, 0.5],     // comedor con mesa redonda
    445 => ['dormitorio-ventanal.webp', 900, 600, 0.5],  // dormitorio con ventanales
    448 => ['ventana.webp', 900, 600, 0.5],              // ventana de dormitorio
    750 => ['casa-blanca.webp', 900, 600, 0.5],          // casa blanca
    932 => ['casa-tejado.webp', 900, 600, 0.5],          // casa antigua con techo de tejas
    807 => ['sala-cozy.webp', 900, 600, 0.5],            // sala con sofá
    810 => ['cocina-pequena.webp', 900, 600, 0.5],       // cocina de departamento
    // Banda de «vende tu propiedad» (3/2)
    652 => ['casa-jardin.webp', 1200, 800, 0.5],         // casa blanca de madera
];

// Índice de candidatas por carpeta de búsqueda.
$busqueda = [];

foreach (['candidatas-inmobiliaria', 'candidatas-inmobiliaria2', 'candidatas-inmobiliaria3', 'candidatas-inmobiliaria4', 'candidatas-inmobiliaria5', 'candidatas-inmobiliaria6'] as $carpeta) {
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

foreach ($recetas as $n => [$destino, $ancho, $alto, $sesgo]) {
    $fila = $busqueda[$n] ?? null;

    if ($fila === null || ! is_file($fila['archivo'])) {
        fwrite(STDERR, "#{$n}: falta la candidata\n");

        continue;
    }

    $origen = imagecreatefromstring(file_get_contents($fila['archivo']));
    $anchoOrigen = imagesx($origen);
    $altoOrigen = imagesy($origen);

    // Recorte «cover»: la mayor ventana con la proporción pedida, con el sesgo
    // decidiendo de dónde se corta en vertical.
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

    imagewebp($lienzo, DESTINO.'/'.$destino, CALIDAD);
    imagedestroy($lienzo);

    $informe[] = [
        'n' => $n,
        'archivo' => $destino,
        'medidas' => $ancho.'x'.$alto,
        'kb' => round(filesize(DESTINO.'/'.$destino) / 1024),
        'origen' => $fila['titulo'],
        'original' => $fila['original'],
        'autor' => $fila['autor'],
        'licencia' => $fila['licencia'],
        'ficha' => $fila['ficha'],
    ];
}

file_put_contents(__DIR__.'/inmobiliaria-fotos.json', json_encode($informe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

foreach ($informe as $fila) {
    printf("%-24s %-9s %4d KB  %s\n", $fila['archivo'], $fila['medidas'], $fila['kb'], mb_substr($fila['origen'], 5, 46));
}

echo count($informe)." fotos preparadas\n";
