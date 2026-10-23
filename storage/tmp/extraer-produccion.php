<?php

/**
 * Extrae el contenido público de la landing real (avc.pichanguero.com) a JSON:
 * título, formato, equipos con su tabla, premios, contacto y partidos.
 * Sirve para revisar la nueva piel con los textos y nombres de verdad.
 */

$html = file_get_contents(__DIR__ . '/prod-landing.html');

$doc = new DOMDocument();
@$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
$xp = new DOMXPath($doc);

$texto = static fn (?DOMNode $n): string => trim(preg_replace('/\s+/u', ' ', $n?->textContent ?? ''));

$h1 = $xp->query('//h1[contains(@class,"se-hero__title")]')->item(0);
$desc = $xp->query('//*[contains(@class,"se-hero__desc")]')->item(0);
$descHtml = $desc ? $doc->saveHTML($desc) : '';

// Descripción interna (sin el contenedor)
$inicio = strpos($descHtml, '>');
$fin = strrpos($descHtml, '</div>');
$descInterior = ($inicio !== false && $fin !== false) ? substr($descHtml, $inicio + 1, $fin - $inicio - 1) : '';

$titulo = $texto($h1);
$edicion = $texto($xp->query('//*[contains(@class,"se-badge")]')->item(0));
$inscripcion = $texto($xp->query('//*[contains(@class,"se-prizes-inscription")]')->item(0));

$equipos = [];
foreach ($xp->query('//table[contains(@class,"se-table")]//tbody/tr') as $fila) {
    $celdas = [];
    foreach ($xp->query('./td', $fila) as $td) {
        $celdas[] = $texto($td);
    }

    if (count($celdas) < 11) {
        continue;
    }

    $equipos[] = [
        // La primera celda es el puesto (#1); el nombre va en la segunda.
        'nombre' => $celdas[1],
        'pts' => (int) $celdas[2],
        'extra' => (int) $celdas[3],
        'pj' => (int) $celdas[4],
        'pg' => (int) $celdas[5],
        'pe' => (int) $celdas[6],
        'pp' => (int) $celdas[7],
        'dg' => (int) $celdas[8],
        'gf' => (int) $celdas[9],
        'gc' => (int) $celdas[10],
    ];
}

$premios = [];
$lugares = $xp->query('//*[contains(@class,"se-prize-card__place")]');
$valores = $xp->query('//*[contains(@class,"se-prize-card__value")]');
foreach ($lugares as $i => $lugar) {
    $premios[] = [
        'puesto' => $texto($lugar),
        'valor' => $texto($valores->item($i)),
    ];
}

$contacto = [];
foreach ($xp->query('//*[contains(@class,"se-contact-card__value")]') as $valor) {
    $contacto[] = $texto($valor);
}

// Partidos: todas las fechas de todas las fases, con su número de fecha.
$partidos = [];
foreach ($xp->query('//div[contains(@class,"se-phase")]') as $fase) {
    $tituloFase = $texto($xp->query('./h3[contains(@class,"se-phase-title")]', $fase)->item(0));

    foreach ($xp->query('.//details[contains(@class,"se-round")]', $fase) as $ronda) {
        $resumen = $texto($xp->query('./summary', $ronda)->item(0));
        $numero = preg_match('/Fecha\s*(\d+)/u', $resumen, $m) ? (int) $m[1] : null;

        foreach ($xp->query('.//article[contains(@class,"se-match")]', $ronda) as $match) {
            $nombres = [];
            foreach ($xp->query('.//*[contains(@class,"se-match__name")]', $match) as $nombre) {
                $nombres[] = $texto($nombre);
            }

            if (count($nombres) !== 2) {
                continue;
            }

            $partidos[] = [
                'fase' => $tituloFase,
                'fecha' => $numero,
                'local' => $nombres[0],
                'visitante' => $nombres[1],
                'marcador' => $texto($xp->query('.//*[contains(@class,"se-match__score")]', $match)->item(0)),
                'estado' => $texto($xp->query('.//*[contains(@class,"se-match__meta")]', $match)->item(0)),
            ];
        }
    }
}

$datos = [
    'titulo' => $titulo,
    'edicion' => $edicion,
    'descripcion' => trim($descInterior),
    'inscripcion' => $inscripcion,
    'equipos' => $equipos,
    'premios' => $premios,
    'contacto' => $contacto,
    'partidos' => $partidos,
];

file_put_contents(__DIR__ . '/datos-produccion.json', json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo 'Título: ' . $titulo . PHP_EOL;
echo 'Edición: ' . $edicion . PHP_EOL;
echo 'Equipos: ' . count($equipos) . ' · Premios: ' . count($premios) . ' · Partidos: ' . count($partidos) . PHP_EOL;
echo 'Contacto: ' . implode(' · ', $contacto) . PHP_EOL;
echo 'Inscripción: ' . $inscripcion . PHP_EOL;
