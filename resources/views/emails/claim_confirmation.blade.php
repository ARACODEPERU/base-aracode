<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { background-color: #007bff; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; font-size: 0.8em; color: #666; padding: 15px; border-top: 1px solid #eee; margin-top: 20px; }
        .highlight { font-weight: bold; color: #007bff; }
        .button {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 15px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
    @php
        $company = \App\Models\Company::first();
    @endphp
</head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Confirmación de Recepción de Reclamo</h2>
            </div>
            <div class="content">
                <p>Estimado(a) {{ $data->names }},</p>

                <p>Le confirmamos que hemos recibido su reclamo correctamente.</p>

                <p>Su número de folio para seguimiento es: <strong class="highlight">{{ $data->composite_code }}</strong></p>

                <p>Agradecemos que nos haya contactado. En <strong class="highlight">{{ $company->name }}</strong>, su satisfacción es nuestra prioridad y ya estamos revisando su solicitud. Le daremos una respuesta oficial en un plazo no mayor a **quince (15) días hábiles**, tal como lo establece la Ley N° 29571 (Código de Protección y Defensa del Consumidor).</p>

                <p>Mientras tanto, puede consultar nuestros <a href="{{ route('terms_main') }}" style="color:#007bff;">Términos y Condiciones</a> o nuestra <a href="[ENLACE_A_POLITICA_PRIVACIDAD]" style="color:#007bff;">Política de Privacidad</a>.</p>

                <p>Gracias por su paciencia y por confiar en {{ $company->name }}.</p>

                <p>Atentamente,</p>
                <p>El equipo de {{ $company->name }}</p>
            </div>
            <div class="footer">
                <p>Este es un correo electrónico automático, por favor no lo responda.</p>
                <p>&copy; {{ date("Y") }} {{ $company->name }}. Todos los derechos reservados.</p>
                <p>{{ $company->fiscal_address }}<br>{{ $company->phone }}</p>
            </div>
        </div>
    </body>
</html>
