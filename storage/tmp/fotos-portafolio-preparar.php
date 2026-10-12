<?php

/*
 * Prepara las fotos de la demo «Portafolio Creativo»: toma las candidatas
 * elegidas de los lotes de Commons, las recorta a las proporciones de la
 * plantilla y las guarda en WebP dentro de public/demos/portafolio-creativo/img/.
 *
 * Proporciones:
 *   - portada: 16/9 (tres fotos horizontales distintas, para el slider)
 *   - proyectos: 4/3 (las piezas del catálogo)
 *   - detalles: 3/2 (las bandas de sección)
 *   - estudio: 3/4 (la vertical del bloque de equipo)
 *
 * Uso: php storage/tmp/fotos-portafolio-preparar.php
 */

const DESTINO = __DIR__.'/../../public/demos/portafolio-creativo/img';
const CALIDAD = 82;

// n (de la búsqueda) => [destino, ancho, alto, sesgo del recorte (0 arriba, 1 abajo), calidad]
// La calidad se baja solo donde la foto es muy densa (una pared llena de
// láminas): con 82 pasaba de los 250 KB de la regla del banco.
$recetas = [
    // Portada (slider de tres fotos)
    807 => ['pliego.webp', 1024, 576, 0.5, 80],      // pared de láminas y carteles en la calle
    850 => ['mural.webp', 1280, 720, 0.5, 82],       // fachada pintada a bloques de color
    849 => ['taller.webp', 1280, 720, 0.5, 82],      // manos trabajando sobre el banco del taller

    // Proyectos
    924 => ['cartel.webp', 1000, 750, 0.5, 82],          // collage de carteles rasgados
    859 => ['ilustracion.webp', 1000, 750, 0.5, 82],     // mesa de ilustración con acuarela
    873 => ['editorial.webp', 1000, 750, 0.5, 82],       // pliegos y revista impresa
    945 => ['encuadernacion.webp', 1000, 750, 0.5, 82],  // manos trabajando una pieza en el banco
    846 => ['mural-rosa.webp', 1000, 750, 0.5, 82],      // mural rosa en proceso, con pistola
    806 => ['marca.webp', 1000, 750, 0.5, 82],           // marca urbana sobre pared azul

    // Detalles de sección
    952 => ['taller-detalle.webp', 1200, 800, 0.5, 82],  // pared de herramientas rotuladas
    818 => ['estudio-taller.webp', 1200, 800, 0.5, 82],  // estudio con caballete y pinceles

    // Vertical del bloque de estudio
    1008 => ['estudio.webp', 900, 1200, 0.5, 82],        // fotógrafa en el camino arbolado
];

$busqueda = [];

foreach (['candidatas-portafolio', 'candidatas-portafolio2', 'candidatas-portafolio3', 'candidatas-portafolio-v'] as $carpeta) {
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

foreach ($recetas as $n => [$destino, $ancho, $alto, $sesgo, $calidad]) {
    $fila = $busqueda[$n] ?? null;

    if ($fila === null || ! is_file($fila['archivo'])) {
        $problemas[] = "#{$n}: falta la candidata";
        continue;
    }

    $origen = imagecreatefromstring(file_get_contents($fila['archivo']));
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

    imagewebp($lienzo, DESTINO.'/'.$destino, $calidad ?? CALIDAD);
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
        'origen' => $fila['titulo'],
        'fuente' => $fila['fuente'] ?? 'Wikimedia Commons',
        'autor' => $fila['autor'],
        'licencia' => $fila['licencia'],
        'ficha' => $fila['ficha'],
    ];
}

file_put_contents(__DIR__.'/portafolio-fotos.json', json_encode($informe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

foreach ($informe as $fila) {
    printf("%-22s %-9s %4d KB  %s\n", $fila['archivo'], $fila['medidas'], $fila['kb'], mb_substr($fila['origen'], 5, 46));
}

if ($problemas) {
    fwrite(STDERR, "Revisar:\n - ".implode("\n - ", $problemas)."\n");
    exit(1);
}
