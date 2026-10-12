<?php

/*
 * Prepara las fotos de la demo «Restaurante y carta digital»: toma las
 * candidatas de Wikimedia Commons, las recorta a las proporciones del banco y
 * las guarda en WebP dentro de public/demos/restaurante-carta/img/.
 *
 * Proporciones (las mismas de la plantilla de referencia):
 *   - portada: 16/11 (tres fotos horizontales distintas)
 *   - vertical de la sección de confianza: 4/5
 *   - detalle: 16/10
 *
 * Uso: php storage/tmp/fotos-restaurante-preparar.php
 */

const DESTINO = __DIR__.'/../../public/demos/restaurante-carta/img';
const CALIDAD = 82;

// n (de la búsqueda) => [archivo destino, ancho, alto, sesgo del recorte (0 arriba, 1 abajo)]
$recetas = [
    6 => ['portada.webp', 1024, 704, 0.5],      // ceviche
    10 => ['parrilla.webp', 1024, 704, 0.5],    // pollo a la brasa
    207 => ['comedor.webp', 1024, 704, 0.5],    // mesa del restaurante
    209 => ['cocina.webp', 819, 1024, 0.2],     // chef trabajando (vertical)
    104 => ['causa.webp', 1200, 750, 0.5],      // causa limeña (detalle)
    15 => ['postres.webp', 1200, 750, 0.5],     // postres (detalle)
];

$busqueda = [];

foreach (['candidatas-commons', 'candidatas-commons2', 'candidatas-commons3'] as $carpeta) {
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

    $origen = imagecreatefromjpeg($fila['archivo']);
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

    imagewebp($lienzo, DESTINO.'/'.$destino, CALIDAD);
    imagedestroy($lienzo);

    $informe[] = [
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

file_put_contents(__DIR__.'/restaurante-fotos.json', json_encode($informe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

foreach ($informe as $fila) {
    printf("%-14s %-9s %4d KB  %s\n", $fila['archivo'], $fila['medidas'], $fila['kb'], mb_substr($fila['origen'], 5, 46));
}
