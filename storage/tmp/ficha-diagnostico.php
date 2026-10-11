<?php

/*
|--------------------------------------------------------------------------
| Rastrear `diagnostico.webp`
|--------------------------------------------------------------------------
|
| Última foto del slider sin ficha documentada: se parece a una consulta con
| radiografía. Se consulta Openverse por esos temas y se compara cada miniatura
| con el archivo publicado (huella de luminancia 8×8). Se borra al terminar.
|
*/

$destino = __DIR__.'/fichas-diagnostico';
@mkdir($destino, 0777, true);

$objetivos = [
    'diagnostico' => __DIR__.'/../../public/demos/clinica-dental/img/diagnostico.webp',
];

$consultas = [
    'dental x-ray patient',
    'dentist x-ray',
    'dental radiograph',
    'dentist explaining',
    'dental consultation',
    'dentistry students patient',
    'dental panoramic',
    'dental clinic patient',

    // Sin filtro de licencia: primero se identifica la imagen y luego se
    // comprueba que la licencia sea CC0 / dominio público.
];

function huella(string $datos): ?array
{
    $img = @imagecreatefromstring($datos);

    if (! $img) {
        return null;
    }

    $m = imagecreatetruecolor(8, 8);
    imagecopyresampled($m, $img, 0, 0, 0, 0, 8, 8, imagesx($img), imagesy($img));

    $grises = [];

    for ($y = 0; $y < 8; $y++) {
        for ($x = 0; $x < 8; $x++) {
            $c = imagecolorat($m, $x, $y);
            $grises[] = (($c >> 16 & 0xFF) * 0.299) + (($c >> 8 & 0xFF) * 0.587) + (($c & 0xFF) * 0.114);
        }
    }

    $media = array_sum($grises) / count($grises);

    return array_map(fn ($v) => $v >= $media ? 1 : 0, $grises);
}

$referencia = huella(file_get_contents($objetivos['diagnostico']));

$mejores = [];

foreach ($consultas as $q) {
    $url = 'https://api.openverse.org/v1/images/?'.http_build_query([
        'q' => $q,
        'page_size' => 20,
    ]);

    $json = shell_exec('curl -s --max-time 25 -H "Accept: application/json" '.escapeshellarg($url));
    $datos = json_decode((string) $json, true);

    echo str_pad($q, 26).count($datos['results'] ?? []).' resultados'.PHP_EOL;

    foreach (($datos['results'] ?? []) as $r) {
        $miniatura = $r['thumbnail'] ?? null;

        if (! $miniatura) {
            continue;
        }

        $datosImagen = shell_exec('curl -sL --max-time 20 '.escapeshellarg($miniatura).' | base64');
        $imagen = base64_decode(trim((string) $datosImagen));

        if ($imagen === '') {
            continue;
        }

        $h = huella($imagen);

        if (! $h) {
            continue;
        }

        $d = 0;

        foreach ($h as $i => $bit) {
            $d += $bit === $referencia[$i] ? 0 : 1;
        }

        if ($d <= 12) {
            $mejores[$d][] = [
                'titulo' => $r['title'] ?? '',
                'autor' => $r['creator'] ?? '',
                'licencia' => ($r['license'] ?? '').' '.($r['license_version'] ?? ''),
                'proveedor' => $r['source'] ?? '',
                'origen' => $r['foreign_landing_url'] ?? $r['url'],
                'consulta' => $q,
            ];
        }
    }

    usleep(300000);
}

ksort($mejores);

foreach ($mejores as $d => $fichas) {
    foreach (array_slice($fichas, 0, 4) as $f) {
        echo PHP_EOL."d=$d  ".$f['licencia'].'  ['.$f['proveedor'].']'.PHP_EOL;
        echo '  '.$f['titulo'].' — '.$f['autor'].PHP_EOL;
        echo '  '.$f['origen'].PHP_EOL;
    }
}

if (! $mejores) {
    echo PHP_EOL.'Sin coincidencias por debajo de d=12.'.PHP_EOL;
}
