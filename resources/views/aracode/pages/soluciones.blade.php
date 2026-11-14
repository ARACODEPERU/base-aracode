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

    {{-- Hero (formato común de páginas internas) --}}
    <x-v2.hero-pagina
        etiqueta="Nuestras Soluciones"
        titulo="Soluciones que"
        resaltado="impulsan"
        resto=" tu negocio"
        bajada="Ofrecemos un ecosistema completo de productos y servicios tecnológicos diseñados para la transformación digital de tu empresa."
    />

    {{-- Products --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Siete soluciones con la misma tarjeta del home: el diseño del
                 producto a sangre, el rubro en píldora, el nombre, la frase
                 corta y la píldora blanca «Leer más» sobre el velo navy.
                 Las frases se dejan en dos o tres líneas: la tarjeta tiene
                 alto fijo y el texto vive sobre el pie oscuro. --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- KAPTA LMS --}}
                <x-v2.product-card
                    title="KAPTA LMS"
                    description="Plataforma SaaS para gestión y formación educativa."
                    :href="$sitiosProducto['kapta']"
                    :nuevaPestana="true"
                    image="{{ asset('themes/webpage/images/misc/s1.jpg') }}"
                    badge="Educación"
                    variante="sobrepuesta"
                    etiqueta="Leer más"
                    :delay="1"
                />

                {{-- KIRAFACT: el nombre comercial se escribe como el producto lo
                     publica (config/kirafact.php «titulo_marca» y los títulos de
                     su propio sitio). Una sola grafía en todo el ecosistema. --}}
                <x-v2.product-card
                    title="KIRAFACT"
                    description="Facturación electrónica y gestión comercial para empresas."
                    :href="$sitiosProducto['kirafact']"
                    :nuevaPestana="true"
                    image="{{ asset('themes/webpage/images/misc/s2.jpg') }}"
                    badge="Empresas"
                    variante="sobrepuesta"
                    etiqueta="Leer más"
                    :delay="2"
                />

                {{-- Pichanguero --}}
                <x-v2.product-card
                    title="Pichanguero"
                    description="Plataforma para organizar torneos de fútbol: fixture, posiciones y estadísticas."
                    {{-- Igual que en la portada: la tarjeta abre la ficha del
                         producto en el corporativo (/pichanguero), que la explica
                         y desde ahí enlaza a su web oficial. Misma pestaña. --}}
                    :href="route('solucion_pichanguero')"
                    image="{{ asset('themes/webpage/images/misc/s3.jpg') }}"
                    badge="Deportes"
                    variante="sobrepuesta"
                    etiqueta="Leer más"
                    :delay="3"
                />

                {{-- Desarrollo: enlace interno, así que abre en la misma
                     pestaña. --}}
                <x-v2.product-card
                    title="Desarrollo a Medida"
                    description="Software a medida para las necesidades específicas de tu empresa."
                    :href="route('solucion_desarrollo')"
                    image="{{ asset('themes/webpage/images/productos/desarrollo-medida.webp') }}"
                    badge="Personalizado"
                    variante="sobrepuesta"
                    etiqueta="Leer más"
                    :delay="4"
                />

                {{-- Automatización: servicio, no producto con web propia. El
                     botón abre el formulario de asesoría (`data-asesoria`), no
                     navega a otra página. --}}
                <x-v2.product-card
                    title="Automatización de Procesos"
                    description="Automatiza los procesos manuales y reduce errores y tiempos."
                    :href="route('contacto')"
                    :asesoria="true"
                    image="{{ asset('themes/webpage/images/productos/automatizacion-procesos.webp') }}"
                    badge="Servicios"
                    variante="sobrepuesta"
                    etiqueta="Solicitar asesoría"
                    :delay="5"
                />

                {{-- IA --}}
                <x-v2.product-card
                    title="Inteligencia Artificial"
                    description="Inteligencia artificial aplicada a tus datos para decidir mejor."
                    :href="route('contacto')"
                    image="{{ asset('themes/webpage/images/productos/inteligencia-artificial.webp') }}"
                    badge="Innovación"
                    variante="sobrepuesta"
                    etiqueta="Conocer más"
                    :delay="6"
                />

                {{-- Páginas Web: el servicio que publica una plantilla del Banco
                     de Plantillas con la marca y el dominio del cliente. El
                     enlace es interno y el precio de entrada sale de
                     config/servicio_web.php, igual que en /paginas-web y en el
                     catálogo: aquí no se escribe ningún importe. --}}
                <x-v2.product-card
                    title="Páginas Web"
                    description="Web con plantilla del catálogo, tu marca y tu dominio. Desde {{ $precioDesdeServicio }} al mes."
                    :href="$urlServicioWeb"
                    image="{{ asset('themes/webpage/images/productos/paginas-web.webp') }}"
                    badge="Servicios"
                    variante="sobrepuesta"
                    etiqueta="Ver los planes"
                    :delay="7"
                />
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
