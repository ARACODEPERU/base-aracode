<?php

/*
|--------------------------------------------------------------------------
| Plano de la demo «Inmobiliaria» (AL-2401)
|--------------------------------------------------------------------------
|
| El plano se dibuja desde medidas en metros, no a ojo: cada ambiente declara
| su rectángulo y el guion comprueba antes de escribir el SVG que
|
|   1. ningún ambiente se sale de la planta,
|   2. ningún par de ambientes se solapa,
|   3. la suma de las superficies es exactamente la de la planta.
|
| Así las cotas que se imprimen (frente, fondo y la medida de cada ambiente)
| cuadran entre sí y con la ficha del inmueble. El plano anterior se dibujó a
| mano y las cotas no cerraban (columnas con fondos distintos) y algunos
| rótulos se encimaban.
|
| Uso:  php storage/tmp/plano-inmobiliaria.php
| Escribe: public/demos/inmobiliaria-propiedades/img/plano.svg
|
*/

const ESCALA = 70;      // unidades de SVG por metro
const MARGEN_X = 140;   // borde izquierdo de la planta dentro del SVG
const MARGEN_Y = 150;   // borde superior de la planta (deja sitio al balcón y al título)

// --- La planta ---------------------------------------------------------------
// Origen (0, 0) en la esquina superior izquierda. x hacia la derecha, y hacia
// abajo. Todas las medidas en metros, de eje a eje.

$planta = ['ancho' => 12.00, 'alto' => 10.00];   // 120,00 m²

$ambientes = [
    // nombre,                 x,     y,    ancho, alto
    ['SALA Y COMEDOR',       0.00,  0.00,  5.60, 4.00],
    ['COCINA',               5.60,  0.00,  2.60, 2.60],
    ['LAVANDERÍA',           5.60,  2.60,  2.60, 1.40],
    ['ESTUDIO',              8.20,  0.00,  3.80, 4.00],
    ['HALL',                 0.00,  4.00, 12.00, 1.20],
    ['DORMITORIO 2',         0.00,  5.20,  3.00, 3.00],
    ['ESCRITORIO',           0.00,  8.20,  3.00, 1.80],
    ['DORMITORIO 3',         3.00,  5.20,  3.00, 3.00],
    ['CLOSET',               3.00,  8.20,  3.00, 1.80],
    ['BAÑO 1',               6.00,  5.20,  2.20, 2.20],
    ['DEPÓSITO',             6.00,  7.40,  2.20, 2.60],
    ['DORM. PRINCIPAL',      8.20,  5.20,  3.80, 3.00],
    ['BAÑO PRINCIPAL',       8.20,  8.20,  1.90, 1.80],
    ['VESTIDOR',            10.10,  8.20,  1.90, 1.80],
];

$balcon = ['nombre' => 'BALCÓN', 'x' => 0.00, 'y' => -1.10, 'ancho' => 4.20, 'alto' => 1.10];

// --- Vanos -------------------------------------------------------------------
// Las ventanas van sobre muro exterior; las puertas llevan hoja y arco.

$ventanas = [
    ['muro' => 'frente',    'a' => 0.60,  'b' => 2.20],   // sala
    ['muro' => 'frente',    'a' => 2.60,  'b' => 3.60],   // puerta al balcón
    ['muro' => 'frente',    'a' => 6.00,  'b' => 7.80],   // cocina
    ['muro' => 'frente',    'a' => 8.80,  'b' => 11.60],  // estudio
    ['muro' => 'izquierda', 'a' => 6.00,  'b' => 8.00],   // dormitorio 2
    ['muro' => 'derecha',   'a' => 5.60,  'b' => 7.80],   // dormitorio principal
    ['muro' => 'fondo',     'a' => 0.60,  'b' => 2.40],   // escritorio
    ['muro' => 'fondo',     'a' => 6.60,  'b' => 7.80],   // depósito
    ['muro' => 'fondo',     'a' => 8.60,  'b' => 9.80],   // baño principal
];

