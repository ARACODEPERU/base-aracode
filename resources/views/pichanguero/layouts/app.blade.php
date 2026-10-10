{{--
    Layout del sitio Pichanguero.
    Blade puro: sin Inertia, sin Vue y sin assets de ARACODE.
    Se puede copiar junto con resources/views/pichanguero a su propio dominio.
--}}
<!DOCTYPE html>
<html lang="es" class="no-js">
<head>
    <meta charset="utf-8">

    {{-- Sin JS el contenido se ve igual: los bloques animados solo se ocultan cuando hay JS. --}}
    <script>document.documentElement.classList.remove('no-js');document.documentElement.classList.add('js');</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- SEO --}}
    <title>@yield('title', 'Pichanguero — Organiza tu torneo de fútbol')</title>
    <meta name="description" content="@yield('description', 'Pichanguero es la plataforma para organizar campeonatos de fútbol: fixture automático, tabla de posiciones, estadísticas de jugadores y resultados en vivo.')">
    <meta name="author" content="Pichanguero">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Pichanguero">
    <meta property="og:title" content="@yield('og_title', 'Pichanguero — Organiza tu torneo de fútbol')">
    <meta property="og:description" content="@yield('og_description', 'Fixture automático, tabla de posiciones, estadísticas y resultados en vivo para tu campeonato.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('themes/pichanguero/images/og-image.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Icono --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('themes/pichanguero/images/favicon.svg') }}">

    {{-- Color de la barra del navegador en móvil --}}
    <meta name="theme-color" content="#071F36">

    {{-- Tipografía: Montserrat para titulares, Inter para interfaz y datos --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">

    {{-- Estilos propios del sitio (fuente: resources/css/pichanguero) --}}
    <link rel="stylesheet" href="{{ asset('themes/pichanguero/css/pichanguero.css') }}">

    @stack('head')
</head>
<body class="font-pg antialiased">

    @yield('content')

    {{-- JS propio del sitio --}}
    <script src="{{ asset('themes/pichanguero/js/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
