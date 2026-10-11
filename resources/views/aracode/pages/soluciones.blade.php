@extends('aracode.layouts.webpage')

@section('meta_title', 'Soluciones | ARACODE Smart Solutions')
@section('meta_description', 'Descubre todas nuestras soluciones tecnológicas: KAPTA LMS, Facturación Electrónica, Desarrollo a Medida y más.')

@php
    /*
        Sitios propios de cada producto
        -------------------------------
        Los productos que ya tienen su propio sitio web (resources/views/kapta,
        kirafact y pichanguero) mandan ahí su botón «Conocer Más»: es la web del
        producto, con su identidad y —en producción— su propio dominio.

        Las URLs NO se escriben aquí: salen de config/productos.php, que es el
        único archivo que se edita el día que un producto se copie a su dominio
        (así esta página y las fichas de producto cambian a la vez). Mientras
        conviven dentro de este repositorio la config guarda la ruta relativa y
        SitiosProducto la resuelve contra el host actual. Ver
        docs/SEO_ECOSISTEMA_PRODUCTOS.md.

        «Desarrollo a Medida» y las tarjetas de Automatización e IA son servicios
        de ARACODE, no productos con sitio propio, así que siguen apuntando a sus
        páginas internas.

        Decisión de navegación: los sitios de producto abren en una PESTAÑA
        NUEVA. Esta página funciona como catálogo y el visitante puede querer
        abrir dos o tres productos para compararlos sin perder el lugar donde
        estaba; además el destino es otra web, con su propia navegación y su
        propio logo. Los destinos internos (contacto, desarrollo) siguen
        abriéndose en la misma pestaña.
    */
    $sitiosProducto = [
        'kapta' => \App\Support\SitiosProducto::url('kapta'),
        'kirafact' => \App\Support\SitiosProducto::url('kirafact'),
        'pichanguero' => \App\Support\SitiosProducto::url('pichanguero'),
    ];
@endphp

@section('content')
    @include('aracode.components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Nuestras Soluciones</span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 reveal reveal-delay-1">
                    Soluciones que <span class="text-gradient">impulsan</span> tu negocio
                </h1>
                <p class="text-lg text-white/70 reveal reveal-delay-2">
                    Ofrecemos un ecosistema completo de productos y servicios tecnológicos diseñados para la transformación digital de tu empresa.
                </p>
            </div>
        </div>
    </section>

    {{-- Products --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- KAPTA LMS --}}
                <x-v2.product-card
                    title="KAPTA LMS"
                    description="Plataforma SaaS para gestión y formación educativa completa."
                    :href="$sitiosProducto['kapta']"
                    :nuevaPestana="true"
                    image="{{ asset('themes/webpage/images/misc/s1.jpg') }}"
                    badge="Plataforma SaaS"
                    :features="[
                        'Gestión completa de cursos y módulos',
                        'Aulas virtuales con contenido multimedia',
                        'Certificación automática personalizada',
                        'Control de matrículas y seguimiento',
                        'Integración con pasarelas de pago',
                        'Panel administrativo con métricas IA'
                    ]"
                    :delay="1"
                />

                {{-- KIRAFACT: el nombre comercial se escribe como el producto lo
                     publica (config/kirafact.php «titulo_marca» y los títulos de
                     su propio sitio). Una sola grafía en todo el ecosistema. --}}
                <x-v2.product-card
                    title="KIRAFACT"
                    description="Facturación electrónica y gestión comercial para empresas: ventas, inventario y comprobantes electrónicos."
                    :href="$sitiosProducto['kirafact']"
                    :nuevaPestana="true"
                    image="{{ asset('themes/webpage/images/misc/s2.jpg') }}"
                    badge="SUNAT"
                    :features="[
                        'Facturas, boletas y notas de crédito/débito',
                        'Integración directa con SUNAT',
                        'Panel comercial con reportes de ventas',
                        'Generación automática de XML y PDF',
                        'Alertas y validaciones avanzadas',
                        'Seguridad y cumplimiento normativo'
                    ]"
                    :delay="2"
                />

                {{-- Desarrollo --}}
                <x-v2.product-card
                    title="Desarrollo a Medida"
                    description="Creamos soluciones de software personalizadas para las necesidades específicas de tu empresa."
                    :href="route('solucion_desarrollo')"
                    image="{{ asset('themes/webpage/images/misc/s3.jpg') }}"
                    badge="Personalizado"
                    :features="[
                        'Análisis y diseño de soluciones',
                        'Arquitectura moderna y escalable',
                        'Integraciones con sistemas existentes',
                        'Testing y garantía de calidad',
                        'Soporte y mantenimiento continuo',
                        'Documentación técnica completa'
                    ]"
                    :delay="3"
                />

                {{-- Pichanguero --}}
                <x-v2.product-card
                    title="Pichanguero"
                    description="Plataforma para organizar torneos de fútbol: fixture automático, posiciones y estadísticas en tiempo real."
                    :href="$sitiosProducto['pichanguero']"
                    :nuevaPestana="true"
                    image="{{ asset('themes/webpage/images/misc/s4.webp') }}"
                    badge="App móvil"
                    :features="[
                        'Fixture automático y calendario de fechas',
                        'Tabla de posiciones y desempates',
                        'Estadísticas de jugadores y rankings',
                        'Resultados en vivo y landing pública',
                        'Inscripciones, cupos y pagos',
                        'App móvil para equipos y jugadores'
                    ]"
                    :delay="4"
                />

                {{-- Automatización: servicio, no producto con web propia. Usa la
                     misma estructura que las tarjetas de producto (franja
                     superior + texto debajo) para que la fila se alinee; en la
                     franja va un degradado con el icono, no una imagen. --}}
                <article class="ara-product-card ara-product-card--plano reveal reveal-delay-5">
                    <div class="ara-product-media ara-product-media--azul">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span class="ara-product-badge">Servicios</span>
                    </div>

                    <div class="ara-product-content">
                        <h3 class="ara-product-title">Automatización de Procesos</h3>
                        <p class="ara-product-text">
                            Optimiza y automatiza los procesos manuales de tu empresa para reducir errores, ahorrar tiempo y aumentar la productividad de tu equipo.
                        </p>
                        <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary ara-product-cta">
                            Solicitar Asesoría
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                </article>

                {{-- IA: mismo caso que Automatización. --}}
                <article class="ara-product-card ara-product-card--plano reveal reveal-delay-6">
                    <div class="ara-product-media ara-product-media--verde">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
                        </svg>
                        <span class="ara-product-badge">Innovación</span>
                    </div>

                    <div class="ara-product-content">
                        <h3 class="ara-product-title">Inteligencia Artificial</h3>
                        <p class="ara-product-text">
                            Integramos IA en cada una de nuestras soluciones para analizar datos de forma inteligente y brindar una toma de decisiones asistida.
                        </p>
                        <a href="{{ route('contacto') }}" class="ara-btn ara-btn-green ara-product-cta">
                            Conocer Más
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <x-v2.cta-section 
        title="¿Necesitas una solución personalizada?"
        subtitle="Cuéntanos sobre los retos de tu empresa y te diseñaremos una solución a medida."
        buttonText="Hablar con un Especialista"
    />

    @include('aracode.components.v2.footer')
@endsection