$puertas = [
    // muro, desde, hasta, hacia dónde abre la hoja (n/s/e/o)
    ['muro' => 'frente',    'a' => 4.20,  'b' => 5.20,  'lado' => 's'],  // ingreso
    ['muro' => 'horizontal', 'y' => 4.00, 'a' => 6.00,  'b' => 7.00,  'lado' => 'n'],  // lavandería
    ['muro' => 'vertical',   'x' => 5.60, 'a' => 1.40,  'b' => 2.40,  'lado' => 'o'],  // cocina
    ['muro' => 'horizontal', 'y' => 4.00, 'a' => 9.00,  'b' => 10.00, 'lado' => 'n'],  // estudio
    ['muro' => 'horizontal', 'y' => 5.20, 'a' => 1.00,  'b' => 2.00,  'lado' => 's'],  // dormitorio 2
    ['muro' => 'horizontal', 'y' => 5.20, 'a' => 4.00,  'b' => 5.00,  'lado' => 's'],  // dormitorio 3
    ['muro' => 'horizontal', 'y' => 5.20, 'a' => 6.80,  'b' => 7.60,  'lado' => 's'],  // baño 1
    ['muro' => 'horizontal', 'y' => 5.20, 'a' => 9.00,  'b' => 10.00, 'lado' => 's'],  // dorm. principal
    ['muro' => 'horizontal', 'y' => 8.20, 'a' => 1.00,  'b' => 2.00,  'lado' => 's'],  // escritorio
    ['muro' => 'horizontal', 'y' => 8.20, 'a' => 4.00,  'b' => 5.00,  'lado' => 's'],  // closet
    ['muro' => 'horizontal', 'y' => 7.40, 'a' => 6.80,  'b' => 7.60,  'lado' => 's'],  // depósito
    ['muro' => 'horizontal', 'y' => 8.20, 'a' => 8.60,  'b' => 9.60,  'lado' => 's'],  // baño principal
    ['muro' => 'vertical',   'x' => 10.10, 'a' => 8.50, 'b' => 9.30,  'lado' => 'e'],  // vestidor
];

// --- Comprobaciones ----------------------------------------------------------

$problemas = [];

$areaPlanta = $planta['ancho'] * $planta['alto'];
$areaAmbientes = 0.0;

foreach ($ambientes as $indice => [$nombre, $x, $y, $ancho, $alto]) {
    $areaAmbientes += $ancho * $alto;

    if ($x < 0 || $y < 0 || $x + $ancho > $planta['ancho'] + 1e-9 || $y + $alto > $planta['alto'] + 1e-9) {
        $problemas[] = "«{$nombre}» se sale de la planta.";
    }
}

foreach ($ambientes as $i => [$nombreA, $ax, $ay, $aw, $ah]) {
    foreach ($ambientes as $j => [$nombreB, $bx, $by, $bw, $bh]) {
        if ($j <= $i) {
            continue;
        }

        $solapeX = min($ax + $aw, $bx + $bw) - max($ax, $bx);
        $solapeY = min($ay + $ah, $by + $bh) - max($ay, $by);

        if ($solapeX > 1e-9 && $solapeY > 1e-9) {
            $problemas[] = "«{$nombreA}» y «{$nombreB}» se solapan.";
        }
    }
}

if (abs($areaAmbientes - $areaPlanta) > 1e-9) {
    $problemas[] = sprintf(
        'La suma de los ambientes (%.2f m²) no es la de la planta (%.2f m²).',
        $areaAmbientes,
        $areaPlanta
    );
}

if ($problemas) {
    fwrite(STDERR, "El plano no cierra:\n - " . implode("\n - ", $problemas) . "\n");
    exit(1);
}

// --- Dibujo ------------------------------------------------------------------

$u = fn (float $metros): float => round($metros * ESCALA, 2);
$x = fn (float $metros): float => MARGEN_X + $u($metros);
$y = fn (float $metros): float => MARGEN_Y + $u($metros);
$numero = fn (float $valor): string => number_format($valor, 2, ',', '');

