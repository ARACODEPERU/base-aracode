<?php

/*
 * Hoja de finalistas: la revisión de tamaño real de las candidatas elegidas.
 * Busca cada número en todas las carpetas de candidatas del portafolio y las
 * pinta grandes, en su proporción, para poder juzgar composición y encuadre
 * antes de recortarlas.
 *
 * Uso: php storage/tmp/portafolio-finalistas.php 803,804,807 480 [salida] [columnas]
 */

$lista = array_filter(array_map('intval', explode(',', $argv[1] ?? '')));
$ancho = (int) ($argv[2] ?? 480);
$salida = $argv[3] ?? __DIR__.'/finalistas-portafolio.png';
$columnas = (int) ($argv[4] ?? 4);

$carpetas = ['candidatas-portafolio', 'candidatas-portafolio2', 'candidatas-portafolio3', 'candidatas-portafolio-v', 'candidatas-portafolio-ov'];
$buscado = [];

foreach ($carpetas as $carpeta) {
    $ruta = __DIR__.'/'.$carpeta.'/datos.json';

    if (! is_file($ruta)) {
        continue;
    }

    foreach (json_decode(file_get_contents($ruta), true) as $fila) {
        if (in_array($fila['n'], $lista, true) && ! isset($buscado[$fila['n']])) {
            $buscado[$fila['n']] = $fila;
        }
    }
}

// El orden de la lista es el orden en la hoja: así se lee como la decisión.
$elegidas = array_values(array_filter(array_map(fn (int $n) => $buscado[$n] ?? null, $lista)));

$anchoCelda = $ancho;
$altoCelda = (int) round($ancho * 1.25);
$filas = (int) ceil(count($elegidas) / $columnas);

$hoja = imagecreatetruecolor($anchoCelda * $columnas, $altoCelda * $filas);
imagefill($hoja, 0, 0, imagecolorallocate($hoja, 18, 20, 24));

foreach ($elegidas as $indice => $fila) {
    $columna = $indice % $columnas;
    $filaActual = intdiv($indice, $columnas);
    $x = $columna * $anchoCelda;
    $y = $filaActual * $altoCelda;

    $foto = @imagecreatefromstring(file_get_contents($fila['archivo']));

    if ($foto === false) {
        continue;
    }

    $anchoFoto = imagesx($foto);
    $altoFoto = imagesy($foto);
    $escala = min($anchoCelda / $anchoFoto, $altoCelda / $altoFoto);
    $destinoAncho = (int) round($anchoFoto * $escala);
    $destinoAlto = (int) round($altoFoto * $escala);

    imagecopyresampled(
        $hoja,
        $foto,
        $x + (int) round(($anchoCelda - $destinoAncho) / 2),
        $y + (int) round(($altoCelda - $destinoAlto) / 2),
        0,
        0,
        $destinoAncho,
        $destinoAlto,
        $anchoFoto,
        $altoFoto
    );

    imagedestroy($foto);

    $etiqueta = $fila['n'].' '.$fila['medidas'];
    imagefilledrectangle($hoja, $x + 4, $y + 4, $x + 190, $y + 24, imagecolorallocate($hoja, 0, 0, 0));
    imagestring($hoja, 5, $x + 8, $y + 7, $etiqueta, imagecolorallocate($hoja, 255, 255, 255));
}

imagepng($hoja, $salida);
imagedestroy($hoja);

printf("%s (%d finalistas)\n", $salida, count($elegidas));
