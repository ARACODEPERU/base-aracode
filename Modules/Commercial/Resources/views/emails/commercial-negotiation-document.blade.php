<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ env('APP_NAME', 'Global CPA') }} - Acuerdo aprobado</title>
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
            text-align: justify;
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
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            background-color: #d1fae5;
            color: #065f46;
            font-size: 13px;
            font-weight: bold;
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
        <h1>¡Tu acuerdo fue aprobado!</h1>
        <p>
            Hola <b>{{ $negotiation->client_data['full_name'] ?? $negotiation->client->full_name ?? 'cliente' }}</b>,
            queremos informarte que el acuerdo <b>{{ $negotiation->title }}</b> fue aprobado.
            En este correo encontrarás los detalles del acuerdo y adjunto tu comprobante de pago.
        </p>

        @if ($document)
            <p style="text-align: center;">
                <span class="badge">Comprobante: {{ $document->invoice_type_doc == '01' ? 'FACTURA' : 'BOLETA' }} {{ $document->invoice_serie }}-{{ $document->invoice_correlative }}</span>
            </p>
            <p>
                Tu comprobante de venta está adjunto en este correo en formato PDF.
            </p>
        @endif

        @if (! empty($credentials) && ($credentials['username'] ?? null))
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px 20px; margin: 16px 0;">
                <h2 style="margin: 0 0 8px; font-size: 18px; color: #1e293b;">Acceso a la plataforma</h2>
                <p style="margin: 0 0 4px; color: #475569;">
                    Ya puedes ingresar a la plataforma con las siguientes credenciales:
                </p>
                <p style="margin: 8px 0 2px;">
                    <b>Usuario:</b> {{ $credentials['username'] }}
                </p>
                <p style="margin: 2px 0 8px;">
                    <b>Contraseña:</b> {{ $credentials['password'] ?? '---' }}
                </p>
                <p style="margin: 8px 0 0; text-align: center;">
                    <a class="btn" href="{{ url('/login') }}" style="padding: 10px 22px; font-size: 14px;">Ingresar a la plataforma</a>
                </p>
            </div>
            <p style="font-size: 13px; color: #64748b;">
                Te recomendamos cambiar tu contraseña la primera vez que ingreses.
            </p>
        @endif

        @php
            $presentationMode = \App\Helpers\Invoice\DocumentPresentation::modeForCount($negotiation->items->count());
            $presentationNames = \App\Helpers\Invoice\DocumentPresentation::names($negotiation->items);
            $presentationTotal = number_format((float) $negotiation->total_price, 2);
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
                                S/ {{ number_format((float) $item->price, 2) }}
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

        <p>
            <b>Tipo de pago:</b>
            {{ $negotiation->payment_type === 'installments' ? 'Pago en cuotas' : 'Pago unico' }}<br>
            @if ($negotiation->payment_type === 'installments' && is_array($negotiation->schedule) && count($negotiation->schedule) > 0)
                <b>Primera cuota (vencimiento):</b> {{ $negotiation->schedule[0]['due_date'] }}<br>
                <b>Cantidad de cuotas:</b> {{ count($negotiation->schedule) }}<br>
            @elseif ($negotiation->payment_type === 'single')
                <b>Plazo de pago:</b> {{ $negotiation->single_payment_days ?? '--' }} dias<br>
            @endif
            <b>Medio de pago:</b> {{ $negotiation->payment_method }}<br>
            <b>Fecha de aprobacion:</b> {{ now()->format('d/m/Y H:i') }}
        </p>

        @if (($negotiation->invoice->invoice_type ?? null) === 'factura')
            <p>
                <b>RUC:</b> {{ $negotiation->invoice->ruc ?? 'No registrado' }}<br>
                <b>Razon social:</b> {{ $negotiation->invoice->razon_social ?? 'No registrado' }}<br>
                <b>Direccion:</b> {{ $negotiation->invoice->direccion ?? 'No registrado' }}
            </p>
        @endif

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
