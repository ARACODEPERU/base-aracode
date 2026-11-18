<!DOCTYPE html>
<html lang="es">
<head>
    <title>Suscripción renovada</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>¡Hola {{ $studentName }}!</p>

    <p>Tu suscripción académica <strong>{{ $preapproval->subscription?->title ?? 'mensual' }}</strong> se renovó correctamente.</p>

    <p>Estos son los detalles del cobro:</p>
    <ul>
        <li><strong>Monto:</strong> {{ $preapproval->currency_id }} {{ number_format((float) $payment->amount, 2) }}</li>
        <li><strong>Periodo cubierto:</strong> {{ $payment->date_start?->format('d/m/Y') }} al {{ $payment->date_end?->format('d/m/Y') }}</li>
        <li><strong>Próximo cobro:</strong> {{ $preapproval->next_payment_date?->format('d/m/Y') ?? 'según tu fecha de alta' }}</li>
    </ul>

    <p>Tu acceso continúa activo sin interrupciones. ¡Te invitamos a seguir creciendo académicamente con nosotros!</p>

    <p>Puedes revisar el estado de tu suscripción en <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>.</p>

    <p>Saludos cordiales,</p>
    <p>El Equipo Académico.</p>
</body>
</html>