$svg = [];
$svg[] = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1100 1040" width="1100" height="1040" role="img" aria-labelledby="titulo-plano desc-plano" font-family="ui-monospace, \'Cascadia Mono\', Menlo, Consolas, monospace">';
$svg[] = '    <title id="titulo-plano">Plano del departamento AL-2401</title>';
$svg[] = '    <desc id="desc-plano">Plano de un departamento de 120 metros cuadrados: sala y comedor con balcón al frente, cocina con lavandería, estudio, hall de distribución, tres dormitorios (el principal con baño propio y vestidor), dos baños completos, escritorio, closet y depósito. Las medidas están anotadas en metros.</desc>';

$svg[] = '    <defs>';
$svg[] = '        <marker id="punta" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">';
$svg[] = '            <path d="M0 0 10 5 0 10z" fill="#4C6C0F"/>';
$svg[] = '        </marker>';
$svg[] = '    </defs>';

// Rótulo del plano
$svg[] = '    <g>';
$svg[] = '        <text x="' . MARGEN_X . '" y="42" font-size="20" font-weight="700" fill="#171B20" letter-spacing="2">PLANO DE REFERENCIA · AL-2401 · PISO 6</text>';
$svg[] = '        <text x="' . MARGEN_X . '" y="64" font-size="14" fill="#5B646E" letter-spacing="1">Medidas de eje a eje, en metros. Planta de ' . $numero($areaPlanta) . ' m² y balcón de ' . $numero($balcon['ancho'] * $balcon['alto']) . ' m².</text>';
$svg[] = '        <rect x="890" y="26" width="150" height="44" rx="8" fill="none" stroke="#DEE4D8" stroke-width="2"/>';
$svg[] = '        <text x="910" y="54" font-size="15" fill="#5B646E" letter-spacing="1">ESCALA 1:100</text>';
$svg[] = '    </g>';

// Rellenos: cada ambiente y el balcón
$svg[] = '    <g>';
$svg[] = '        <rect x="' . $x(0) . '" y="' . $y(0) . '" width="' . $u($planta['ancho']) . '" height="' . $u($planta['alto']) . '" fill="#EDF0EA"/>';
$svg[] = '        <rect x="' . $x($balcon['x']) . '" y="' . $y($balcon['y']) . '" width="' . $u($balcon['ancho']) . '" height="' . $u($balcon['alto']) . '" fill="#DDE3D6"/>';
$svg[] = '    </g>';

// Tabiques interiores
$svg[] = '    <!-- Tabiques interiores -->';
$svg[] = '    <g stroke="#171B20" stroke-width="3" fill="none">';

foreach ($ambientes as [$nombre, $ax, $ay, $ancho, $alto]) {
    $svg[] = '        <rect x="' . $x($ax) . '" y="' . $y($ay) . '" width="' . $u($ancho) . '" height="' . $u($alto) . '"/>';
}

$svg[] = '    </g>';

// Muros exteriores y balcón
$svg[] = '    <!-- Muros exteriores y balcón -->';
$svg[] = '    <g fill="none" stroke="#171B20">';
$svg[] = '        <rect x="' . $x(0) . '" y="' . $y(0) . '" width="' . $u($planta['ancho']) . '" height="' . $u($planta['alto']) . '" stroke-width="7"/>';
$svg[] = '        <rect x="' . $x($balcon['x']) . '" y="' . $y($balcon['y']) . '" width="' . $u($balcon['ancho']) . '" height="' . $u($balcon['alto']) . '" stroke-width="5"/>';
$svg[] = '    </g>';

// Baranda del balcón
$svg[] = '    <g stroke="#4C6C0F" stroke-width="2">';
$svg[] = '        <line x1="' . ($x($balcon['x']) + $u(0.20)) . '" y1="' . ($y($balcon['y']) + $u(0.35)) . '" x2="' . ($x($balcon['x'] + $balcon['ancho']) - $u(0.20)) . '" y2="' . ($y($balcon['y']) + $u(0.35)) . '"/>';
$svg[] = '        <line x1="' . ($x($balcon['x']) + $u(0.20)) . '" y1="' . ($y($balcon['y']) + $u(0.70)) . '" x2="' . ($x($balcon['x'] + $balcon['ancho']) - $u(0.20)) . '" y2="' . ($y($balcon['y']) + $u(0.70)) . '"/>';
$svg[] = '    </g>';

