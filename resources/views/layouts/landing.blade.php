<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" href="{{ asset('img/isotipo.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Scripts -->
    @routes
    @php
        $parts = explode('::', $page['component']);
    @endphp
    @if (count($parts) > 1)
        @vite(['resources/js/app.js', "Modules/{$parts[0]}/Resources/assets/js/Pages/{$parts[1]}.vue"])
    @else
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @endif
    @inertiaHead
    <link rel="stylesheet" href="{{ asset('themes/personalLanding/assets/font/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/personalLanding/assets/css/aos.min.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/personalLanding/assets/css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/personalLanding/assets/css/jquery.fancybox.min.css') }}">

    <!-- main style -->
    <link rel="stylesheet" href="{{ asset('themes/personalLanding/assets/css/style.css') }}">
</head>

<body class="overflow-x-hidden">
    @inertia
    <script>
        window.assetUrl = @json(asset(''));
    </script>

</body>

</html>
