<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Global CPA') }} - Inscripción confirmada</title>
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
            text-align: justify;
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
        .btn-secondary {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 5px;
            background-color: #1e293b;
            color: #ffffff;
            text-decoration: none;
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
        .card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px 20px;
            margin: 16px 0;
        }
        .card p {
            margin: 0 0 4px;
            color: #475569;
        }
        .muted {
            font-size: 13px;
            color: #64748b;
        }
        .total {
            text-align: right;
            font-weight: bold;
            font-size: 20px;
            color: #2c3e50;
            margin: 0;
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
    @php
        // ============================================================================
        // AQUI DEBE IR EL ENLACE DEL VIDEO TUTORIAL DE INGRESO.
        // Pegar la URL dentro de las comillas, por ejemplo:
        // $tutorialVideoUrl = 'https://www.youtube.com/watch?v=XXXXXXXXXXX';
        // Mientras este en blanco, el boton "Video tutorial de ingreso" no se muestra.
        // (Tambien se puede enviar la URL desde el mailable al construir la vista.)
        // ============================================================================
        $tutorialVideoUrl = $tutorialVideoUrl ?? '';

        $studentName = $negotiation->client_data['full_name'] ?? $negotiation->client->full_name ?? 'estudiante';

        // Marca que firma el correo. Se deja fija para que la despedida diga siempre
        // "El equipo de Global CPA"; cambiar aqui si el nombre de marca cambia.
        $teamName = 'Global CPA';

        $documentType = ($document->invoice_type_doc ?? null) == '01' ? 'FACTURA' : 'BOLETA';
        $documentNumber = $documentType . ' ' . ($document->invoice_serie ?? '') . '-' . ($document->invoice_correlative ?? '');

        $items = $negotiation->items;
        $itemNames = $items->pluck('title')->filter()->values()->all();
        $onlyCourses = $items->every(fn ($item) => ($item->item_type ?? 'course') === 'course');

        $isInstallments = $negotiation->payment_type === 'installments';
        $schedule = is_array($negotiation->schedule) ? $negotiation->schedule : [];
        $firstDueDate = ! empty($schedule[0]['due_date'])
            ? \Illuminate\Support\Carbon::parse($schedule[0]['due_date'])->format('d/m/Y')
            : null;

        $approvalDate = ($negotiation->verified_at ?? now())->format('d/m/Y H:i');
        $totalPaid = number_format((float) $negotiation->total_price, 2);
        $hasCredentials = ! empty($credentials) && ($credentials['username'] ?? null);
    @endphp

    <br>
    <div class="container">
        <img style="width: 100%;" src="{{ asset('img/banner-email.jpg') }}" alt="Encabezado">
        <h1>¡Tu inscripción ha sido confirmada! 🎉</h1>
        <p>
            Hola <b>{{ $studentName }}</b>,
            nos alegra darte la bienvenida. Queremos informarte que tu inscripción ha sido procesada y
            aprobada con éxito. En este correo encontrarás tus credenciales de acceso y el detalle de tu compra.
        </p>

        @if ($document)
            <p style="text-align: center;">
                <span class="badge">📄 Comprobante: {{ $documentNumber }}</span>
            </p>
            <p>
                Tu documento en PDF y el XML del comprobante electrónico se encuentran adjuntos a este correo.
            </p>
        @endif

        @if ($hasCredentials)
            <div class="card">
                <h2 style="margin: 0 0 8px;">Acceso a la plataforma</h2>
                <p>
                    Ya tienes todo listo para comenzar a aprender. Ingresa con las siguientes credenciales:
                </p>
                <p style="margin: 8px 0 2px;">
                    <b>Usuario:</b> {{ $credentials['username'] }}
                </p>
                <p style="margin: 2px 0 12px;">
                    <b>Contraseña:</b> {{ $credentials['password'] ?? '---' }}
                </p>
                <p style="margin: 0; text-align: center;">
                    <a class="btn" href="{{ url('/login') }}" style="padding: 10px 22px; font-size: 14px;">Ingresar a la plataforma</a>
                    @if (! empty($tutorialVideoUrl))
                        <a class="btn-secondary" href="{{ $tutorialVideoUrl }}" target="_blank" rel="noopener" style="padding: 10px 22px; font-size: 14px; margin-left: 8px;">Video tutorial de ingreso</a>
                    @endif
                </p>
            </div>
            <p class="muted" style="text-align: center;">
                💡 Por tu seguridad, te recomendamos cambiar tu contraseña la primera vez que ingreses.
            </p>
        @endif

        <h2>Resumen de tu inscripción</h2>

        <p style="margin: 0 0 8px; font-weight: bold;">
            {{ $onlyCourses ? 'Cursos adquiridos:' : 'Cursos y servicios adquiridos:' }}
        </p>
        @if (! empty($itemNames))
            <ul>
                @foreach ($itemNames as $itemName)
                    <li>{{ $itemName }}</li>
                @endforeach
            </ul>
        @endif

        <p style="margin: 16px 0 0; font-weight: bold;">TOTAL PAGADO:</p>
        <p class="total">{{ $negotiation->currency }} {{ $totalPaid }}</p>

        <h2>Detalles del pago</h2>
        <p>
            <b>Tipo de pago:</b> {{ $isInstallments ? 'Pago en cuotas' : 'Pago único' }}<br>
            @if ($isInstallments)
                <b>Cuotas:</b> {{ count($schedule) }}<br>
                @if ($firstDueDate)
                    <b>Primera cuota vence:</b> {{ $firstDueDate }}<br>
                @endif
            @else
                <b>Plazo de pago:</b> {{ $negotiation->single_payment_days ?? '--' }} días<br>
            @endif
            <b>Medio de pago:</b> {{ \Modules\Commercial\Entities\CommercialNegotiation::paymentMethodLabel($negotiation->payment_method) }}<br>
            <b>Fecha de aprobación:</b> {{ $approvalDate }}
        </p>

        @if (($negotiation->invoice->invoice_type ?? null) === 'factura')
            <h2>Datos de facturación</h2>
            <p>
                <b>RUC:</b> {{ $negotiation->invoice->ruc ?? 'No registrado' }}<br>
                <b>Razón social:</b> {{ $negotiation->invoice->razon_social ?? 'No registrado' }}<br>
                <b>Dirección:</b> {{ $negotiation->invoice->direccion ?? 'No registrado' }}
            </p>
        @endif

        <h2>¡Éxitos en tu aprendizaje!</h2>
        <p>El equipo de {{ $teamName }}</p>

        <br>
        <footer>
            <p style="text-align: center; font-size: 15px; color: #fff;">
                &copy; Derechos Reservados {{ config('app.name') }} | Desarrollado por
                <a href="https://aracodeperu.com/">Aracode Smart Solutions</a>
            </p>
        </footer>
    </div>
</body>

</html>
