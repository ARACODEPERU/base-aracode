<?php

/*
 * Prepara las fotos de la demo «Estudio Contable»: toma las candidatas de
 * Wikimedia Commons, las recorta a las proporciones del banco y las guarda en
 * WebP dentro de public/demos/estudio-contable/img/.
 *
 * Proporciones (las mismas de la plantilla de referencia de formato sitio):
 *   - portada: 16/11 (tres fotos horizontales distintas)
 *   - vertical de la sección de confianza: 4/5
 *   - resto de las secciones: 3/2
 *
 * Uso: php storage/tmp/fotos-estudio-preparar.php
 */

const DESTINO = __DIR__.'/../../public/demos/estudio-contable/img';
const CALIDAD = 82;

// n (de la búsqueda) => [archivo destino, ancho, alto, sesgo del recorte (0 arriba, 1 abajo)]
$recetas = [
    403 => ['reunion.webp', 1024, 704, 0.5],     // sala de reuniones (portada)
    317 => ['oficina.webp', 1024, 704, 0.5],     // oficina con ventanales (portada)
    302 => ['escritorio.webp', 1024, 704, 0.5],  // laptop y cuaderno con apuntes (portada)
    307 => ['equipo.webp', 819, 1024, 0.5],      // puestos de trabajo con una persona (vertical)
    303 => ['asistencia.webp', 1200, 800, 0.5],  // laptop con lentes y mouse (detalle)
    316 => ['archivo.webp', 1200, 800, 0.5],     // estantería con libros y plantas (detalle)
    406 => ['revision.webp', 1200, 800, 0.5],    // sala de reuniones vacía (detalle)
];

$busqueda = [];

foreach (['candidatas-estudio', 'candidatas-estudio2', 'candidatas-estudio3', 'candidatas-estudio4', 'candidatas-estudio5', 'candidatas-estudio6', 'candidatas-estudio7'] as $carpeta) {
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

file_put_contents(__DIR__.'/estudio-fotos.json', json_encode($informe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

foreach ($informe as $fila) {
    printf("%-16s %-9s %4d KB  %s\n", $fila['archivo'], $fila['medidas'], $fila['kb'], mb_substr($fila['origen'], 5, 44));
}
