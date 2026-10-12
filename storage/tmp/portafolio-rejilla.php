<?php

/*
 * Rejilla de candidatas para revisarlas de un vistazo, con miniaturas del
 * tamaño suficiente para juzgar una foto y el número de cada una dibujado
 * encima. Es la misma idea que storage/tmp/rejilla-candidatas.php, pero por
 * tramos: con 187 candidatas una sola hoja tendría miniaturas ilegibles, así que
 * aquí se pide el tramo y la hoja cae entera en una pantalla.
 *
 * Uso: php storage/tmp/portafolio-rejilla.php <carpeta> <desde> <hasta> [salida] [columnas] [ancho]
 */

$carpeta = $argv[1] ?? 'candidatas-portafolio3';
$desde = (int) ($argv[2] ?? 1);
$hasta = (int) ($argv[3] ?? 48);
$salida = $argv[4] ?? __DIR__.'/rejilla-portafolio-'.$desde.'-'.$hasta.'.png';
$columnas = (int) ($argv[5] ?? 8);
$ancho = (int) ($argv[6] ?? 240);

$alto = (int) round($ancho * 0.72);
$datos = json_decode(file_get_contents(__DIR__.'/'.$carpeta.'/datos.json'), true);
$tramo = array_values(array_filter($datos, fn (array $fila) => $fila['n'] >= $desde && $fila['n'] <= $hasta));

$filas = (int) ceil(count($tramo) / $columnas);

$rejilla = imagecreatetruecolor($ancho * $columnas, $alto * $filas);
imagefill($rejilla, 0, 0, imagecolorallocate($rejilla, 18, 20, 24));

$blanco = imagecolorallocate($rejilla, 255, 255, 255);
$fondo = imagecolorallocate($rejilla, 0, 0, 0);

foreach ($tramo as $indice => $fila) {
    $columna = $indice % $columnas;
    $filaActual = intdiv($indice, $columnas);
    $x = $columna * $ancho;
    $y = $filaActual * $alto;

    $foto = @imagecreatefromstring(file_get_contents($fila['archivo']));

    if ($foto === false) {
        continue;
    }

    // Recorte «cover» dentro de la celda: así ninguna foto se deforma y todas
    // ocupan lo mismo, que es lo que permite compararlas de un vistazo.
    $anchoFoto = imagesx($foto);
    $altoFoto = imagesy($foto);
    $escala = max($ancho / $anchoFoto, $alto / $altoFoto);
    $destinoAncho = (int) round($anchoFoto * $escala);
    $destinoAlto = (int) round($altoFoto * $escala);

    imagecopyresampled(
        $rejilla,
        $foto,
        $x - (int) round(($destinoAncho - $ancho) / 2),
        $y - (int) round(($destinoAlto - $alto) / 2),
        0,
        0,
        $destinoAncho,
        $destinoAlto,
        $anchoFoto,
        $altoFoto
    );

    imagedestroy($foto);

    // Etiqueta con el número para poder pedir una candidata concreta.
    imagefilledrectangle($rejilla, $x + 4, $y + 4, $x + 52, $y + 22, $fondo);
    imagestring($rejilla, 5, $x + 8, $y + 6, (string) $fila['n'], $blanco);
}

imagepng($rejilla, $salida);
imagedestroy($rejilla);

printf("%s (%d candidatas, %d columnas)\n", $salida, count($tramo), $columnas);
