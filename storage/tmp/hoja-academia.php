<?php

/*
|--------------------------------------------------------------------------
| Hoja de contacto temporal de las candidatas de la academia
|--------------------------------------------------------------------------
|
| Genera miniaturas JPEG y una hoja navegable en public/demos/_candidatas-academia/
| para elegirlas mirándolas. Ese directorio es temporal y se borra al terminar.
|
*/

$origen = __DIR__.'/candidatas-academia';
$destino = __DIR__.'/../../public/demos/_candidatas-academia';
@mkdir($destino, 0777, true);

$fichas = json_decode((string) file_get_contents($origen.'/fichas.json'), true) ?: [];
$filas = '';

foreach ($fichas as $ficha) {
    $ruta = $origen.'/'.$ficha['archivo'];

    if (! is_file($ruta)) {
        continue;
    }

    $cabecera = file_get_contents($ruta, false, null, 0, 12);
    $imagen = str_starts_with($cabecera, 'RIFF') ? @imagecreatefromwebp($ruta)
        : (str_starts_with($cabecera, "\x89PNG") ? @imagecreatefrompng($ruta) : @imagecreatefromjpeg($ruta));

    if (! $imagen) {
        echo "ILEGIBLE {$ficha['archivo']}\n";

        continue;
    }

    $w = imagesx($imagen);
    $h = imagesy($imagen);
    $thumbW = 460;
    $thumbH = (int) round($h * ($thumbW / $w));

    $lienzo = imagecreatetruecolor($thumbW, $thumbH);
    imagecopyresampled($lienzo, $imagen, 0, 0, 0, 0, $thumbW, $thumbH, $w, $h);
    imagejpeg($lienzo, $destino.'/'.$ficha['archivo'].'.jpg', 78);
    imagedestroy($imagen);
    imagedestroy($lienzo);

    $filas .= '<figure><img src="'.htmlspecialchars($ficha['archivo']).'.jpg" loading="lazy">'
        .'<figcaption><strong>'.htmlspecialchars($ficha['archivo']).'</strong> · '.$w.'×'.$h
        .'<br>'.htmlspecialchars($ficha['titulo']).'<br>'.htmlspecialchars($ficha['autor']).' · '.htmlspecialchars($ficha['licencia'])
        .' · '.htmlspecialchars($ficha['consulta']).'</figcaption></figure>';
}

file_put_contents($destino.'/index.html', '<!doctype html><html lang="es"><head><meta charset="utf-8">'
    .'<title>Candidatas para el hero de la academia</title><style>body{font:13px/1.4 system-ui;margin:0;background:#0b1220;color:#e8eefc}'
    .'h1{font-size:16px;padding:16px 20px;margin:0;background:#122040}main{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;padding:20px}'
    .'figure{margin:0;background:#16233f;border-radius:10px;overflow:hidden}img{display:block;width:100%;height:230px;object-fit:cover}'
    .'figcaption{padding:8px 10px;font-size:12px;color:#b9c8e4}</style></head><body>'
    .'<h1>'.count($fichas).' candidatas — elige por número</h1><main>'.$filas.'</main></body></html>');

echo 'hoja de contacto: public/demos/_candidatas-academia/index.html'.PHP_EOL;
