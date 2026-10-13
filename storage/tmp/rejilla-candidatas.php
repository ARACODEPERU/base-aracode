<?php

/*
 * Arma una rejilla con todas las candidatas de una carpeta para revisarlas de
 * un vistazo (en la sesión el panel de vista previa no siempre compone
 * fotogramas, y así con una sola captura se ven todas).
 *
 * Uso: php storage/tmp/rejilla-candidatas.php <carpeta> [salida] [columnas]
 */

$carpeta = $argv[1] ?? 'candidatas-inmobiliaria2';
$salida = $argv[2] ?? __DIR__.'/rejilla-'.$carpeta.'.png';
$columnas = (int) ($argv[3] ?? 9);

$datos = json_decode(file_get_contents(__DIR__.'/'.$carpeta.'/datos.json'), true);

$ancho = 160;
$alto = 120;
$filas = (int) ceil(count($datos) / $columnas);

$rejilla = imagecreatetruecolor($ancho * $columnas, $alto * $filas);
imagefill($rejilla, 0, 0, imagecolorallocate($rejilla, 18, 20, 24));

$blanco = imagecolorallocate($rejilla, 255, 255, 255);
$fondoEtiqueta = imagecolorallocate($rejilla, 20, 24, 29);

foreach ($datos as $indice => $fila) {
    $columna = $indice % $columnas;
    $filaActual = intdiv($indice, $columnas);
    $x = $columna * $ancho;
    $y = $filaActual * $alto;

    $foto = @imagecreatefromjpeg($fila['archivo']);

    if ($foto === false) {
        continue;
    }

    $anchoFoto = imagesx($foto);
    $altoFoto = imagesy($foto);
    $escala = min(($ancho - 4) / $anchoFoto, ($alto - 4) / $altoFoto);
    $destinoAncho = (int) round($anchoFoto * $escala);
    $destinoAlto = (int) round($altoFoto * $escala);

    imagecopyresampled(
        $rejilla,
        $foto,
        $x + (int) (($ancho - $destinoAncho) / 2),
        $y + (int) (($alto - $destinoAlto) / 2),
        0,
        0,
        $destinoAncho,
        $destinoAlto,
        $anchoFoto,
        $altoFoto
    );

    imagedestroy($foto);

    imagestring($rejilla, 5, $x + 6, $y + 4, (string) $fila['n'], $blanco);
}

imagepng($rejilla, $salida);
imagedestroy($rejilla);

echo $salida.' ('.$columnas.' columnas, '.$filas." filas)\n";
