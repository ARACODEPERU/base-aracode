<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ env('APP_NAME', 'Global CPA') }} - Negociacion confirmada</title>
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
        h2 {
            margin: 24px 0 8px;
            font-size: 18px;
            color: #1e293b;
        }
        p {
            line-height: 22px;
            color: #555;
        }
        ul {
            padding-left: 20px;
            margin: 0 0 16px;
            color: #555;
        }
        li {
            line-height: 22px;
            margin-bottom: 6px;
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
            color: #555;
            line-height: 20px;
        }
        .data-table th {
            width: 34%;
            background-color: #f1f5f9;
            color: #1e293b;
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
        .total {
            text-align: right;
            font-weight: bold;
            font-size: 18px;
            color: #2c3e50;
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

        <h1>El cliente confirmo su negociacion</h1>

        @php
            $clientName = $client?->full_name
                ?: trim(($client?->father_lastname ?? '') . ' ' . ($client?->mother_lastname ?? '') . ' ' . ($client?->names ?? ''))
                ?: 'Cliente sin nombre registrado';

            $presentationMode = \App\Helpers\Invoice\DocumentPresentation::modeForCount($negotiation->items->count());
            $presentationNames = \App\Helpers\Invoice\DocumentPresentation::names($negotiation->items);
            $presentationTotal = number_format((float) $negotiation->total_price, 2);
            $advisorName = $negotiation->creator?->name ?: ($negotiation->contact_detail ?: 'No registrado');
        @endphp

        <p>
            <b>{{ $clientName }}</b> ingreso al enlace de su cotizacion <b>{{ $negotiation->title }}</b>,
            la acepto y envio sus datos de registro. El siguiente paso es continuar con el proceso
            de verificacion de la negociacion.
        </p>

        <p style="text-align: center; margin-top: 24px;">
            <a href="{{ $processUrl }}" class="btn">
                Continuar con el proceso
            </a>
        </p>
        <p style="text-align: center; font-size: 13px; color: #888;">
            Si el boton no funciona, copia y pega este enlace en tu navegador:
        </p>
        <p style="text-align: center; font-size: 12px; color: #555; word-break: break-all;">
            {{ $processUrl }}
        </p>

        <h2>Datos del cliente</h2>
        <table class="data-table">
            <tr>
                <th>Nombre</th>
                <td>{{ $clientName }}</td>
            </tr>
            <tr>
                <th>Documento</th>
                <td>{{ $client?->number ?: 'No registrado' }}</td>
            </tr>
            <tr>
                <th>Correo</th>
                <td>{{ $client?->email ?: 'No registrado' }}</td>
            </tr>
            <tr>
                <th>Telefono</th>
                <td>{{ $client?->telephone ?: 'No registrado' }}</td>
            </tr>
            @if ($client?->ubigeo_description)
                <tr>
                    <th>Ubicacion</th>
                    <td>{{ $client->ubigeo_description }}</td>
                </tr>
            @endif
            @if ($client?->address)
                <tr>
                    <th>Direccion</th>
                    <td>{{ $client->address }}</td>
                </tr>
            @endif
        </table>

        <h2>Resumen de la negociacion</h2>

        @if ($presentationMode === 'list')
            <p style="font-weight: bold; color: #333; margin: 20px 0 10px;">
                Cursos adquiridos
            </p>
            <ul>
                @foreach ($presentationNames as $name)
                    <li>{{ $name }}</li>
                @endforeach
            </ul>
        @elseif ($presentationMode === 'summary')
            <p style="font-weight: bold; color: #333; margin: 20px 0 10px;">
                Compra de Cursos de Capacitacion
            </p>
            <p style="margin: 0 0 16px;">
                Cantidad de cursos: <strong>{{ $negotiation->items->count() }}</strong>
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
            </table>
        @endif

        <p class="total">TOTAL A PAGAR</p>
        <p class="total">{{ $negotiation->currency }} {{ $presentationTotal }}</p>

        <table class="data-table">
            <tr>
                <th>Asesor de la negociacion</th>
                <td>{{ $advisorName }}</td>
            </tr>
            <tr>
                <th>Medio de pago</th>
                <td>{{ \Modules\Commercial\Entities\CommercialNegotiation::paymentMethodLabel($negotiation->payment_method) ?: 'Por definir' }}</td>
            </tr>
            @if ($negotiation->single_payment_days)
                <tr>
                    <th>Plazo de pago</th>
                    <td>{{ $negotiation->single_payment_days }} dias</td>
                </tr>
            @endif
            <tr>
                <th>Estado</th>
                <td>Confirmada por el cliente</td>
            </tr>
            <tr>
                <th>Fecha de confirmacion</th>
                <td>{{ now()->format('d/m/Y H:i') }}</td>
            </tr>
        </table>

        <p style="font-size: 13px; color: #888;">
            Recibes este correo porque eres el asesor de esta negociacion o tienes un rol de
            administrador. Cualquiera de ellos puede continuar con el proceso.
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
