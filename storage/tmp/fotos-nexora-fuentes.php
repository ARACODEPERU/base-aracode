<?php

/*
 * Buscador de fotos para la demo «NÉXORA MARKET» del Banco de Plantillas Web.
 *
 * Consulta las dos fuentes que ya usa el banco —Wikimedia Commons
 * (storage/tmp/fotos-verdia-commons.php) y la API de Openverse
 * (storage/tmp/fotos-portafolio-openverse.php)— porque ninguna de las dos basta
 * sola para un catálogo de comercio: Commons tiene poco producto moderno con
 * licencia sin obligaciones y Openverse no siempre trae las medidas que pide el
 * recorte. Se filtra por licencias sin obligaciones (CC0 / dominio público),
 * que es la regla de CREDITOS.md.
 *
 * Grupos y sus exigencias de tamaño:
 *   - horizontal  portada y bandas: ancha y con altura suficiente.
 *   - vertical    la foto de la sección de confianza: el lado largo es el alto.
 *   - producto    las piezas del catálogo: casi cuadradas o algo anchas.
 *   - rubro       las seis tarjetas de categoría (4/3).
 *   - revista     las tres guías del blog (3/2).
 *
 * Licencias admitidas: las del estándar del banco —sin obligaciones (CC0 /
dominio público) o con atribución simple (CC BY)—. Quedan fuera las que añaden
condiciones que no se pueden cumplir en una plantilla que se revende: compartir
igual (SA), no comercial (NC) y sin obra derivada (ND); el recorte y el reescalado
a WebP son obra derivada. El autor y la ficha de cada foto quedan en CREDITOS.md.
 *
 * Uso: php storage/tmp/fotos-nexora-fuentes.php <temas.json> [inicio] [carpeta] [fuentes]
 * Salida: storage/tmp/<carpeta>/{n}.jpg + datos.json + hoja.html
 */

const AGENTE = 'ARACODE-BancoPlantillas/1.0 (demo de plantilla; soporte@aracodeperu.com)';
const ANCHO_COMONS = 1280;

// Openverse entrega a veces el original (100 MB, 6000 px): reescalarlo necesita
// bastante más memoria que el límite de CLI por defecto, y sin esto la imagen
// gigante se quedaba tal cual en la carpeta de candidatas.
ini_set('memory_limit', '1024M');

// Tope por término y por fuente: las búsquedas traen mucho repetido y bajar cien
// fotos para descartar noventa no vale el tiempo ni el disco.
const MAX_POR_BUSQUEDA = 3;
const MAX_LADO = 1600;

$archivoTemas = $argv[1] ?? __DIR__.'/nexora-temas.json';
$inicio = (int) ($argv[2] ?? 0);
$carpeta = $argv[3] ?? 'candidatas-nexora';
$fuentes = isset($argv[4]) ? explode(',', $argv[4]) : ['commons', 'openverse'];

define('SALIDA', __DIR__.'/'.$carpeta);

$temas = json_decode(file_get_contents($archivoTemas), true);

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