/**
 * Muro donde cae un vano. Las puertas pueden declarar un muro interior con su
 * coordenada (`horizontal` con `y`, o `vertical` con `x`) o el nombre de un
 * muro de la envolvente (`frente`, `fondo`, `izquierda`, `derecha`).
 */
function infoMuro(array $vano, array $planta): array
{
    if ($vano['muro'] === 'horizontal') {
        return ['eje' => 'h', 'valor' => $vano['y'], 'grosor' => 6];
    }

    if ($vano['muro'] === 'vertical') {
        return ['eje' => 'v', 'valor' => $vano['x'], 'grosor' => 6];
    }

    return muro($vano['muro'], $planta);
}

/** Extremos y grosor del muro donde cae un vano. */
function muro(string $clave, array $planta): array
{
    return match ($clave) {
        'frente' => ['eje' => 'h', 'valor' => 0.0, 'grosor' => 9],
        'fondo' => ['eje' => 'h', 'valor' => $planta['alto'], 'grosor' => 9],
        'izquierda' => ['eje' => 'v', 'valor' => 0.0, 'grosor' => 9],
        'derecha' => ['eje' => 'v', 'valor' => $planta['ancho'], 'grosor' => 9],
        default => ['eje' => 'h', 'valor' => 0.0, 'grosor' => 6],
    };
}

// Huecos: el vano se abre borrando el muro con una línea del color del papel
$svg[] = '    <!-- Huecos de puertas y ventanas -->';
$svg[] = '    <g stroke="#EDF0EA" stroke-linecap="butt">';

$lineasHueco = [];

foreach ($ventanas as $ventana) {
    $info = muro($ventana['muro'], $planta);
    $lineasHueco[] = [$info, $ventana['a'], $ventana['b']];
}

foreach ($puertas as $puerta) {
    $lineasHueco[] = [infoMuro($puerta, $planta), $puerta['a'], $puerta['b']];
}

foreach ($lineasHueco as [$info, $desde, $hasta]) {
    if ($info['eje'] === 'h') {
        $svg[] = '        <line x1="' . $x($desde) . '" y1="' . $y($info['valor']) . '" x2="' . $x($hasta) . '" y2="' . $y($info['valor']) . '" stroke-width="' . $info['grosor'] . '"/>';
    } else {
        $svg[] = '        <line x1="' . $x($info['valor']) . '" y1="' . $y($desde) . '" x2="' . $x($info['valor']) . '" y2="' . $y($hasta) . '" stroke-width="' . $info['grosor'] . '"/>';
    }
}

$svg[] = '    </g>';

// Ventanas: el cristal se dibuja como una línea de color sobre el hueco
$svg[] = '    <!-- Ventanas -->';
$svg[] = '    <g stroke="#4C6C0F" stroke-width="2.5">';

foreach ($ventanas as $ventana) {
    $info = muro($ventana['muro'], $planta);

    if ($info['eje'] === 'h') {
        $svg[] = '        <line x1="' . $x($ventana['a']) . '" y1="' . $y($info['valor']) . '" x2="' . $x($ventana['b']) . '" y2="' . $y($info['valor']) . '"/>';
    } else {
        $svg[] = '        <line x1="' . $x($info['valor']) . '" y1="' . $y($ventana['a']) . '" x2="' . $x($info['valor']) . '" y2="' . $y($ventana['b']) . '"/>';
    }
}

$svg[] = '    </g>';

// Puertas: hoja y arco, dibujados punto por punto para no depender de banderas
$svg[] = '    <!-- Puertas: hoja y arco de apertura -->';
$svg[] = '    <g fill="none" stroke="#4C6C0F" stroke-width="2" stroke-linejoin="round">';

