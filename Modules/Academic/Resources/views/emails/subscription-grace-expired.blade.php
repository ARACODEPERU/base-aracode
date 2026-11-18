<!DOCTYPE html>
<html lang="es">
<head>
    <title>Suscripción suspendida</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>¡Hola {{ $studentName }}!</p>

    <p>Pasaron los <strong>{{ $graceDays }} días</strong> de reintento y no pudimos completar el cobro de tu suscripción académica <strong>{{ $preapproval->subscription?->title ?? 'mensual' }}</strong> por {{ $preapproval->currency_id }} {{ number_format((float) $preapproval->amount, 2) }}.</p>

    <p>Por ese motivo tu suscripción quedó <strong>suspendida</strong>: ya no podrás acceder a los contenidos de tu plan hasta que la renueves manualmente.</p>

    <p>Puedes reactivarla cuando quieras desde <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>. Si el problema fue la tarjeta, revisa que tenga el uso por internet activado o registra una nueva.</p>

    <p>Tu historial de pagos y comprobantes se mantiene intacto.</p>

    <p>Saludos cordiales,</p>
    <p>El Equipo Académico.</p>
</body>
</html>
