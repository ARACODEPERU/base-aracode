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

    /*
        El servicio que publica una plantilla del Banco de Plantillas
        (/paginas-web) es el quinto bloque del ecosistema. Enlaza a una página
        del propio sitio, así que abre en la misma pestaña.

        El precio de entrada sale de config/servicio_web.php a través de
        App\Support\ServicioWeb, igual que en /paginas-web y en el catálogo: aquí
        no se escribe ningún importe.
    */
    $precioDesdeServicio = \App\Support\ServicioWeb::precioDesde();
    $urlServicioWeb = \App\Support\ServicioWeb::url();
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
            {{-- Tres por fila y con la misma tarjeta compacta del home: el
                 ecosistema se presenta igual en la portada y en el catálogo.
                 La tarjeta compacta resume con la frase del producto —que es
                 además el texto alternativo de su imagen— e ignora `features`,
                 así que no hace falta pasar la lista de prestaciones. --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- KAPTA LMS --}}
                <x-v2.product-card
                    title="KAPTA LMS"
                    description="Plataforma SaaS para gestión y formación educativa completa."
                    :href="$sitiosProducto['kapta']"
                    :nuevaPestana="true"
                    image="{{ asset('themes/webpage/images/misc/s1.jpg') }}"
                    {{-- badge="Plataforma SaaS" --}}
                    :delay="1"
                    :compacta="true"
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
                    {{-- badge="SUNAT" --}}
                    :delay="2"
                    :compacta="true"
                />

                {{-- Pichanguero --}}
                <x-v2.product-card
                    title="Pichanguero"
                    description="Plataforma para organizar torneos de fútbol: fixture automático, posiciones y estadísticas en tiempo real."
                    :href="$sitiosProducto['pichanguero']"
                    :nuevaPestana="true"
                    image="{{ asset('themes/webpage/images/misc/s3.jpg') }}"
                    {{-- badge="App móvil" --}}
                    :delay="4"
                    :compacta="true"
                />

                {{-- Desarrollo --}}
                <x-v2.product-card
                    title="Desarrollo a Medida"
                    description="Creamos soluciones de software personalizadas para las necesidades específicas de tu empresa."
                    :href="route('solucion_desarrollo')"
                    image="{{ asset('themes/webpage/images/productos/desarrollo-medida.webp') }}"
                    {{-- badge="Personalizado" --}}
                    :delay="3"
                    :compacta="true"
                />

                {{-- Automatización: servicio, no producto con web propia. Usa la
                     misma tarjeta compacta del resto y su misma estructura
                     (franja superior + texto debajo) para que la fila se
                     alinee; la franja lleva su imagen diseñada, con la misma
                     proporción que el archivo para que no queden bandas. --}}
                <article class="ara-product-card ara-product-card--plano ara-product-card--compacta reveal reveal-delay-5">
                    <div class="ara-product-media"
                         style="--ara-product-media-bg: url('{{ asset('themes/webpage/images/productos/automatizacion-procesos.webp') }}'); --ara-product-media-ratio: 960 / 600">
                        <img src="{{ asset('themes/webpage/images/productos/automatizacion-procesos.webp') }}"
                             alt="Automatización de procesos" loading="lazy" decoding="async">
                        <span class="ara-product-badge">Servicios</span>
                    </div>

                    <div class="ara-product-content">
                        <h3 class="ara-product-title">Automatización de Procesos</h3>
                        <p class="ara-product-text">
                            Optimiza y automatiza los procesos manuales de tu empresa para reducir errores, ahorrar tiempo y aumentar la productividad de tu equipo.
                        </p>
                        <a href="{{ route('contacto') }}" data-asesoria class="ara-btn ara-btn-primary ara-product-cta">
                            Solicitar Asesoría
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                </article>

                {{-- IA: mismo caso que Automatización, con su imagen diseñada en
                     lugar del degradado con el icono. --}}
                <article class="ara-product-card ara-product-card--plano ara-product-card--compacta reveal reveal-delay-6">
                    <div class="ara-product-media"
                         style="--ara-product-media-bg: url('{{ asset('themes/webpage/images/productos/inteligencia-artificial.webp') }}'); --ara-product-media-ratio: 960 / 600">
                        <img src="{{ asset('themes/webpage/images/productos/inteligencia-artificial.webp') }}"
                             alt="Inteligencia artificial" loading="lazy" decoding="async">
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

                {{-- Páginas Web: el servicio que publica una plantilla del Banco de
                     Plantillas con la marca y el dominio del cliente. Lleva su
                     propia imagen diseñada y su color (morado): el azul y el
                     verde ya son de Automatización e IA.

                     El enlace es interno (misma pestaña) y el precio de entrada
                     sale de config/servicio_web.php: el mismo que publican
                     /paginas-web, el catálogo y la ficha de cada plantilla.
                     Lo que incluye el plan se detalla en /paginas-web: aquí
                     basta la frase con el precio, como en el resto. --}}
                <article class="ara-product-card ara-product-card--plano ara-product-card--compacta reveal reveal-delay-7">
                    <div class="ara-product-media"
                         style="--ara-product-media-bg: url('{{ asset('themes/webpage/images/productos/paginas-web.webp') }}'); --ara-product-media-ratio: 960 / 600">
                        <img src="{{ asset('themes/webpage/images/productos/paginas-web.webp') }}"
                             alt="Páginas web con plantillas profesionales" loading="lazy" decoding="async">
                        <span class="ara-product-badge">Servicios</span>
                    </div>

                    <div class="ara-product-content">
                        <h3 class="ara-product-title">Páginas Web</h3>
                        <p class="ara-product-text">
                            Elige una plantilla de nuestro catálogo y la publicamos con tu marca y tu dominio.
                            Desde {{ $precioDesdeServicio }} al mes, sin costo de implementación.
                        </p>
                        <a href="{{ $urlServicioWeb }}" class="ara-btn ara-btn-primary ara-product-cta">
                            Ver los planes
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
