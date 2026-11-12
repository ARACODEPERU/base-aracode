<?php
// Segunda tanda: faq, precios, equipo, portafolio, trabaja y las páginas legales.

$base = __DIR__ . '/../resources/views/aracode/pages/';

$paginas = [
    'faq' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Soporte"
        titulo="Preguntas"
        resaltado="Frecuentes"
        bajada="Encuentra respuestas a las dudas más comunes sobre nuestros servicios y productos."
        arte="faq"
        url="aracode.pe/faq"
    />
BLADE,

    'precios' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Precios"
        titulo="Planes"
        resaltado="Transparentes"
        bajada="Elige el plan que mejor se adapte a tu negocio. Sin costos ocultos, sin sorpresas."
        arte="precios"
        url="aracode.pe/precios"
    />
BLADE,

    'equipo' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Nuestro Equipo"
        titulo="Las personas detrás de la"
        resaltado="tecnología"
        bajada="Un equipo apasionado por la innovación y la excelencia en cada proyecto."
        arte="empresa"
        url="aracode.pe/equipo"
    />
BLADE,

    'portafolio' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Nuestros Trabajos"
        titulo=""
        resaltado="Portafolio"
        resto=" de Proyectos"
        bajada="Conoce los proyectos que hemos desarrollado para empresas e instituciones en todo Perú."
        arte="casos"
        url="aracode.pe/portafolio"
    />
BLADE,

    'trabaja-nosotros' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Carreras"
        titulo="Trabaja con"
        resaltado="Nosotros"
        bajada="Únete a un equipo de profesionales apasionados por la tecnología y la innovación. Buscamos talento que quiera hacer la diferencia."
        arte="empresa"
        url="aracode.pe/trabaja-con-nosotros"
    />
BLADE,

    'politica-cookies' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Legal"
        titulo="Política de"
        resaltado="Cookies"
        :bajada="'Última actualización: ' . date('d/m/Y')"
        arte="documento"
        url="aracode.pe/politica-cookies"
    />
BLADE,

    'politica-privacidad' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Legal"
        titulo="Política de"
        resaltado="Privacidad"
        bajada="Versión 2.0 — Última actualización: 16 de octubre de 2026"
        arte="documento"
        url="aracode.pe/politica-privacidad"
    />
BLADE,

    'terminos-condiciones' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Legal"
        titulo="Términos y"
        resaltado="Condiciones"
        :bajada="'Última actualización: ' . now()->format('d/m/Y')"
        arte="documento"
        url="aracode.pe/terminos-condiciones"
    />
BLADE,

    'libro-reclamaciones' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Legal"
        titulo="Libro de"
        resaltado="Reclamaciones"
        bajada="En cumplimiento de la normativa de INDECOPI"
        arte="documento"
        url="aracode.pe/libro-reclamaciones"
    />
BLADE,
];

foreach ($paginas as $slug => $nuevo) {
    $ruta = $base . $slug . '.blade.php';
    $html = file_get_contents($ruta);

    if ($html === false) {
        echo "FALTA: $ruta\n";
        continue;
    }

    $inicio = strpos($html, '{{-- Hero --}}');
    $fin = $inicio === false ? false : strpos($html, '</section>', $inicio);

    if ($inicio === false || $fin === false) {
        echo "SIN MARCADOR: $slug\n";
        continue;
    }

    $html = substr($html, 0, $inicio) . $nuevo . substr($html, $fin + strlen('</section>'));
    file_put_contents($ruta, $html);
    echo "OK: $slug\n";
}
