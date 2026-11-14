<?php
// Une las capturas de las páginas internas en una rejilla 2x4 para revisarlas juntas.
$paginas = ['soluciones', 'empresa', 'contacto', 'blog', 'faq', 'precios', 'paginas-web', 'politica-privacidad'];
$k = 0.5;
$fuentes = [];

foreach ($paginas as $p) {
    $ruta = __DIR__ . '/ph-' . $p . '.png';
    $fuentes[$p] = imagecreatefrompng($ruta);
}

$w = (int) (imagesx($fuentes['soluciones']) * $k);
$h = (int) (imagesy($fuentes['soluciones']) * $k);
$lienzo = imagecreatetruecolor($w * 2 + 12, $h * 4 + 18);
imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 30, 30, 30));

foreach ($paginas as $i => $p) {
    $x = ($i % 2) * ($w + 4) + 2;
    $y = intdiv($i, 2) * ($h + 4) + 2;
    imagecopyresampled($lienzo, $fuentes[$p], $x, $y, 0, 0, $w, $h, imagesx($fuentes[$p]), imagesy($fuentes[$p]));
}

imagepng($lienzo, __DIR__ . '/ph-montaje.png');
echo 'OK ' . imagesx($lienzo) . 'x' . imagesy($lienzo) . "\n";
