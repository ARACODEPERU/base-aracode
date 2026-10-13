<?php

/*
 * Auditoría de las candidatas de la tienda.
 *
 * El panel de vista previa no compone fotogramas en esta máquina, así que la
 * elección de las fotos se hace con números y no a ojo: de cada candidata se
 * imprime su tamaño, el brillo medio, el contraste, una medida de nitidez (el
 * cuadrado medio del gradiente: una foto desenfocada la da baja), los tres
 * colores dominantes y un mapa de luz en ASCII por si hay que mirar dónde está
 * el sujeto. Lo que se busca es lo del banco: foto clara, con contraste y con el
 * sujeto reconocible.
 *
 * Uso: php storage/tmp/casa-auditar.php <carpeta> [n,n,n...]
 */

$carpeta = $argv[1] ?? 'candidatas-casa3';
$elegidas = isset($argv[2]) ? array_map('intval', explode(',', $argv[2])) : null;

$filas = json_decode(file_get_contents(__DIR__.'/'.$carpeta.'/datos.json'), true);

/** Mapa de luz en ASCII: 15 columnas por 8 filas. */
function mapa($imagen, $ancho, $alto): string
{
    $columnas = 15;
    $filas = 8;
    $salida = '';

    for ($f = 0; $f < $filas; $f++) {
        $linea = '    ';

        for ($c = 0; $c < $columnas; $c++) {
            $suma = 0;
            $cuenta = 0;

            for ($y = intdiv($f * $alto, $filas); $y < intdiv(($f + 1) * $alto, $filas); $y += 3) {
                for ($x = intdiv($c * $ancho, $columnas); $x < intdiv(($c + 1) * $ancho, $columnas); $x += 3) {
                    $color = imagecolorat($imagen, $x, $y);
                    $suma += (($color >> 16 & 255) * 0.299 + ($color >> 8 & 255) * 0.587 + ($color & 255) * 0.114);
                    $cuenta++;
                }
            }

            $luz = $cuenta ? $suma / $cuenta : 0;
            $linea .= ' .:-=+*#%@'[min(9, (int) ($luz / 25.6))];
        }

        $salida .= $linea."\n";
    }

    return $salida;
}

foreach ($filas as $fila) {
    if ($elegidas !== null && ! in_array($fila['n'], $elegidas, true)) {
        continue;
    }

    $imagen = imagecreatefromstring(file_get_contents($fila['archivo']));
    $ancho = imagesx($imagen);
    $alto = imagesy($imagen);

    // Se muestrea una rejilla de 60x40 puntos para los promedios.
    $luz = 0.0;
    $luz2 = 0.0;
    $gradiente = 0.0;
    $puntos = 0;
    $colores = [];
    $anterior = null;

    for ($y = 4; $y < $alto - 4; $y += max(1, intdiv($alto, 40))) {
        for ($x = 4; $x < $ancho - 4; $x += max(1, intdiv($ancho, 60))) {
            $color = imagecolorat($imagen, $x, $y);
            $r = $color >> 16 & 255;
            $g = $color >> 8 & 255;
            $b = $color & 255;
            $luminancia = $r * 0.299 + $g * 0.587 + $b * 0.114;

            $luz += $luminancia;
            $luz2 += $luminancia * $luminancia;
            $puntos++;

            if ($anterior !== null) {
                $gradiente += abs($luminancia - $anterior);
            }

            $anterior = $luminancia;

            $clave = sprintf('%02x%02x%02x', $r >> 4 << 4, $g >> 4 << 4, $b >> 4 << 4);
            $colores[$clave] = ($colores[$clave] ?? 0) + 1;
        }
    }

    $media = $luz / max($puntos, 1);
    $desvio = sqrt(max(0, $luz2 / max($puntos, 1) - $media * $media));
    $nitidez = $gradiente / max($puntos, 1);
    arsort($colores);

    printf(
        "\n#%03d  %s  %dx%d  luz %.0f  contraste %.0f  nitidez %.1f  colores %s\n     %s\n",
        $fila['n'],
        $fila['grupo'],
        $ancho,
        $alto,
        $media,
        $desvio,
        $nitidez,
        implode(' ', array_slice(array_keys($colores), 0, 4)),
        substr(str_replace('File:', '', $fila['titulo']), 0, 80)
    );

    if ($elegidas !== null) {
        echo mapa($imagen, $ancho, $alto);
    }

    imagedestroy($imagen);
}
