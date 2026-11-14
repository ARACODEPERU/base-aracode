<?php
// Mide en las capturas: dónde termina el hero (transición a blanco) y el
// recuadro de texto claro dentro de él, para ver el aire de arriba y abajo.

$paginas = ['soluciones', 'paginas-web', 'faq', 'empresa'];
$ancho = 1600;

foreach ($paginas as $p) {
    $im = imagecreatefrompng(__DIR__ . "/m-$p.png");
    $w = imagesx($im);
    $h = imagesy($im);

    // Fondo del hero en una columna sin texto ni botones.
    $xRef = (int) ($w * 0.88);

    // Busca la primera fila claramente blanca (la sección siguiente).
    $finHero = null;
    for ($y = 150; $y < $h - 12; $y++) {
        $blancas = 0;
        for ($dy = 0; $dy < 10; $dy++) {
            $c = imagecolorat($im, $xRef, $y + $dy);
            $r = ($c >> 16) & 255; $g = ($c >> 8) & 255; $b = $c & 255;
            if ($r > 240 && $g > 240 && $b > 240) {
                $blancas++;
            }
        }
        if ($blancas === 10) { $finHero = $y; break; }
    }

    // Recuadro del texto claro del hero (luminancia alta) entre y=80 y el fin.
    $limite = $finHero ?? $h;
    $minY = null; $maxY = null; $minX = null; $maxX = null;

    for ($y = 80; $y < $limite; $y++) {
        for ($x = 0; $x < $w; $x += 2) {
            $c = imagecolorat($im, $x, $y);
            $r = ($c >> 16) & 255; $g = ($c >> 8) & 255; $b = $c & 255;
            $lum = 0.299 * $r + 0.587 * $g + 0.114 * $b;
            if ($lum > 170) {
                $minY = $minY === null ? $y : $minY;
                $maxY = $y;
                $minX = $minX === null ? $x : min($minX, $x);
                $maxX = $maxX === null ? $x : max($maxX, $x);
            }
        }
    }

    $alto = $finHero;
    $arriba = $minY !== null ? $minY - 0 : null;
    $abajo = $finHero !== null && $maxY !== null ? $finHero - $maxY : null;
    $izq = $minX;
    $der = $maxX !== null ? $w - $maxX : null;

    printf(
        "%s: hero=%d  aire arriba=%s  aire abajo=%s  texto x=[%s..%s] margen izq=%s der=%s\n",
        $p, $alto, $arriba ?? '?', $abajo ?? '?', $izq ?? '?', $maxX ?? '?', $izq ?? '?', $der ?? '?'
    );
}
