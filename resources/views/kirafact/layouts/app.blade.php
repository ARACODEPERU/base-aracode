{{--
    Layout del sitio KiraFact.
    Blade puro: sin Inertia, sin Vue y sin assets de ARACODE.
--}}
<!DOCTYPE html>
<html lang="es" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Sin JS el contenido se ve igual: los bloques animados solo se ocultan cuando hay JS. --}}
    <script>document.documentElement.classList.remove('no-js');document.documentElement.classList.add('js');</script>

    {{-- SEO --}}
    <title>@yield('title', 'KiraFact — Facturación electrónica y gestión comercial')</title>
    <meta name="description" content="@yield('description', 'KiraFact emite facturas, boletas y notas electrónicas con integración SUNAT, control de inventario y reportes de ventas.')">
    <meta name="author" content="KiraFact">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="KiraFact">
    <meta property="og:title" content="@yield('og_title', 'KiraFact — Facturación electrónica y gestión comercial')">
    <meta property="og:description" content="@yield('og_description', 'Comprobantes electrónicos, integración SUNAT, inventario y reportes de ventas para tu empresa.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('themes/kirafact/images/og-image.webp') }}">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Icono --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('themes/kirafact/images/favicon.svg') }}">

    {{-- Tipografía --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Estilos propios del sitio (fuente: resources/css/kirafact) --}}
    <link rel="stylesheet" href="{{ asset('themes/kirafact/css/kirafact.css') }}">

    @stack('head')
</head>
<body class="font-kf antialiased">

    @yield('content')

    {{-- JS propio del sitio --}}
    <script src="{{ asset('themes/kirafact/js/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