foreach ($puertas as $puerta) {
    $ancho = $puerta['b'] - $puerta['a'];
    $radio = $u($ancho);

    $info = infoMuro($puerta, $planta);

    if ($info['eje'] === 'h') {
        $ejeY = $info['valor'];
        $bisagraX = $x($puerta['a']);
        $bisagraY = $y($ejeY);
        $finalX = $x($puerta['b']);
        $finalY = $y($ejeY);
        $signo = $puerta['lado'] === 's' ? 1 : -1;
        $puntaX = $bisagraX;
        $puntaY = $bisagraY + $signo * $radio;
        $inicio = atan2($puntaY - $bisagraY, $puntaX - $bisagraX);
        $fin = atan2($finalY - $bisagraY, $finalX - $bisagraX);
    } else {
        $ejeX = $info['valor'];
        $bisagraY = $y($puerta['a']);
        $bisagraX = $x($ejeX);
        $finalX = $x($ejeX);
        $finalY = $y($puerta['b']);
        $signo = $puerta['lado'] === 'e' ? 1 : -1;
        $puntaX = $bisagraX + $signo * $radio;
        $puntaY = $bisagraY;
        $inicio = atan2($puntaY - $bisagraY, $puntaX - $bisagraX);
        $fin = atan2($finalY - $bisagraY, $finalX - $bisagraX);
    }

    // Giro corto: se recorre la diferencia normalizada a 90º.
    $delta = $fin - $inicio;

    while ($delta > M_PI) {
        $delta -= 2 * M_PI;
    }

    while ($delta < -M_PI) {
        $delta += 2 * M_PI;
    }

    $puntos = [];

    for ($paso = 0; $paso <= 10; $paso++) {
        $angulo = $inicio + $delta * ($paso / 10);
        $puntos[] = round($bisagraX + $radio * cos($angulo), 2) . ' ' . round($bisagraY + $radio * sin($angulo), 2);
    }

    $svg[] = '        <path d="M' . $puntos[0] . ' ' . implode(' ', array_map(fn ($p) => 'L' . $p, array_slice($puntos, 1))) . '" stroke-dasharray="6 5"/>';
    $svg[] = '        <line x1="' . $bisagraX . '" y1="' . $bisagraY . '" x2="' . $puntaX . '" y2="' . $puntaY . '"/>';
}

$svg[] = '    </g>';

// Muebles de referencia: la cocina equipada que describe la ficha
$muebles = [
    ['x' => 5.70, 'y' => 0.12, 'ancho' => 2.40, 'alto' => 0.60],   // muebles bajos
    ['x' => 6.40, 'y' => 1.85, 'ancho' => 1.20, 'alto' => 0.70],   // isla
];

$svg[] = '    <!-- Muebles de referencia: cocina -->';
$svg[] = '    <g fill="#DDE3D6" stroke="#171B20" stroke-width="2">';

foreach ($muebles as $mueble) {
    $svg[] = '        <rect x="' . $x($mueble['x']) . '" y="' . $y($mueble['y']) . '" width="' . $u($mueble['ancho']) . '" height="' . $u($mueble['alto']) . '"/>';
}

$svg[] = '    </g>';

// Nombre y medida de cada ambiente
$svg[] = '    <!-- Nombre y medida de cada ambiente -->';
$svg[] = '    <g letter-spacing="1.2">';

foreach ($ambientes as [$nombre, $ax, $ay, $ancho, $alto]) {
    $centroX = $x($ax + $ancho / 2);
    $centroY = $y($ay + $alto / 2);
    $medida = $numero($ancho) . ' × ' . $numero($alto);

    // El hall es un pasillo de lado a lado de la planta: el rótulo va en una
    // sola línea, sin la medida debajo, para no salirse de los 1,20 m de alto.
    if ($nombre === 'HALL') {
        $svg[] = '        <text x="' . $centroX . '" y="' . ($centroY + 5) . '" font-size="15" font-weight="700" fill="#171B20" text-anchor="middle">HALL · ANCHO 1,20 m</text>';
        continue;
    }

    // La cocina lleva los muebles dibujados: el rótulo sube para no pisarlos.
    if ($nombre === 'COCINA') {
        $centroX = $x(6.90);
        $centroY = $y(1.05);
    }

    $pequeno = $alto < 2.30;
    $tamNombre = $pequeno ? 13 : 15;

    $svg[] = '        <text x="' . $centroX . '" y="' . ($centroY - 2) . '" font-size="' . $tamNombre . '" font-weight="700" fill="#171B20" text-anchor="middle">' . htmlspecialchars($nombre, ENT_QUOTES) . '</text>';
    $svg[] = '        <text x="' . $centroX . '" y="' . ($centroY + 16) . '" font-size="12" fill="#5B646E" text-anchor="middle">' . $medida . '</text>';
}

