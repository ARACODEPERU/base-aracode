<?php
// 1) Centra el bloque de texto del hero y borra el CSS de la ilustración.
// 2) Quita arte="" y url="" de las invocaciones del componente.

$css = __DIR__ . '/../public/css/aracode-evolucion.css';
$s = file_get_contents($css);

// Recorta todo el CSS de la ilustración (va al final del archivo).
$marca = "/* ---------- Ilustración dibujada ---------- */";
$pos = strpos($s, $marca);
if ($pos === false) {
    echo "SIN MARCA DE ARTE\n";
} else {
    $s = rtrim(substr($s, 0, $pos)) . "\n";
    echo " CSS recortado\n";
}

$cambios = [
    // Contenedor: una sola columna centrada.
    ".ara-ph__grid {\n    display: grid;\n    grid-template-columns: minmax(0, 1fr);\n    gap: 2.5rem;\n    align-items: center;\n}"
        => ".ara-ph__texto {\n    max-width: 46rem;\n    margin: 0 auto;\n    text-align: center;\n}",

    // Fuera la rejilla de dos columnas en escritorio.
    "    .ara-ph__grid {\n        grid-template-columns: minmax(0, 1.02fr) minmax(0, .98fr);\n        gap: 3rem;\n    }\n\n"
        => "",

    // Bajada centrada.
    ".ara-ph__lead {\n    margin: 0;\n    max-width: 34rem;"
        => ".ara-ph__lead {\n    margin: 0 auto;\n    max-width: 34rem;",

    // Botones centrados.
    ".ara-ph__acciones {\n    display: flex;\n    flex-wrap: wrap;\n    gap: .6rem;\n    margin-top: 1.6rem;\n}"
        => ".ara-ph__acciones {\n    display: flex;\n    flex-wrap: wrap;\n    justify-content: center;\n    gap: .6rem;\n    margin-top: 1.6rem;\n}",

    // Lista extra: bloque centrado, texto alineado a la izquierda para leerla.
    ".ara-ph__extra {\n    margin-top: 1.4rem;\n    font-size: .875rem;\n    color: rgba(255, 255, 255, .72);\n}"
        => ".ara-ph__extra {\n    max-width: 34rem;\n    margin: 1.4rem auto 0;\n    text-align: left;\n    font-size: .875rem;\n    color: rgba(255, 255, 255, .72);\n}",
];

foreach ($cambios as $viejo => $nuevo) {
    if (strpos($s, $viejo) === false) {
        echo " NO ENCONTRADO: " . substr(str_replace("\n", '|', $viejo), 0, 40) . "\n";
        continue;
    }
    $s = str_replace($viejo, $nuevo, $s);
}

file_put_contents($css, $s);
echo "CSS: " . strlen($s) . " bytes\n";

// Páginas: quitar las líneas arte="..." y url="..."
$paginas = glob(__DIR__ . '/../resources/views/aracode/pages/*.blade.php');
$tocadas = 0;

foreach ($paginas as $ruta) {
    $html = file_get_contents($ruta);
    $nuevo = preg_replace('/^[ \t]*(arte|url)="[^"]*"\n/m', '', $html);

    if ($nuevo !== $html) {
        file_put_contents($ruta, $nuevo);
        $tocadas++;
        echo " sin arte: " . basename($ruta) . "\n";
    }
}

echo "Páginas tocadas: $tocadas\n";
