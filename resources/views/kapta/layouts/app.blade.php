{{--
    Layout del sitio KAPTA LMS.
    Blade puro: sin Inertia, sin Vue y sin assets de ARACODE.
    Se puede copiar junto con resources/views/kapta a su propio dominio.
--}}
<!DOCTYPE html>
<html lang="es" class="no-js">
<head>
    <meta charset="utf-8">

    {{-- Sin JS el contenido se ve igual: los bloques animados solo se ocultan cuando hay JS. --}}
    <script>document.documentElement.classList.remove('no-js');document.documentElement.classList.add('js');</script>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- SEO --}}
    <title>@yield('title', 'KAPTA LMS — Gestiona tu formación en una sola plataforma')</title>
    <meta name="description" content="@yield('description', 'KAPTA LMS administra cursos, alumnos, matrículas, evaluaciones y certificados desde una sola plataforma, pensada para academias, institutos y organizaciones que capacitan.')">
    <meta name="author" content="KAPTA LMS — ARACODE Smart Solutions">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="robots" content="index, follow">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_PE">
    <meta property="og:site_name" content="KAPTA LMS">
    <meta property="og:title" content="@yield('og_title', 'KAPTA LMS — Gestiona tu formación en una sola plataforma')">
    <meta property="og:description" content="@yield('og_description', 'Cursos, aulas virtuales, matrículas, evaluaciones y certificados en un solo lugar.')">
    <meta property="og:url" content="{{ url()->current() }}">
    {{-- Pendiente: versión de la imagen social con la marca aplicada (1200×630). --}}
    <meta property="og:image" content="{{ asset('themes/kapta/images/bg-hero.webp') }}">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Icono del sitio: por ahora una marca de posición, no el logotipo oficial --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('themes/kapta/images/favicon.svg') }}">

    {{-- Color de la barra del navegador en móvil (azul marino de la marca) --}}
    <meta name="theme-color" content="#0B1740">

    {{-- Tipografía: Plus Jakarta Sans para titulares, Inter para interfaz y datos --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    {{--
        Estilos y scripts propios del sitio (fuente: resources/css/kapta).
        La versión va atada a la fecha del archivo compilado: al volver a compilar,
        el navegador descarga la hoja nueva en lugar de quedarse con la anterior.
    --}}
    @php
        $tema = public_path('themes/kapta');
        $version = function (string $relativa) use ($tema): string {
            return is_file($tema . '/' . $relativa) ? '?v=' . filemtime($tema . '/' . $relativa) : '';
        };
    @endphp
    <link rel="stylesheet" href="{{ asset('themes/kapta/css/kapta.css') . $version('css/kapta.css') }}">

    @stack('head')
</head>
<body class="font-ka antialiased">

    @yield('content')

    {{-- JS propio del sitio --}}
    <script src="{{ asset('themes/kapta/js/app.js') . $version('js/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
