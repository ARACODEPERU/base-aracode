<?php

/*
 * Identifica de qué ficha de Openverse salieron las fotos de clinica-dental.
 *
 * La carpeta temporal de candidatas se borró, así que en vez de adivinar los
 * identificadores se vuelve a consultar la API pública de Openverse y se
 * comparan las miniaturas con los archivos publicados usando un hash de
 * luminancia 8×8 (distancia de Hamming). La coincidencia más parecida es la
 * ficha del original.
 */

$destino = __DIR__.'/fichas-clinica';
@mkdir($destino, 0777, true);

// Fotos publicadas que queremos rastrear.
$objetivos = [
    'portada' => __DIR__.'/../../public/demos/clinica-dental/img/portada.webp',
    'consulta' => __DIR__.'/../../public/demos/clinica-dental/img/consulta.webp',
    'diagnostico' => __DIR__.'/../../public/demos/clinica-dental/img/diagnostico.webp',
];

function hashImagen(string $ruta): ?array
{
    $img = @imagecreatefromstring(file_get_contents($ruta));
    if (! $img) {
        return null;
    }

    $muestra = imagecreatetruecolor(8, 8);
    imagecopyresampled($muestra, $img, 0, 0, 0, 0, 8, 8, imagesx($img), imagesy($img));

    $valores = [];
    for ($y = 0; $y < 8; $y++) {
        for ($x = 0; $x < 8; $x++) {
            $color = imagecolorat($muestra, $x, $y);
            $valores[] = (($color >> 16 & 0xFF) * 0.299) + (($color >> 8 & 0xFF) * 0.587) + (($color & 0xFF) * 0.114);
        }
    }

    $media = array_sum($valores) / count($valores);

    return array_map(fn ($v) => $v >= $media ? 1 : 0, $valores);
}

function distancia(array $a, array $b): int
{
    $d = 0;
    foreach ($a as $i => $bit) {
        $d += $bit === $b[$i] ? 0 : 1;
    }

    return $d;
}

$hashes = [];
foreach ($objetivos as $nombre => $ruta) {
    $hashes[$nombre] = hashImagen($ruta);
}

$consultas = ['dental clinic', 'dental office', 'dentist chair', 'dental treatment', 'dental instruments clinic'];

$candidatas = [];

foreach ($consultas as $q) {
    $url = 'https://api.openverse.org/v1/images/?'.http_build_query([
        'q' => $q,
        'license' => 'cc0',
        'page_size' => 20,
    ]);

    $contexto = stream_context_create(['http' => ['timeout' => 30, 'header' => "User-Agent: AracodePlantillas/1.0\r\n"]]);
    $cuerpo = @file_get_contents($url, false, $contexto);
    if (! $cuerpo) {
        echo "Sin respuesta para «$q»\n";

        continue;
    }

    foreach (json_decode($cuerpo, true)['results'] ?? [] as $f) {
        $candidatas[$f['id']] = $f;
    }

    usleep(400000);
}

echo 'Fichas recuperadas: '.count($candidatas)."\n\n";

$descargadas = 0;

foreach ($candidatas as $id => $f) {
    $miniatura = $f['thumbnail'] ?? $f['url'] ?? null;
    if (! $miniatura) {
        continue;
    }

    $contexto = stream_context_create(['http' => ['timeout' => 30, 'header' => "User-Agent: AracodePlantillas/1.0\r\n"]]);
    $datos = @file_get_contents($miniatura, false, $contexto);
    if (! $datos) {
        continue;
    }

    $archivo = $destino.'/'.substr(md5($id), 0, 8).'.img';
    file_put_contents($archivo, $datos);
    $hash = hashImagen($archivo);

    if (! $hash) {
        continue;
    }

    $descargadas++;

    foreach ($objetivos as $nombre => $ruta) {
        if (! $hashes[$nombre]) {
            continue;
        }

        $d = distancia($hashes[$nombre], $hash);
        if ($d <= 12) {
            printf("≈ %-12s d=%-2d | %s | %s | %s | %s\n",
                $nombre,
                $d,
                $f['title'] ?: '(sin título)',
                $f['creator'] ?: '(sin autor)',
                $f['license'].' '.($f['license_version'] ?? ''),
                $f['foreign_landing_url'] ?? $f['url']
            );
        }
    }
}

echo "\nMiniaturas comparadas: $descargadas\n";
echo "Detalle completo en fichas-clinica.json\n";

file_put_contents($destino.'.json', json_encode($candidatas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
