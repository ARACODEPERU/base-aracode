<?php

/*
 * Prepara las fotos de la demo «Tienda en línea» para la versión de tienda de
 * casa moderna.
 *
 * Tres orígenes:
 *   - Candidatas ya descargadas de Commons CC0 (varias carpetas de búsqueda).
 *   - Fotos que la demo ya tenía y que siguen sirviendo (se reutilizan tal cual:
 *     la tabla, los cucharones, la taza, el jarrón, la manta, la cesta y el jabón).
 *   - Dos fotos de la versión anterior que se recortan a otra proporción para el
 *     nuevo uso (la repisa de la tienda y el embalaje).
 *
 * Proporciones: portada 16/9 · bandas 3/2 · vertical del bloque de tienda 3/4 ·
 * productos 4/3.
 *
 * Uso: php storage/tmp/casa-fotos-preparar.php
 */

const DESTINO = __DIR__.'/../../public/demos/tienda-online/img';

// n => [destino, ancho, alto, sesgo del recorte, calidad]
$recetas = [
    'candidatas-casa3' => [
        1 => ['portada-salon.webp', 1280, 720, 0.45, 82],
        11 => ['portada-cocina.webp', 1280, 720, 0.5, 82],
        14 => ['portada-comedor.webp', 1280, 720, 0.5, 82],
        29 => ['banda-sala.webp', 1200, 800, 0.5, 82],
        23 => ['producto-sabanas-lino.webp', 900, 675, 0.5, 82],
        100 => ['producto-organizador-cajon.webp', 900, 675, 0.55, 82],
    ],
    'candidatas-casa' => [
        26 => ['producto-sarten-hierro.webp', 900, 675, 0.5, 82],
        33 => ['producto-maceta-ceramica.webp', 900, 675, 0.5, 82],
    ],
    'candidatas-casa4' => [
        9 => ['producto-vaso-vidrio.webp', 900, 675, 0.5, 82],
    ],
];

// Fotos que ya están en la carpeta y cambian de proporción y de nombre.
$recortes = [
    'portada-ceramica.webp' => ['banda-detalle.webp', 1200, 800, 0.5, 82],
    'taller-envio.webp' => ['estudio-vertical.webp', 900, 1200, 0.4, 82],
];

$problemas = [];
$informe = [];

/** Recorta en «cover» y guarda en WebP. */
function recortar($origen, string $archivo, int $ancho, int $alto, float $sesgo, int $calidad): void
{
    $anchoOrigen = imagesx($origen);
    $altoOrigen = imagesy($origen);
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
    $lienzo = imagecreatetruecolor($ancho, $alto);
    imagecopyresampled($lienzo, $recorte, 0, 0, 0, 0, $ancho, $alto, $anchoRecorte, $altoRecorte);
    imagewebp($lienzo, DESTINO.'/'.$archivo, $calidad);
    imagedestroy($recorte);
    imagedestroy($lienzo);

    global $problemas, $informe;

    $kb = (int) round(filesize(DESTINO.'/'.$archivo) / 1024);

    if ($kb > 250) {
        $problemas[] = sprintf('%s pesa %d KB', $archivo, $kb);
    }

    $informe[] = [$archivo, $ancho.'x'.$alto, $kb];
}

foreach ($recetas as $carpeta => $lista) {
    $datos = [];

    foreach (json_decode(file_get_contents(__DIR__.'/'.$carpeta.'/datos.json'), true) as $fila) {
        $datos[$fila['n']] = $fila;
    }

    foreach ($lista as $n => [$destino, $ancho, $alto, $sesgo, $calidad]) {
        $fila = $datos[$n] ?? null;

        if ($fila === null || ! is_file($fila['archivo'])) {
            $problemas[] = "#{$n} de {$carpeta}: falta la candidata";

            continue;
        }

        recortar(imagecreatefromstring(file_get_contents($fila['archivo'])), $destino, $ancho, $alto, $sesgo, $calidad);
    }
}

foreach ($recortes as $origen => [$destino, $ancho, $alto, $sesgo, $calidad]) {
    if (! is_file(DESTINO.'/'.$origen)) {
        $problemas[] = $origen.': no está en la carpeta';

        continue;
    }

    recortar(imagecreatefromstring(file_get_contents(DESTINO.'/'.$origen)), $destino, $ancho, $alto, $sesgo, $calidad);
}

foreach ($informe as [$archivo, $medidas, $kb]) {
    printf("%-32s %-9s %4d KB\n", $archivo, $medidas, $kb);
}

// Las fotos de la versión anterior que ya no se usan: la carpeta de una demo se
// copia entera a su subdominio, así que no se deja material muerto.
$sobran = [
    'pieza-ovillos.webp', 'pieza-vela.webp', 'banda-bazar.webp', 'banda-hilos.webp',
    'portada-torno.webp', 'portada-envio.webp', 'portada-ceramica.webp', 'taller-envio.webp',
];

foreach ($sobran as $archivo) {
    if (is_file(DESTINO.'/'.$archivo)) {
        unlink(DESTINO.'/'.$archivo);
        echo "fuera: {$archivo}\n";
    }
}

if ($problemas) {
    fwrite(STDERR, "\nNo se pudo preparar todo:\n - ".implode("\n - ", $problemas)."\n");
    exit(1);
}

printf("\n%d fotos nuevas · %d imágenes en la carpeta\n", count($informe), count(glob(DESTINO.'/*.webp')));
