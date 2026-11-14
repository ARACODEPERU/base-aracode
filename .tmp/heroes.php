<?php
// Sustituye el hero antiguo de cada página interna por <x-v2.hero-pagina>.
// Recorta desde "{{-- Hero --}}" hasta el primer </section> y escribe el componente.

$base = __DIR__ . '/../resources/views/aracode/pages/';

$paginas = [
    'soluciones' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Nuestras Soluciones"
        titulo="Soluciones que"
        resaltado="impulsan"
        resto=" tu negocio"
        bajada="Ofrecemos un ecosistema completo de productos y servicios tecnológicos diseñados para la transformación digital de tu empresa."
        arte="soluciones"
        url="aracode.pe/soluciones"
    />
BLADE,

    'empresa' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Sobre Nosotros"
        titulo="Conoce a"
        resaltado="ARACODE"
        bajada="Empresa peruana especializada en desarrollo de software empresarial, automatización de procesos e inteligencia artificial."
        arte="empresa"
        url="aracode.pe/empresa"
    />
BLADE,

    'contacto' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Contacto"
        titulo="Hablemos de tu"
        resaltado="proyecto"
        bajada="Contáctanos y recibe asesoría personalizada para encontrar la solución ideal para tu empresa."
        arte="contacto"
        url="aracode.pe/contacto"
    />
BLADE,

    'blog' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Blog"
        titulo="Noticias y"
        resaltado="recursos"
        bajada="Artículos sobre tecnología, automatización, inteligencia artificial y transformación digital para tu empresa."
        arte="blog"
        url="aracode.pe/blog"
    />
BLADE,

    'casos-exito' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Portfolio"
        titulo="Casos de"
        resaltado="Éxito"
        bajada="Conoce los proyectos que hemos desarrollado y los resultados obtenidos para nuestros clientes."
        arte="casos"
        url="aracode.pe/casos-exito"
    />
BLADE,

    'desarrollo' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Personalizado"
        titulo="Desarrollo de Software"
        resaltado="a Medida"
        bajada="Creamos soluciones de software diseñadas específicamente para las necesidades de tu empresa. Desde la idea hasta la implementación."
        arte="desarrollo"
        url="aracode.pe/soluciones/desarrollo"
    >
        <x-slot:acciones>
            <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary">
                Solicitar Presupuesto
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
            <a href="{{ route('soluciones') }}" class="ara-btn ara-btn-secondary">Ver Otros Servicios</a>
        </x-slot:acciones>
    </x-v2.hero-pagina>
BLADE,

    'paginas-web' => <<<'BLADE'
    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Servicio de ARACODE"
        titulo="Tu página web"
        resaltado="publicada en días"
        :bajada="'Elige una plantilla de nuestro catálogo, la adaptamos con tu logo, tus textos y tus fotos, y la dejamos online con tu propio dominio. Planes desde ' . $precioDesde . ' al mes o el año completo: sin costo de implementación y sin sorpresas.'"
        arte="web"
        url="aracode.pe/paginas-web"
    >
        <x-slot:acciones>
            <a href="{{ route('plantillas.catalogo') }}" class="ara-btn ara-btn-primary">
                Ver las plantillas
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
            <a href="{{ route('contacto') }}" data-asesoria class="ara-btn ara-btn-secondary">Solicitar asesoría</a>
        </x-slot:acciones>

        <x-slot:extra>
            <ul>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-ara-green flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Sin costo de implementación: se paga el plan, no el proyecto
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-ara-green flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Dominio, hosting y certificado de seguridad incluidos
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-ara-green flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Publicada y con cambios de contenido incluidos
                </li>
            </ul>
        </x-slot:extra>
    </x-v2.hero-pagina>
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
