<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Carnés escolares - {{ count($cards) }} alumnos</title>
    <style>
        @page {
            margin: 8mm;
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
    <style>
        /*
         * Cuadrícula de impresión masiva: 3 columnas x 3 filas por hoja A4 (210x297mm).
         * Cada carné conserva su tamaño original (54x85.6mm) sin deformar; la celda
         * agrega 3mm por lado como espacio de recorte (6mm entre carnés vecinos).
         */
        .pagina-carnets {
            page-break-after: always;
        }

        .pagina-carnets:last-child {
            page-break-after: auto;
        }

        .celda-carnet {
            display: inline-block;
            width: 60mm;
            padding: 3mm;
            vertical-align: top;
        }
    </style>
</head>
<body>
    @php($chunks = array_chunk($cards, 9))
    @foreach ($chunks as $chunk)
        <div class="pagina-carnets">
            @foreach ($chunk as $card)
                <div class="celda-carnet">
                    @include('academic::cards.partials.carnet_item', [
                        'student' => $card['student'],
                        'enrollment' => $card['enrollment'],
                        'school' => $school,
                        'logoDataUri' => $logoDataUri,
                        'qrDataUri' => $card['qrDataUri'],
                    ])
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