// Balcón
$svg[] = '        <text x="' . $x($balcon['x'] + $balcon['ancho'] / 2) . '" y="' . ($y($balcon['y'] + $balcon['alto'] / 2) + 5) . '" font-size="13" font-weight="700" fill="#171B20" text-anchor="middle">BALCÓN ' . $numero($balcon['ancho']) . ' × ' . $numero($balcon['alto']) . '</text>';
$svg[] = '    </g>';

// Ingreso: se rotula junto a la puerta de la calle
$svg[] = '    <text x="' . $x(4.95) . '" y="' . ($y(0) - 22) . '" font-size="13" fill="#4C6C0F" letter-spacing="1">INGRESO</text>';

// Cotas
$svg[] = '    <!-- Cotas -->';
$svg[] = '    <g stroke="#4C6C0F" stroke-width="2" fill="#4C6C0F">';

// Columnas del frente, justo debajo de la planta
$columnas = [[0.0, 5.60], [5.60, 8.20], [8.20, 12.00]];
$lineaColumnas = $y($planta['alto']) + 42;

$svg[] = '        <line x1="' . $x(0) . '" y1="' . $lineaColumnas . '" x2="' . $x($planta['ancho']) . '" y2="' . $lineaColumnas . '"/>';

foreach ($columnas as [$desde, $hasta]) {
    $svg[] = '        <line x1="' . $x($desde) . '" y1="' . ($lineaColumnas - 8) . '" x2="' . $x($desde) . '" y2="' . ($lineaColumnas + 8) . '"/>';
    $svg[] = '        <text x="' . $x(($desde + $hasta) / 2) . '" y="' . ($lineaColumnas - 14) . '" font-size="13" text-anchor="middle">' . $numero($hasta - $desde) . '</text>';
}

$svg[] = '        <line x1="' . $x($planta['ancho']) . '" y1="' . ($lineaColumnas - 8) . '" x2="' . $x($planta['ancho']) . '" y2="' . ($lineaColumnas + 8) . '"/>';

// Frente total
$lineaFrente = $lineaColumnas + 40;
$svg[] = '        <line x1="' . $x(0) . '" y1="' . $lineaFrente . '" x2="' . $x($planta['ancho']) . '" y2="' . $lineaFrente . '" marker-start="url(#punta)" marker-end="url(#punta)"/>';
$svg[] = '        <text x="' . $x($planta['ancho'] / 2) . '" y="' . ($lineaFrente + 26) . '" font-size="16" letter-spacing="1.5" text-anchor="middle">FRENTE ' . $numero($planta['ancho']) . ' m</text>';

// Fondo total
$svg[] = '        <line x1="80" y1="' . $y(0) . '" x2="80" y2="' . $y($planta['alto']) . '" marker-start="url(#punta)" marker-end="url(#punta)"/>';
$svg[] = '        <line x1="94" y1="' . $y(0) . '" x2="' . ($x(0) - 6) . '" y2="' . $y(0) . '"/>';
$svg[] = '        <line x1="94" y1="' . $y($planta['alto']) . '" x2="' . ($x(0) - 6) . '" y2="' . $y($planta['alto']) . '"/>';
$svg[] = '        <text x="58" y="' . $y($planta['alto'] / 2) . '" font-size="16" letter-spacing="1.5" text-anchor="middle" transform="rotate(-90 58 ' . $y($planta['alto'] / 2) . ')">FONDO ' . $numero($planta['alto']) . ' m</text>';

// Profundidad del balcón
$centroBalcon = $y($balcon['y'] + $balcon['alto'] / 2);
$svg[] = '        <line x1="80" y1="' . $y($balcon['y']) . '" x2="80" y2="' . $y(0) . '"/>';
$svg[] = '        <text x="58" y="' . $centroBalcon . '" font-size="13" text-anchor="middle" transform="rotate(-90 58 ' . $centroBalcon . ')">' . $numero($balcon['alto']) . '</text>';

$svg[] = '    </g>';

