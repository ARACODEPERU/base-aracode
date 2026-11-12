<?php
// 1) CSS de plantillas: fuera las reglas .plt-demo y .plt-hero__grid, texto centrado,
//    hero más bajo.
// 2) Blade del catálogo: hero sin maqueta ni script, contenido centrado.
// 3) CSS compartido: baja el alto de los heroes internos.

$raiz = __DIR__ . '/..';
$rutaCss = $raiz . '/public/themes/plantillas/css/plantillas.css';
$css = file_get_contents($rutaCss);

/**
 * Recorre el CSS y devuelve el texto sin las reglas cuyo selector cumpla el
 * filtro. Entra en @media y descarta los que se queden vacíos.
 */
function filtrar(string $texto, callable $descartar): string
{
    $salida = '';
    $i = 0;
    $largo = strlen($texto);

    while ($i < $largo) {
        $llave = strpos($texto, '{', $i);

        if ($llave === false) {
            $salida .= substr($texto, $i);
            break;
        }

        $selector = substr($texto, $i, $llave - $i);
        $limpio = trim($selector);

        // Cierre de la regla contando llaves.
        $nivel = 0;
        $j = $llave;

        while ($j < $largo) {
            if ($texto[$j] === '{') {
                $nivel++;
            } elseif ($texto[$j] === '}') {
                $nivel--;
                if ($nivel === 0) {
                    break;
                }
            }
            $j++;
        }

        $cuerpo = substr($texto, $llave + 1, $j - $llave - 1);

        if ($descartar($limpio)) {
            // Se omite la regla entera.
        } elseif (str_starts_with($limpio, '@media') || str_starts_with($limpio, '@supports')) {
            $interior = filtrar($cuerpo, $descartar);
            if (trim($interior) !== '') {
                $salida .= $selector . '{' . $interior . '}';
            }
        } else {
            $salida .= $selector . '{' . $cuerpo . '}';
        }

        $i = $j + 1;

        // Conserva los saltos de línea que siguen a la regla.
        while ($i < $largo && ($texto[$i] === "\n" || $texto[$i] === "\r")) {
            $salida .= $texto[$i];
            $i++;
        }
    }

    return $salida;
}

$fuera = fn (string $s): bool => str_contains($s, 'plt-demo') || str_contains($s, 'plt-hero__grid');

$css = filtrar($css, $fuera);

// Hero más bajo y contenido centrado.
$css = str_replace(
    '.plt-hero{position:relative;overflow:hidden;padding:9rem 0 3.5rem;',
    '.plt-hero{position:relative;overflow:hidden;padding:8rem 0 3rem;',
    $css
);

$css = str_replace(
    '.plt-buscador{margin-top:2rem;max-width:42rem}',
    '.plt-hero__contenido{max-width:46rem;margin:0 auto;text-align:center}'
    . '.plt-buscador{margin:2rem auto 0;max-width:42rem}',
    $css
);

$css = str_replace(
    '.plt-hero{height:540px;display:flex;flex-direction:column;justify-content:center;padding:3.5rem 0}',
    '.plt-hero{min-height:440px;display:flex;flex-direction:column;justify-content:center;padding:3rem 0}',
    $css
);

file_put_contents($rutaCss, $css);
echo 'plantillas.css: ' . strlen($css) . " bytes\n";
echo 'quedan reglas plt-demo: ' . substr_count($css, 'plt-demo') . "\n";

// 3) Heroes internos: mismo alto nuevo.
$rutaEvo = $raiz . '/public/css/aracode-evolucion.css';
$evo = file_get_contents($rutaEvo);
$evo = str_replace(
    "    .ara-ph {\n        min-height: 540px;",
    "    .ara-ph {\n        min-height: 440px;",
    $evo
);
$evo = str_replace(
    "        justify-content: center;\n        padding: 3.5rem 0;",
    "        justify-content: center;\n        padding: 3rem 0;",
    $evo
);
file_put_contents($rutaEvo, $evo);
echo 'aracode-evolucion.css: ' . strlen($evo) . " bytes\n";
echo 'min-height 440: ' . (str_contains($evo, 'min-height: 440px') ? 'sí' : 'NO') . "\n";

// 2) Blade del catálogo.
$rutaBlade = $raiz . '/resources/views/plantillas/catalogo.blade.php';
$blade = file_get_contents($rutaBlade);

$inicio = strpos($blade, '<section class="plt-hero">');
$fin = $inicio === false ? false : strpos($blade, '</section>', $inicio);

if ($inicio === false || $fin === false) {
    echo "HERO NO ENCONTRADO\n";
    exit(1);
}

$nuevo = <<<'BLADE'
<section class="plt-hero">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="plt-hero__contenido">
                    <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Banco de Plantillas</span>

                    <h1 class="plt-hero__titulo reveal reveal-delay-1">
                        Plantillas web listas para <span class="text-gradient">personalizar</span>
                    </h1>

                    <p class="plt-hero__lead reveal reveal-delay-2">
                        Elige el rubro, mira la demostración funcionando y la adaptamos con tu marca,
                        tus textos y tus datos. Cada plantilla es un sitio web completo, no una imagen.
                    </p>

                    {{-- Buscador: nombre, rubro o etiqueta --}}
                    <form class="plt-buscador reveal reveal-delay-3" method="get"
                          action="{{ route('plantillas.catalogo') }}" role="search">
                        <label class="plt-buscador__label" for="plt-q">¿Qué rubro buscas?</label>

                        <div class="plt-buscador__campo">
                            <span class="plt-buscador__icono" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                                </svg>
                            </span>

                            <input id="plt-q" type="search" name="q" value="{{ $busqueda }}"
                                   placeholder="dental, carta, propiedades, cursos…"
                                   aria-label="Buscar plantillas por nombre, rubro o etiqueta">

                            @if ($categoria !== '')
                                <input type="hidden" name="categoria" value="{{ $categoria }}">
                            @endif

                            <button type="submit" class="ara-btn ara-btn-primary">Buscar</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
BLADE;

$blade = substr($blade, 0, $inicio) . $nuevo . substr($blade, $fin + strlen('</section>'));
file_put_contents($rutaBlade, $blade);

echo 'catálogo: maqueta=' . (str_contains($blade, 'plt-demo') ? 'QUEDA' : 'fuera')
    . ' script=' . (str_contains($blade, 'maquetaPlantillas') ? 'QUEDA' : 'fuera') . "\n";
