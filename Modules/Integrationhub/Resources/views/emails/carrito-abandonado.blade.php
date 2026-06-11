<!DOCTYPE html>
<html>
<head>
    <title>¡Tus cursos te están esperando!</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; padding: 40px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #dc2626; font-size: 24px; margin-bottom: 10px; }
        p { color: #555555; font-size: 16px; line-height: 1.6; }
        .course-item { padding: 12px 0; border-bottom: 1px solid #eee; }
        .course-item:last-child { border-bottom: none; }
        .total { font-size: 20px; font-weight: bold; color: #dc2626; margin: 20px 0; }
        .button { display: inline-block; padding: 14px 32px; background-color: #dc2626; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; margin-top: 20px; }
        .footer { margin-top: 30px; font-size: 12px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ $name ? "¡$name, tus cursos te están esperando!" : '¡Tus cursos te están esperando!' }}</h1>
        <p>Notamos que dejaste cursos pendientes en tu carrito de compras. ¡No dejes pasar esta oportunidad!</p>

        @if(count($items) > 0)
            <h3>Cursos seleccionados:</h3>
            @foreach($items as $item)
                <div class="course-item">
                    <strong>{{ $item['name'] ?? 'Curso' }}</strong>
                </div>
            @endforeach
        @endif

        @if($total > 0)
            <div class="total">Total: S/ {{ number_format($total, 2) }}</div>
        @endif

        <p>Completa tu compra ahora y asegura tu acceso a los cursos.</p>

        <a href="{{ url('/carrito') }}" class="button">Finalizar mi compra</a>

        <div class="footer">
            <p>CPA Academy — Formación que impulsa tu futuro</p>
            <p>Si tienes dudas, escríbenos a informes@globalcpaperu.com</p>
        </div>
    </div>
</body>
</html>
