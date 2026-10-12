<?php

/*
 * Revisa por números los dos planos capturados (claro y oscuro): escala cada uno
 * a 78 × 30 celdas y lo dibuja en ASCII, además de resumir su luminancia.
 * Sirve para saber si el dibujo invertido conserva trazos, calles y rótulos.
 *
 * Uso: php storage/tmp/repaso-plano.php
 */

const RUTAS = [
    'claro' => __DIR__.'/repaso/plano-claro.png',
    'oscuro' => __DIR__.'/repaso/plano-oscuro.png',
];

const RAMPA = ' .:-=+*#%@';

$ancho = 78;
$alto = 30;

foreach (RUTAS as $nombre => $ruta) {
    if (! is_file($ruta)) {
        fwrite(STDERR, "falta {$ruta}\n");
        exit(1);
    }

    $imagen = imagecreatefrompng($ruta);
    $anchoReal = imagesx($imagen);
    $altoReal = imagesy($imagen);

    $celdas = [];
    $valores = [];

    for ($fila = 0; $fila < $alto; $fila++) {
        $linea = '';

        for ($columna = 0; $columna < $ancho; $columna++) {
            $x0 = (int) ($columna * $anchoReal / $ancho);
            $x1 = (int) (($columna + 1) * $anchoReal / $ancho);
            $y0 = (int) ($fila * $altoReal / $alto);
            $y1 = (int) (($fila + 1) * $altoReal / $alto);

            $suma = 0;
            $cuenta = 0;

            for ($y = $y0; $y < $y1; $y++) {
                for ($x = $x0; $x < $x1; $x++) {
                    $color = imagecolorat($imagen, $x, $y);
                    $gris = (($color >> 16 & 0xFF) * 0.299)
                        + (($color >> 8 & 0xFF) * 0.587)
                        + (($color & 0xFF) * 0.114);

                    $suma += $gris;
                    $cuenta++;
                    $valores[] = $gris;
                }
            }

            $media = $suma / max(1, $cuenta);
            $linea .= RAMPA[(int) min(9, round($media / 25.5))];
        }

        $celdas[] = $linea;
    }

    sort($valores);

    $percentil = fn (float $p) => $valores[(int) min(count($valores) - 1, floor($p * count($valores)))];

    printf("=== %s (%d × %d) ===\n", strtoupper($nombre), $anchoReal, $altoReal);
    printf(
        "gris: mínimo %.0f · p10 %.0f · mediana %.0f · p90 %.0f · máximo %.0f · media %.0f\n",
        $valores[0],
        $percentil(0.10),
        $percentil(0.50),
        $percentil(0.90),
        end($valores),
        array_sum($valores) / count($valores)
    );

    // Rango entre el décimo y el nonagésimo percentil: mide cuánto se separan
    // las superficies del fondo frente a los trazos y los rótulos.
    printf("separación p10–p90: %.0f niveles de gris\n\n", $percentil(0.90) - $percentil(0.10));

    foreach ($celdas as $linea) {
        echo $linea."\n";
    }

    echo "\n";
}
