<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPA Academy - Comprobante de Pago</title>

    <!--Google Fonts-->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        h1 {
            text-align: center;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        h1 i {
            margin-right: 10px;
        }

        p {
            line-height: 22px;
            text-align: justify;
        }

        .card-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            margin-top: 20px;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin: 10px;
            padding: 20px;
            flex: 1 1 calc(33% - 40px);
            /* Tres tarjetas por fila en pantallas grandes */
            box-sizing: border-box;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h4 {
            margin-top: 0;
            color: #333;
        }

        .card p {
            color: #555;
        }

        .card button {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .card button:hover {
            background-color: #0056b3;
        }


        .boton-degradado-campus {
            width: 100%;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(45deg, #3c4a99, #4f46e5);
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s, background 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .boton-degradado-campus:hover {
            transform: translateY(-3px);
            background: linear-gradient(45deg, #4f46e5, #3c4a99);
        }


        @media (max-width: 768px) {
            .card {
                flex: 1 1 calc(50% - 40px);
                /* Dos tarjetas por fila en pantallas medianas */
            }
        }

        @media (max-width: 480px) {
            .card {
                flex: 1 1 100%;
                /* Una tarjeta por fila en pantallas pequeñas */
            }
        }

        .btn {
            border: none;
            color: white;
            padding: 14px 28px;
            cursor: pointer;
            border-radius: 5px;
        }

        .primary {
            background-color: #ff8607;
        }

        .primary:hover {
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

        footer a:hover {
            text-decoration: none;
            color: orange;
        }
    </style>
</head>

<body>

    <br>


    <div class="container">
        <img style="width: 100%;" src="{{ asset('img/banner-email.jpg') }}" alt="Encabezado">
        <br>
        <h1>
            <img style="width: 25px;" data-emoji="🎉" class="an1" alt="🎉" aria-label="🎉" draggable="false"
                src="https://fonts.gstatic.com/s/e/notoemoji/16.0/1f389/32.png" loading="lazy">
            &nbsp; ¡Gracias por tu compra! &nbsp;
            <img style="width: 25px;" data-emoji="🎉" class="an1" alt="🎉" aria-label="🎉" draggable="false"
                src="https://fonts.gstatic.com/s/e/notoemoji/16.0/1f389/32.png" loading="lazy">
        </h1>
        <p>
            Estimado(a) {{ $data['from_name'] }},<br>
Gracias por tu compra. Tu comprobante de pago ha sido enviado correctamente y puedes descargarlo en los archivos adjuntos. La transacción ha sido registrada sin inconvenientes.
<br><br>
Detalle de tu compra
        </p>

        <table style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; margin: 20px 0;">
            <!-- Header azul suave -->
            <thead>
                <tr style="background-color: #3498db;">
                    <th style="padding: 12px 15px; text-align: left; border: 1px solid #3498db; color: #ffffff; font-weight: bold;">
                        Curso
                    </th>
                    <th style="padding: 12px 15px; text-align: right; border: 1px solid #3498db; color: #ffffff; font-weight: bold;">
                        Precio
                    </th>
                </tr>
            </thead>
            @if ($sale->items->count() > 0)

            <!-- Cuerpo de la tabla -->
            <tbody>

                @foreach ($sale->items as $item)
                <tr>
                    <td style="padding: 12px 15px; border: 1px solid #e5e7eb;">
                        <h4 style="margin: 0; font-size: 16px; font-weight: 600;">
                            {{ $item->decription_product }}
                        </h4>
                    </td>
                    <td style="padding: 12px 15px; border: 1px solid #e5e7eb; text-align: right;">
                        <p style="color: #4f46e5; font-size: 16px; font-weight: 700; margin: 0;">
                            S/. {{ $item->mto_total }}
                        </p>
                    </td>
                </tr>
                @endforeach
            </tbody>

            <!-- Footer azul oscuro con total -->
            <tfoot>
                <tr style="background-color: #2c3e50;">
                    <td style="padding: 15px; text-align: right; color: white; font-weight: bold; font-size: 16px;">
                        TOTAL:
                    </td>
                    <td style="padding: 15px; text-align: right; color: white; font-weight: bold; font-size: 18px;">
                        S/. {{ $sale->overall_total }}
                    </td>
                </tr>
            </tfoot>
            @else
                    <p style="text-align: center; color: #7f8c8d; font-style: italic; padding: 20px;">
                        La venta no contiene elementos detallados.
                    </p>
            @endif
        </table>

        <div class="card-container">

            <a href="https://academy.globalcpaperu.com/login" style="margin-top: 20px;">
                <button class="boton-degradado-campus">Ingresar a la plataforma</button>
            </a>
        </div>
        <br>

        <p>
<b>Tu acceso ya está habilitado.</b><br>
Puedes ingresar ahora y comenzar a vivir la experiencia.<br><br>

<b>Dentro de la plataforma podrás:</b><br>
– Acceder y descargar los materiales de trabajo<br>
– Revisar las grabaciones sin fecha de caducidad<br>
– Certificado <br><br>

<b>Confianza y respaldo técnico</b><br>
Formarás parte de una escuela respaldada por profesionales con experiencia en <b>firmas líderes</b>, y reconocida como <b>Approved Learning Partner (ALP) de ACCA</b>. Este estándar garantiza rigurosidad técnica y aplicabilidad práctica desde el primer día.
<br>
<b>Soporte inmediato</b><br>
Si necesitas asistencia, puedes escribirnos en todo momento al WhatsApp: <b>+51 967 052 506</b>.<br>
Gracias por confiar en <b>CPA Academy</b>. Estamos comprometidos en acompañarte en cada etapa de tu avance profesional.<br><br>

Atentamente,<br><br><br>


Equipo de CPA Academy<br>
academy.globalcpaperu.com<br>

        </p>

        <p style="text-align: center; font-size: 14px;">
            CPA Academy, Jirón Pedro Conde Nro. 514, Oficina 203., Distrito de Lince, Provincia de Lima,
            Perú, +51 967052506
        </p>
        <br>
        <footer>
            <p style="text-align: center; font-size: 15px;">
                &copy; Derechos Reservados {{ env('APP_NAME') }} | Desarrollado por <a href="https://aracodeperu.com/"
                    style="">Aracode Smart Solutions</a>
            </p>
        </footer>
    </div>

</body>

</html>
