<?php

/*
 * Hoja de contactos de las candidatas de la tienda de hogar.
 *
 * El panel de vista previa solo sirve archivos bajo public/, así que esta hoja
 * vive ahí mientras dura la revisión: miniaturas de 260 px de ancho, con el
 * número, el grupo y la búsqueda de cada candidata, agrupadas por grupo.
 *
 * Uso: php storage/tmp/casa-rejilla.php <carpeta-candidatas> <destino-public>
 */

$carpeta = $argv[1] ?? 'candidatas-casa3';
$destino = $argv[2] ?? 'tmp-candidatas';

$origen = __DIR__.'/'.$carpeta;
$filas = json_decode(file_get_contents($origen.'/datos.json'), true);
$salida = __DIR__.'/../../public/'.$destino;

if (! is_dir($salida)) {
    mkdir($salida, 0777, true);
}

$grupos = [];

foreach ($filas as $fila) {
    $grupos[$fila['grupo']][] = $fila;
}

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Rejilla '.$carpeta.'</title>'
    .'<style>body{font:13px system-ui;background:#101010;color:#eee;margin:16px}'
    .'h2{font-size:15px;margin:22px 0 10px}'
    .'.rejilla{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}'
    .'figure{margin:0;background:#1c1c1c;border-radius:6px;overflow:hidden}'
    .'img{width:100%;height:180px;object-fit:cover;display:block}'
    .'figcaption{padding:5px 7px;font-size:11px;line-height:1.35}'
    .'b{color:#ffd79a}em{color:#9fd3a0;font-style:normal}</style></head><body>';

foreach ($grupos as $grupo => $lista) {
    $html .= '<h2>'.$grupo.' · '.count($lista).'</h2><div class="rejilla">';

    foreach ($lista as $fila) {
        $miniatura = $salida.'/'.$fila['n'].'.jpg';
        $imagen = imagecreatefromstring(file_get_contents($fila['archivo']));
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $nuevoAncho = 320;
        $nuevoAlto = (int) round($alto * ($nuevoAncho / $ancho));
        $lienzo = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        imagecopyresampled($lienzo, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
        imagejpeg($lienzo, $miniatura, 78);
        imagedestroy($lienzo);
        imagedestroy($imagen);

        $html .= sprintf(
            '<figure><img src="%d.jpg" alt="candidata %d" loading="lazy"><figcaption><b>#%d</b> · %s × %s<br><em>%s</em><br>%s</figcaption></figure>',
            $fila['n'],
            $fila['n'],
            $fila['n'],
            $fila['orientacion'] === 'horizontal' ? '↔' : ($fila['orientacion'] === 'vertical' ? '↕' : '□'),
            $fila['medidas'],
            htmlspecialchars($fila['busqueda']),
            htmlspecialchars(substr(str_replace('File:', '', $fila['titulo']), 0, 54))
        );
    }

    $html .= '</div>';
}

file_put_contents($salida.'/rejilla.html', $html.'</body></html>');

echo count($filas)." miniaturas en public/{$destino}/rejilla.html\n";
