<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ env('APP_NAME', 'Global CPA') }} - Tu cotizacion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 720px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        p {
            line-height: 22px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            margin: 20px 0;
        }
        table th {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #3498db;
            color: #ffffff;
            font-weight: bold;
            background-color: #3498db;
        }
        table td {
            padding: 12px 15px;
            border: 1px solid #e5e7eb;
        }
        .btn {
            display: inline-block;
            padding: 14px 28px;
            border-radius: 5px;
            background-color: #ff8607;
            color: #ffffff;
            text-decoration: none;
        }
        .btn:hover {
            background: #010101;
        }
        footer {
            padding: 2px 15px;
            text-align: center;
            background: #000;
            color: #fff;
        }
        footer a {
            text-decoration: none;
            color: yellow;
        }
    </style>
</head>

<body>
    <br>
    <div class="container">
        <img style="width: 100%;" src="{{ asset('img/banner-email.jpg') }}" alt="Encabezado">
        <h1>Tu cotizacion</h1>
        <p>
            Hola, hemos preparado una cotizacion para ti: <b>{{ $negotiation->title }}</b>.
            Haz clic en el boton de abajo para revisarla y, si estas de acuerdo, confirmar tu compra.
        </p>

        @php
            $presentationMode = \App\Helpers\Invoice\DocumentPresentation::modeForCount($negotiation->items->count());
            $presentationNames = \App\Helpers\Invoice\DocumentPresentation::names($negotiation->items);
            $presentationTotal = number_format((float) $negotiation->total_price, 2);
            $quoteUrl = route('comm_negotiations_public_show', $negotiation->token);
        @endphp

        @if ($presentationMode === 'list')
            <p style="text-align: left; font-weight: bold; font-size: 16px; color: #333; margin: 20px 0 10px;">
                Cursos adquiridos
            </p>
            <ul style="padding-left: 20px; margin: 0 0 20px; color: #555;">
                @foreach ($presentationNames as $name)
                    <li style="margin-bottom: 6px;">{{ $name }}</li>
                @endforeach
            </ul>
            <p style="text-align: right; font-weight: bold; font-size: 16px; color: #333; margin: 20px 0 0;">
                TOTAL A PAGAR
            </p>
            <p style="text-align: right; font-weight: bold; font-size: 20px; color: #2c3e50; margin: 0;">
                {{ $negotiation->currency }} {{ $presentationTotal }}
            </p>
        @elseif ($presentationMode === 'summary')
            <p style="text-align: left; font-weight: bold; font-size: 16px; color: #333; margin: 20px 0 10px;">
                Compra de Cursos de Capacitacion
            </p>
            <p style="text-align: left; color: #555; margin: 0 0 20px;">
                Cantidad de cursos: <strong>{{ $negotiation->items->count() }}</strong>
            </p>
            <p style="text-align: right; font-weight: bold; font-size: 16px; color: #333; margin: 20px 0 0;">
                TOTAL A PAGAR
            </p>
            <p style="text-align: right; font-weight: bold; font-size: 20px; color: #2c3e50; margin: 0;">
                {{ $negotiation->currency }} {{ $presentationTotal }}
            </p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="text-align: right;">Precio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($negotiation->items as $item)
                        <tr>
                            <td>{{ $item->title }}</td>
                            <td style="text-align: right;">
                                {{ $negotiation->currency }} {{ number_format((float) $item->price, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background-color: #2c3e50;">
                        <td style="padding: 15px; text-align: right; color: white; font-weight: bold; font-size: 16px;">TOTAL:</td>
                        <td style="padding: 15px; text-align: right; color: white; font-weight: bold; font-size: 18px;">
                            {{ $negotiation->currency }} {{ $presentationTotal }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        @endif

        <p style="text-align: center; margin-top: 24px;">
            <a href="{{ $quoteUrl }}" class="btn">
                Ver mi cotizacion
            </a>
        </p>
        <p style="text-align: center; font-size: 13px; color: #888;">
            Si el boton no funciona, copia y pega este enlace en tu navegador:
        </p>
        <p style="text-align: center; font-size: 12px; color: #555; word-break: break-all;">
            {{ $quoteUrl }}
        </p>

        <p>
            <b>Asesor:</b> {{ $negotiation->contact_detail ?: 'No registrado' }}<br>
            <b>Medio de pago:</b> {{ $negotiation->payment_method ?: 'Por definir' }}<br>
            @if ($negotiation->single_payment_days)
                <b>Plazo de pago:</b> {{ $negotiation->single_payment_days }} dias<br>
            @endif
            <b>Fecha de cotizacion:</b> {{ now()->format('d/m/Y H:i') }}
        </p>

        <br>
        <p style="text-align: center; font-size: 14px;">
            {{ env('APP_NAME', 'Global CPA') }}
        </p>
        <footer>
            <p style="text-align: center; font-size: 15px;">
                &copy; Derechos Reservados {{ env('APP_NAME') }} | Desarrollado por
                <a href="https://aracodeperu.com/">Aracode Smart Solutions</a>
            </p>
        </footer>
    </div>
</body>

</html>
