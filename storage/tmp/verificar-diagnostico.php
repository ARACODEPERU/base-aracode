<?php

/*
 * ¿`diagnostico.webp` es la misma foto que la ficha CC0 «Lilongwe, Malawi -
 * Col. Sheldon» de rawpixel? Se compara la huella de luminancia con la
 * miniatura de esa ficha, más una búsqueda por el mismo título.
 */

function huella(string $datos): ?array
{
    $img = @imagecreatefromstring($datos);

    if (! $img) {
        return null;
    }

    $m = imagecreatetruecolor(8, 8);
    imagecopyresampled($m, $img, 0, 0, 0, 0, 8, 8, imagesx($img), imagesy($img));

    $g = [];

    for ($y = 0; $y < 8; $y++) {
        for ($x = 0; $x < 8; $x++) {
            $c = imagecolorat($m, $x, $y);
            $g[] = (($c >> 16 & 0xFF) * 0.299) + (($c >> 8 & 0xFF) * 0.587) + (($c & 0xFF) * 0.114);
        }
    }

    $media = array_sum($g) / count($g);

    return array_map(fn ($v) => $v >= $media ? 1 : 0, $g);
}

function api(string $url): array
{
    $contexto = stream_context_create(['http' => [
        'timeout' => 30,
        'header' => "User-Agent: AracodePlantillas/1.0\r\nAccept: application/json\r\n",
    ]]);

    $cuerpo = @file_get_contents($url, false, $contexto);

    return $cuerpo ? (json_decode($cuerpo, true)['results'] ?? []) : [];
}

$referencia = huella(file_get_contents(__DIR__.'/../../public/demos/clinica-dental/img/diagnostico.webp'));

$consultas = [
    'Lilongwe, Malawi - Col. Sheldon',
    'dental x-ray',
];

foreach ($consultas as $q) {
    echo '== '.$q.PHP_EOL;

    $resultados = api('https://api.openverse.org/v1/images/?'.http_build_query([
        'q' => $q,
        'license' => 'cc0,pdm',
        'page_size' => 20,
    ]));

    foreach ($resultados as $r) {
        $miniatura = $r['thumbnail'] ?? null;

        if (! $miniatura) {
            continue;
        }

        $contexto = stream_context_create(['http' => ['timeout' => 25, 'header' => "User-Agent: AracodePlantillas/1.0\r\n"]]);
        $imagen = @file_get_contents($miniatura, false, $contexto);
        $h = $imagen ? huella($imagen) : null;

        if (! $h) {
            continue;
        }

        $d = 0;

        foreach ($h as $i => $bit) {
            $d += $bit === $referencia[$i] ? 0 : 1;
        }

        if ($d > 12) {
            continue;
        }

        printf("  d=%-2d %s | %-22s | %s".PHP_EOL.'     %s'.PHP_EOL,
            $d,
            ($r['license'] ?? '').' '.($r['license_version'] ?? ''),
            mb_substr((string) ($r['source'] ?? ''), 0, 22),
            mb_substr((string) ($r['title'] ?? ''), 0, 60),
            (string) ($r['foreign_landing_url'] ?? $r['url'] ?? '')
        );
    }
}
