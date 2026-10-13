<?php

/*
 * Sondeo rápido de términos en Commons: para cada búsqueda imprime los títulos
 * que devuelve, sin descargar nada. Sirve para elegir los términos buenos antes
 * de lanzar la ronda de candidatas (que sí descarga).
 *
 * Uso: php storage/tmp/portafolio-sondear.php "termino" "otro termino" ...
 */

const AGENTE = 'ARACODE-BancoPlantillas/1.0 (demo de plantilla; soporte@aracodeperu.com)';

function consultar(string $url): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => AGENTE,
    ]);
    $cuerpo = curl_exec($ch);
    $estado = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($cuerpo === false || $estado !== 200) ? null : json_decode($cuerpo, true);
}

foreach (array_slice($argv, 1) as $busqueda) {
    $lista = consultar('https://commons.wikimedia.org/w/api.php?'.http_build_query([
        'action' => 'query',
        'format' => 'json',
        'generator' => 'search',
        'gsrsearch' => 'filetype:bitmap '.$busqueda,
        'gsrnamespace' => 6,
        'gsrlimit' => 14,
        'prop' => 'imageinfo',
        'iiprop' => 'url|size|extmetadata',
        'iiurlwidth' => 1280,
    ]));

    $titulos = [];

    foreach ($lista['query']['pages'] ?? [] as $pagina) {
        $info = $pagina['imageinfo'][0] ?? null;
        $meta = $info['extmetadata'] ?? [];
        $licencia = trim(strip_tags($meta['LicenseShortName']['value'] ?? ''));

        if (! preg_match('/^(CC0|Public domain|PD|CC PD)/i', $licencia)) {
            continue;
        }

        $titulos[] = sprintf(
            '%s  [%dx%d %s]',
            str_replace('File:', '', $pagina['title']),
            (int) ($info['width'] ?? 0),
            (int) ($info['height'] ?? 0),
            $licencia
        );
    }

    printf("\n== %s (%d) ==\n", $busqueda, count($titulos));

    foreach ($titulos as $titulo) {
        echo '  '.$titulo."\n";
    }
}
