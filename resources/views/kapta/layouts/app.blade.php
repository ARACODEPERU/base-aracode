{{--
    Layout del sitio KAPTA.
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
    <title>@yield('title', 'KAPTA — Plataforma de gestión y formación educativa')</title>
    <meta name="description" content="@yield('description', 'KAPTA es la plataforma para enseñar y certificar en línea: cursos, aulas virtuales, evaluaciones, matrículas y certificados automáticos.')">
    <meta name="author" content="KAPTA">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="KAPTA">
    <meta property="og:title" content="@yield('og_title', 'KAPTA — Plataforma de gestión y formación educativa')">
    <meta property="og:description" content="@yield('og_description', 'Cursos, aulas virtuales, evaluaciones y certificados automáticos para tu institución.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('themes/kapta/images/og-image.webp') }}">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Icono --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('themes/kapta/images/favicon.svg') }}">

    {{-- Tipografía --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Estilos propios del sitio (fuente: resources/css/kapta) --}}
    <link rel="stylesheet" href="{{ asset('themes/kapta/css/kapta.css') }}">

    @stack('head')
</head>
<body class="font-ka antialiased">

    @yield('content')

    {{-- JS propio del sitio --}}
    <script src="{{ asset('themes/kapta/js/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
