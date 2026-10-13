<?php

/*
 * Arma una hoja de contactos con las capturas del catálogo para revisarlas de
 * un vistazo (una captura por celda, con su nombre). El PNG sale en
 * storage/tmp/capturas-hoja.png.
 *
 * Uso: php storage/tmp/capturas-hoja.php
 */

$origen = __DIR__.'/capturas';
$salida = __DIR__.'/capturas-hoja.png';

$slugs = [
    'clinica-dental',
    'restaurante-carta',
    'academia-cursos',
    'estudio-contable',
    'inmobiliaria-propiedades',
];

$columnas = 2;
$ancho = 560;
$alto = 350;
$etiqueta = 22;
$margen = 12;
$filas = (int) ceil(count($slugs) / $columnas);

$hoja = imagecreatetruecolor(
    $columnas * $ancho + ($columnas + 1) * $margen,
    $filas * ($alto + $etiqueta) + ($filas + 1) * $margen
);

imagefill($hoja, 0, 0, imagecolorallocate($hoja, 255, 255, 255));
$tinta = imagecolorallocate($hoja, 23, 27, 32);

foreach ($slugs as $i => $slug) {
    $ruta = $origen.'/'.$slug.'.png';

    if (! is_file($ruta)) {
        fwrite(STDERR, "Falta {$ruta}\n");
        continue;
    }

    $captura = imagecreatefrompng($ruta);
    $columna = $i % $columnas;
    $fila = intdiv($i, $columnas);
    $x = $margen + $columna * ($ancho + $margen);
    $y = $margen + $fila * ($alto + $etiqueta + $margen);

    imagecopyresampled($captura, $captura, 0, 0, 0, 0, $ancho, $alto, imagesx($captura), imagesy($captura));
    imagecopy($hoja, $captura, $x, $y, 0, 0, $ancho, $alto);
    imagestring($hoja, 4, $x + 2, $y + $alto + 4, $slug, $tinta);
    imagedestroy($captura);
}

imagepng($hoja, $salida);
imagedestroy($hoja);

echo "Hoja de contactos en {$salida}\n";