// Norte y notas
$svg[] = '    <g transform="translate(1040 930)">';
$svg[] = '        <circle cx="0" cy="0" r="30" fill="none" stroke="#DEE4D8" stroke-width="2"/>';
$svg[] = '        <path d="M0 -22 8 8 0 2 -8 8z" fill="#4C6C0F"/>';
$svg[] = '        <text x="0" y="26" font-size="14" fill="#5B646E" text-anchor="middle">N</text>';
$svg[] = '    </g>';
$svg[] = '    <g fill="#5B646E" font-size="13" letter-spacing="1">';
$svg[] = '        <text x="80" y="' . ($lineaFrente + 62) . '">Cotas en metros, de eje a eje. La superficie de la planta es de ' . $numero($areaPlanta) . ' m² y el balcón no está incluido.</text>';
$svg[] = '        <text x="80" y="' . ($lineaFrente + 82) . '">Los baños interiores ventilan por ducto. Plano de referencia de la demostración, no es un plano municipal.</text>';
$svg[] = '    </g>';
$svg[] = '</svg>';

$destino = __DIR__ . '/../../public/demos/inmobiliaria-propiedades/img/plano.svg';

if (file_put_contents($destino, implode("\n", $svg) . "\n") === false) {
    fwrite(STDERR, "No se pudo escribir {$destino}\n");
    exit(1);
}

// --- Informe -----------------------------------------------------------------

printf("Plano escrito en %s\n\n", realpath($destino));
printf("Planta: %.2f × %.2f m = %.2f m²\n", $planta['ancho'], $planta['alto'], $areaPlanta);
printf("Ambientes: %d, suma %.2f m² (sin el balcón, %.2f m²)\n\n", count($ambientes), $areaAmbientes, $balcon['ancho'] * $balcon['alto']);

foreach ($ambientes as [$nombre, $ax, $ay, $ancho, $alto]) {
    printf("  %-18s %5.2f × %5.2f  = %6.2f m²\n", $nombre, $ancho, $alto, $ancho * $alto);
}

// Comprobación del fondo de cada columna y del ancho de cada franja. El área de
// cada ambiente se recorta contra la banda: así el hall, que cruza la planta de
// lado a lado, cuenta en todas las columnas.
$columnas = [[0.00, 5.60], [5.60, 8.20], [8.20, 12.00]];
$franjas = [[0.00, 4.00], [4.00, 5.20], [5.20, 10.00]];

/** Área de lo que un ambiente aporta dentro de una banda, en un eje. */
function areaEnBanda(array $ambiente, string $eje, float $desde, float $hasta): float
{
    [$nombre, $ax, $ay, $ancho, $alto] = $ambiente;
    $inicio = $eje === 'x' ? $ax : $ay;
    $largo = $eje === 'x' ? $ancho : $alto;
    $recorte = min($inicio + $largo, $hasta) - max($inicio, $desde);

    return $recorte > 0 ? $recorte * ($eje === 'x' ? $alto : $ancho) : 0.0;
}

echo "\nCierre por columnas (el fondo de cada columna debe dar 10,00 m):\n";

foreach ($columnas as [$desde, $hasta]) {
    $area = 0.0;

    foreach ($ambientes as $ambiente) {
        $area += areaEnBanda($ambiente, 'x', $desde, $hasta);
    }

    $fondo = $area / ($hasta - $desde);
    printf("  x %.2f–%.2f: fondo %.2f m %s\n", $desde, $hasta, $fondo, abs($fondo - $planta['alto']) < 1e-9 ? '✓' : '✗');
}

echo "\nCierre por franjas (el ancho de cada franja debe dar 12,00 m):\n";

foreach ($franjas as [$desde, $hasta]) {
    $area = 0.0;

    foreach ($ambientes as $ambiente) {
        $area += areaEnBanda($ambiente, 'y', $desde, $hasta);
    }

    $ancho = $area / ($hasta - $desde);
    printf("  y %.2f–%.2f: ancho %.2f m %s\n", $desde, $hasta, $ancho, abs($ancho - $planta['ancho']) < 1e-9 ? '✓' : '✗');
}

printf("\nVentanas: %d · Puertas: %d\n", count($ventanas), count($puertas));