function descargar(string $url, string $destino): bool
{
    $ch = curl_init($url);
    $fh = fopen($destino, 'wb');
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fh,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_USERAGENT => AGENTE,
    ]);
    $ok = curl_exec($ch);
    $estado = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fh);

    if (! $ok || $estado !== 200 || filesize($destino) < 40000) {
        return false;
    }

    // La API de Openverse a veces entrega el original enorme (5000 px): se
    // reescala aquí para no arrastrar archivos de 20 MB por todo el proceso.
    $medidas = @getimagesize($destino);

    if ($medidas === false) {
        unlink($destino);

        return false;
    }

    // Solo formatos que el recorte y el WebP pueden leer. Openverse también
    // devuelve TIFF y otros archivos que `getimagesize` reconoce pero GD no
    // abre: se descartan aquí en vez de dejarlos en la carpeta de candidatas.
    if (! in_array($medidas[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        unlink($destino);

        return false;
    }

    if (max($medidas[0], $medidas[1]) > MAX_LADO) {
        $origen = imagecreatefromstring(file_get_contents($destino));

        if ($origen !== false) {
            $factor = MAX_LADO / max($medidas[0], $medidas[1]);
            $ancho = (int) round($medidas[0] * $factor);
            $alto = (int) round($medidas[1] * $factor);
            $lienzo = imagecreatetruecolor($ancho, $alto);
            imagecopyresampled($lienzo, $origen, 0, 0, 0, 0, $ancho, $alto, $medidas[0], $medidas[1]);
            imagedestroy($origen);
            imagejpeg($lienzo, $destino, 88);
            imagedestroy($lienzo);
        }
    }

    return true;
}

/**
 * ¿La licencia sirve? Sin obligaciones o con atribución simple; nunca SA/NC/ND.
 */
function licenciaApta(string $licencia): bool
{
    if (preg_match('/^(CC0|Public domain|PD|CC PD)/i', $licencia)) {
        return true;
    }

    if (! preg_match('/^CC BY(\s|-)?(\d|$)/i', $licencia)) {
        return false;
    }

    return ! preg_match('/(SA|NC|ND)/i', $licencia);
}

/**
 * Descartar gráficos planos: en una tarjeta de producto se nota el pegote de un
 * clipart junto a una fotografía real.
 */
function esGraficoPlano(string $titulo, string $grupo): bool
{
    if ($grupo === 'horizontal') {
        return false;
    }

    return (bool) preg_match('/clipart|sticker|silhouette|icon|logo|vector|drawing|engraving|sketch/i', $titulo);
}

/** ¿La foto sirve para el grupo pedido, por proporción y por tamaño? */
function encaja(string $grupo, int $ancho, int $alto): bool
{
    $relacion = $ancho / max($alto, 1);

    if ($grupo === 'vertical') {
        return $relacion <= 0.85 && $alto >= 1400;
    }

    if ($grupo === 'producto') {
        return $relacion >= 0.85 && $relacion <= 1.5 && $ancho >= 1000 && $alto >= 900;
    }

    return $relacion >= 1.15 && $ancho >= 1200 && $alto >= 750;
}

/** Ficha de Commons: el título y el autor vienen con etiquetas HTML. */
function limpiar(?string $texto): string
{
    return trim(preg_replace('/\s+/', ' ', strip_tags((string) $texto)));
}

function deCommons(array $temas): array
{
    $filas = [];
    $vistos = [];

    foreach ($temas as $grupo => $busquedas) {
        foreach ($busquedas as $busqueda) {
            $lista = consultar('https://commons.wikimedia.org/w/api.php?'.http_build_query([
                'action' => 'query',
                'format' => 'json',
                'generator' => 'search',
                'gsrsearch' => 'filetype:bitmap '.$busqueda,
                'gsrnamespace' => 6,
                'gsrlimit' => 30,
                'prop' => 'imageinfo',
                'iiprop' => 'url|size|extmetadata',
                'iiurlwidth' => ANCHO_COMONS,
            ]));

            $bajadas = 0;

            foreach ($lista['query']['pages'] ?? [] as $pagina) {
                $info = $pagina['imageinfo'][0] ?? null;

                if ($info === null || isset($vistos[$pagina['title']]) || $bajadas >= MAX_POR_BUSQUEDA) {
                    continue;
                }

                $meta = $info['extmetadata'] ?? [];
                $licencia = limpiar($meta['LicenseShortName']['value'] ?? '');
                $ancho = (int) ($info['width'] ?? 0);
                $alto = (int) ($info['height'] ?? 0);

                if (! licenciaApta($licencia)) {
                    continue;
                }

                if (esGraficoPlano($pagina['title'] ?? '', $grupo)) {
                    continue;
                }

                if (! encaja($grupo, $ancho, $alto)) {
                    continue;
                }

                $vistos[$pagina['title']] = true;
                $bajadas++;

                $filas[] = [
                    'grupo' => $grupo,
                    'busqueda' => $busqueda,
                    'fuente' => 'Wikimedia Commons',
                    'titulo' => limpiar($pagina['title'] ?? ''),
                    'autor' => limpiar($meta['Artist']['value'] ?? ''),
                    'licencia' => $licencia,
                    'ficha' => $info['descriptionurl'] ?? '',
                    'original' => $ancho.'x'.$alto,
                    'url' => $info['thumburl'] ?? $info['url'],
                ];
            }
        }
    }

    return $filas;
}

function deOpenverse(array $temas): array
{
    $filas = [];
    $vistos = [];

    foreach ($temas as $grupo => $busquedas) {
        foreach ($busquedas as $busqueda) {
            $respuesta = consultar('https://api.openverse.org/v1/images/?'.http_build_query([
                'q' => $busqueda,
                // Sin obligaciones o con atribución simple: ver licenciaApta().
                'license' => 'cc0,pdm,by',
                'size' => 'large',
                'page_size' => 20,
            ]));

            $bajadas = 0;

            foreach ($respuesta['results'] ?? [] as $item) {
                $ancho = (int) ($item['width'] ?? 0);
                $alto = (int) ($item['height'] ?? 0);
                $clave = $item['foreign_landing_url'] ?? $item['id'] ?? '';

                if ($clave === '' || isset($vistos[$clave]) || $bajadas >= MAX_POR_BUSQUEDA) {
                    continue;
                }

                if (! in_array($item['license'] ?? '', ['cc0', 'pdm', 'by'], true)) {
                    continue;
                }

                if (esGraficoPlano((string) ($item['title'] ?? ''), $grupo)) {
                    continue;
                }

                if (! encaja($grupo, $ancho, $alto)) {
                    continue;
                }

                $vistos[$clave] = true;
                $bajadas++;

                $filas[] = [
                    'grupo' => $grupo,
                    'busqueda' => $busqueda,
                    'fuente' => 'Openverse',
                    'titulo' => limpiar($item['title'] ?? ''),
                    'autor' => limpiar($item['creator'] ?? ''),
                    'licencia' => strtoupper($item['license'] ?? ''),
                    'ficha' => $clave,
                    'original' => $ancho.'x'.$alto,
                    'url' => $item['url'] ?? '',
                ];
            }
        }
    }

    return $filas;
}

if (! is_dir(SALIDA)) {
    mkdir(SALIDA, 0777, true);
}

$candidatas = [];

if (in_array('commons', $fuentes, true)) {
    $candidatas = array_merge($candidatas, deCommons($temas));
}

if (in_array('openverse', $fuentes, true)) {
    $candidatas = array_merge($candidatas, deOpenverse($temas));
}

$indice = $inicio;
$filas = [];
$descartadas = [];

foreach ($candidatas as $candidata) {
    if ($candidata['url'] === '') {
        continue;
    }

    $indice++;
    $archivo = sprintf('%s/%03d.jpg', SALIDA, $indice);

    if (! descargar($candidata['url'], $archivo)) {
        $indice--;
        $descartadas[] = $candidata['titulo'];

        continue;
    }

    $medidas = getimagesize($archivo);

    // La medida anunciada no siempre es la que llega: manda el archivo.
    if ($medidas === false || ! encaja($candidata['grupo'], $medidas[0], $medidas[1])) {
        unlink($archivo);
        $indice--;
        $descartadas[] = $candidata['titulo'];

        continue;
    }

    $filas[] = $candidata + [
        'n' => $indice,
        'archivo' => $archivo,
        'medidas' => $medidas[0].'x'.$medidas[1],
        'bytes' => filesize($archivo),
    ];
}

file_put_contents(SALIDA.'/datos.json', json_encode($filas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Candidatas NÉXORA</title>'
    .'<style>body{font:14px system-ui;background:#111;color:#eee;margin:20px}figure{margin:0 0 24px;border-bottom:1px solid #444;padding-bottom:16px}'
    .'img{max-width:520px;display:block;margin-bottom:8px}code{color:#ffd79a}</style></head><body><h1>Candidatas NÉXORA Market</h1>';

foreach ($filas as $fila) {
    $html .= sprintf(
        '<figure id="c%1$03d"><img src="%1$03d.jpg" alt="%2$s" loading="lazy"><figcaption><strong>#%1$03d</strong> · %3$s · %4$s · %5$s · %6$s<br>%7$s · %2$s · %8$s<br><code>%9$s</code></figcaption></figure>',
        $fila['n'],
        htmlspecialchars($fila['busqueda']),
        htmlspecialchars($fila['grupo']),
        htmlspecialchars($fila['medidas']),
        htmlspecialchars($fila['licencia']),
        htmlspecialchars($fila['fuente']),
        htmlspecialchars($fila['titulo']),
        htmlspecialchars($fila['autor']),
        htmlspecialchars($fila['ficha'])
    );
}

file_put_contents(SALIDA.'/hoja.html', $html.'</body></html>');

printf("%d candidatas guardadas en %s\n", count($filas), $carpeta);

if ($descartadas) {
    printf("%d descartadas en la descarga:\n - %s\n", count($descartadas), implode("\n - ", array_slice($descartadas, 0, 15)));
}
