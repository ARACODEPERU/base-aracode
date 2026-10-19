<?php

/*
 * Auditoría de las fotos elegidas para NÉXORA MARKET, sin vista previa.
 *
 * El panel de vista previa de este entorno no compone fotogramas, así que la
 * revisión se hace como en el resto del banco (storage/tmp/auditar-fotos.php):
 * de cada imagen se imprime su tamaño, su brillo medio, su contraste y un mapa
 * de luminancia en ASCII que deja leer la composición —si el sujeto está al
 * centro, si la foto salió plana o si el recorte propuesto se lleva el sujeto
 * fuera del encuadre—.
 *
 * Uso: php storage/tmp/nexora-auditar.php [seleccion.json]
 */

$archivo = $argv[1] ?? __DIR__.'/nexora-seleccion.json';
$seleccion = json_decode(file_get_contents($archivo), true);
$rampa = ' .:-=+*#%@';

$columnas = 16;
$filas = 4;

printf("%-26s %-9s %6s %7s  %s\n", 'destino', 'origen', 'brillo', 'contr.', 'composición');

foreach ($seleccion as $receta) {
    $ruta = sprintf('%s/%s/%03d.jpg', __DIR__, $receta['carpeta'], $receta['n']);

    if (! is_file($ruta)) {
        printf("%-26s %-9s %s\n", $receta['destino'], $receta['carpeta'].'#'.$receta['n'], 'FALTA');

        continue;
    }

    $imagen = imagecreatefromstring(file_get_contents($ruta));
    $ancho = imagesx($imagen);
    $alto = imagesy($imagen);

    $reducida = imagecreatetruecolor($columnas, $filas);
    imagecopyresampled($reducida, $imagen, 0, 0, 0, 0, $columnas, $filas, $ancho, $alto);

    $valores = [];

    for ($y = 0; $y < $filas; $y++) {
        for ($x = 0; $x < $columnas; $x++) {
            $color = imagecolorat($reducida, $x, $y);
            $valores[] = (($color >> 16 & 0xFF) * 0.299) + (($color >> 8 & 0xFF) * 0.587) + (($color & 0xFF) * 0.114);
        }
    }

    $media = array_sum($valores) / count($valores);
    $varianza = sqrt(array_sum(array_map(fn (float $v) => ($v - $media) ** 2, $valores)) / count($valores));

    // ¿Cuánto se parece el recorte propuesto a la foto entera? Un encuadre muy
    // distinto del original avisa de que el sujeto puede quedar fuera.
    $proporcion = $receta['ancho'] / $receta['alto'];
    $recorte = 'igual';

    if (abs(($ancho / $alto) - $proporcion) > 0.25) {
        $recorte = ($ancho / $alto) > $proporcion ? 'recorta arriba/abajo' : 'recorta a los lados';
    }

    printf(
        "%-26s %-9s %6.0f %7.0f  %s\n",
        $receta['destino'],
        $receta['carpeta'].'#'.$receta['n'],
        $media,
        $varianza,
        $recorte
    );

    for ($y = 0; $y < $filas; $y++) {
        $linea = '';

        for ($x = 0; $x < $columnas; $x++) {
            $linea .= $rampa[(int) floor($valores[$y * $columnas + $x] / 256 * strlen($rampa))] ?? '@';
        }

        echo '      '.$linea."\n";
    }

    imagedestroy($reducida);
    imagedestroy($imagen);
}
