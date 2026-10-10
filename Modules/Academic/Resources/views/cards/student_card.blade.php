<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Carné - {{ $student->person?->full_name }}</title>
    <style>
        @page {
            margin: 12mm;
            size: A4 portrait;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            color: #1e293b;
        }
    </style>
    <style>
    @include('academic::cards.partials.carnet_css')
    </style>
</head>
<body>
    @include('academic::cards.partials.carnet_item')
</body>
</html>
