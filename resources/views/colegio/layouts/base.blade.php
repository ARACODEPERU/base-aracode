{{--
    Layout base del sitio del colegio.
    Blade puro: sin Inertia, sin Vue y sin assets de ARACODE, para que el sitio
    pueda publicarse en su propio dominio junto con resources/views/colegio,
    resources/css/colegio y public/themes/colegio.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Colegio')</title>
    <meta name="description" content="@yield('description', 'Sitio institucional del colegio y acceso al aula virtual.')">
    <meta name="theme-color" content="#173F35">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'Colegio')">
    <meta property="og:description" content="@yield('description', 'Sitio institucional del colegio y acceso al aula virtual.')">
    <meta property="og:url" content="{{ url()->current() }}">

    {{-- Tipografía redonda: Fredoka para titulares y Nunito para texto --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Estilos propios del sitio (fuente: resources/css/colegio) --}}
    <link rel="stylesheet" href="{{ asset('themes/colegio/css/colegio.css') }}">

    @stack('head')
</head>
<body class="@yield('body_class')">
    @yield('content')

    @stack('scripts')
</body>
</html>
