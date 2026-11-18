<!DOCTYPE html>
<html lang="es">
<head>
    <title>Cobro rechazado</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>¡Hola {{ $studentName }}!</p>

    <p>Hoy intentamos cobrar tu suscripción académica <strong>{{ $preapproval->subscription?->title ?? 'mensual' }}</strong> por {{ $preapproval->currency_id }} {{ number_format((float) $preapproval->amount, 2) }} y el pago no se pudo completar.</p>

    <p>Esto suele ocurrir por alguno de estos motivos:</p>
    <ul>
        <li>Tu tarjeta no tiene activado el uso por internet (compras en línea).</li>
        <li>Los datos de la tarjeta están vencidos o desactualizados.</li>
        <li>El banco rechazó el cobro por seguridad o por falta de fondos.</li>
    </ul>

    <p>Te recomendamos activar el uso de tu tarjeta en internet o actualizar los datos de tu tarjeta para seguir usando la suscripción sin interrupciones.</p>

    <p>Reintentaremos el cobro automáticamente durante los próximos <strong>{{ $graceDays }} días</strong>. Si no logramos completarlo en ese plazo, tu suscripción quedará suspendida hasta que la renueves manualmente.</p>

    <p>Puedes gestionar tu suscripción en <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>.</p>

    <p>Saludos cordiales,</p>
    <p>El Equipo Académico.</p>
</body>
</html>
