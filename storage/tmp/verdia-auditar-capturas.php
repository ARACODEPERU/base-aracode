<?php

/*
 * Auditoría de las capturas de la demo «Supermercado Online — VERDIA Market».
 *
 * El panel de vista previa de esta máquina no compone imágenes, así que la
 * revisión de la composición se hace aquí: de cada captura se imprime su tamaño,
 * el brillo medio, el contraste, una medida de nitidez y un mapa de luz en ASCII
 * por zonas (la izquierda de cada fila es el borde izquierdo de la página), que
 * basta para ver dónde queda el texto, dónde la foto y si algo salió en blanco.
 *
 * Uso: php storage/tmp/verdia-auditar-capturas.php [nombre.png]
 */

$carpeta = __DIR__.'/repaso-verdia';
$solo = $argv[1] ?? null;

/** Mapa de luz: anchura y altura en celdas, pensadas para capturas apaisadas. */
function mapa($imagen, int $ancho, int $alto, int $columnas = 44, int $filas = 16): string
{
    $salida = '';

    for ($f = 0; $f < $filas; $f++) {
        $linea = '    ';

        for ($c = 0; $c < $columnas; $c++) {
            $suma = 0;
            $cuenta = 0;

            for ($y = intdiv($f * $alto, $filas); $y < intdiv(($f + 1) * $alto, $filas); $y += 4) {
                for ($x = intdiv($c * $ancho, $columnas); $x < intdiv(($c + 1) * $ancho, $columnas); $x += 4) {
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

$archivos = glob($carpeta.'/*.png');
sort($archivos);

foreach ($archivos as $ruta) {
    $nombre = basename($ruta);

    if ($solo !== null && ! str_contains($nombre, $solo)) {
        continue;
    }

    $imagen = imagecreatefrompng($ruta);
    $ancho = imagesx($imagen);
    $alto = imagesy($imagen);

    $luz = 0.0;
    $luz2 = 0.0;
    $gradiente = 0.0;
    $puntos = 0;
    $anterior = null;
    $colores = [];

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
    arsort($colores);

    printf(
        "\n%s  %dx%d  luz %.0f  contraste %.0f  nitidez %.1f  colores %s\n",
        $nombre,
        $ancho,
        $alto,
        $media,
        $desvio,
        $gradiente / max($puntos, 1),
        implode(' ', array_slice(array_keys($colores), 0, 4))
    );

    // El mapa se reserva para las capturas que se piden por nombre y para las
    // secciones de escritorio: son las que se revisan de verdad.
    if ($solo !== null || str_starts_with($nombre, '0') || str_starts_with($nombre, '1')) {
        echo mapa($imagen, $ancho, $alto);
    }

    imagedestroy($imagen);
}
