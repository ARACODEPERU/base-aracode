<!DOCTYPE html>
<html lang="es">

<head>
    <title>Aracode Smart Solutions</title>
    <link rel="icon" href="{{ asset('themes/webpage/images/icon.png') }}" type="image/gif" sizes="16x16">
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" >
    <meta content="Aracode Smart Solutions" name="description" >
    <meta content="" name="keywords" >
    <meta content="" name="author" >
    <!-- CSS Files
    ================================================== -->
    <link href="{{ asset('themes/webpage/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" id="bootstrap">
    <link href="{{ asset('themes/webpage/css/vendors.css') }}" rel="stylesheet" type="text/css" >
    <link href="{{ asset('themes/webpage/css/style.css') }}" rel="stylesheet" type="text/css" >
    <!-- color scheme -->
    <link id="colors" href="{{ asset('themes/webpage/css/colors/scheme-01.css') }}" rel="stylesheet" type="text/css" >

</head>

<body class="dark-scheme">

    @yield('content')
    
    <!-- Javascript Files
    ================================================== -->
    <script src="{{ asset('themes/webpage/js/vendors.js') }}"></script>
    <script src="{{ asset('themes/webpage/js/designesia.js') }}"></script>
    <script src="{{ asset('themes/webpage/js/countdown-custom.js') }}"></script>
    <script src="{{ asset('themes/webpage/js/custom-marquee.js') }}"></script>

</body>

</html>