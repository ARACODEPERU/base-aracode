{{--
    Layout del sitio KIRAFACT.

    Blade puro: sin Inertia, sin Vue y sin assets de ARACODE. Este layout arma
    las tres piezas que comparten todas las páginas (cabecera, contenido y pie);
    cada página solo aporta sus secciones.

    Los metadatos salen de config('kirafact.seo'), así que el título, la
    descripción y la imagen para compartir se cambian en un solo sitio.
--}}
@php
    $seo = config('kirafact.seo');
    $canonical = $seo['canonical'] ?: url()->current();
@endphp
<!DOCTYPE html>
<html lang="es" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Sin JS el contenido se ve igual: los bloques que aparecen al desplazar
         solo se ocultan cuando hay JS. --}}
    <script>document.documentElement.classList.remove('no-js');document.documentElement.classList.add('js');</script>

    {{-- SEO --}}
    <title>@yield('title', $seo['title'])</title>
    <meta name="description" content="@yield('description', $seo['description'])">
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="theme-color" content="{{ $seo['color_tema'] }}">

    {{-- Metadatos para compartir --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('kirafact.marca.nombre') }}">
    <meta property="og:locale" content="es_PE">
    <meta property="og:title" content="@yield('og_title', $seo['og_title'])">
    <meta property="og:description" content="@yield('og_description', $seo['og_description'])">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ asset($seo['og_image']) }}">
    <meta property="og:image:type" content="{{ $seo['og_image_tipo'] }}">
    <meta property="og:image:width" content="{{ $seo['og_image_ancho'] }}">
    <meta property="og:image:height" content="{{ $seo['og_image_alto'] }}">
    <meta property="og:image:alt" content="{{ config('kirafact.marca.nombre') }} — {{ config('kirafact.marca.descriptor') }}">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Icono del sitio (provisional, ver README) --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('themes/kirafact/images/favicon.svg') }}">

    {{-- Tipografía --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Estilos propios del sitio (fuente: resources/css/kirafact) --}}
    <link rel="stylesheet" href="{{ asset('themes/kirafact/css/kirafact.css') }}">

    @stack('head')
</head>
<body class="font-kf antialiased">

    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[70] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-kf-navy">
        Saltar al contenido
    </a>

    @include('kirafact.components.navbar')

    <main id="contenido">
        @yield('content')
    </main>

    @include('kirafact.components.footer')

    {{-- JS propio del sitio --}}
    <script src="{{ asset('themes/kirafact/js/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
