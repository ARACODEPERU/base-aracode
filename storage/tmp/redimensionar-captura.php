<?php

/*
 * Encoge una captura PNG (en proporción) y la guarda como JPEG, para poder
 * mirarla a ojo sin pasarse del límite de tamaño de las imágenes adjuntas.
 *
 * Uso: php storage/tmp/redimensionar-captura.php origen.png destino.jpg [ancho]
 */

$origen = $argv[1] ?? null;
$destino = $argv[2] ?? null;
$ancho = (int) ($argv[3] ?? 820);

if (! $origen || ! $destino || ! is_file($origen)) {
    fwrite(STDERR, "Falta el origen o el destino.\n");
    exit(1);
}

$imagen = imagecreatefrompng($origen);

if (! $imagen) {
    fwrite(STDERR, "No se pudo leer $origen.\n");
    exit(1);
}

$anchoOriginal = imagesx($imagen);
$altoOriginal = imagesy($imagen);
$alto = (int) round($altoOriginal * $ancho / $anchoOriginal);

$lienzo = imagecreatetruecolor($ancho, $alto);

// Fondo blanco por si el PNG trae transparencia.
imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
imagecopyresampled($lienzo, $imagen, 0, 0, 0, 0, $ancho, $alto, $anchoOriginal, $altoOriginal);

imagejpeg($lienzo, $destino, 82);

printf("%s: %dx%d -> %dx%d\n", basename($destino), $anchoOriginal, $altoOriginal, $ancho, $alto);
