<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global CPA - Business School - Comprobante de Pago</title>

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
            &nbsp; ¡Gracias por tu pago - Global CPA Business School! &nbsp;
            <img style="width: 25px;" data-emoji="🎉" class="an1" alt="🎉" aria-label="🎉" draggable="false"
                src="https://fonts.gstatic.com/s/e/notoemoji/16.0/1f389/32.png" loading="lazy">
        </h1>
        <p>
            Gracias por estár con nosotros, te hemos enviado tu comprobante de pago.
        </p>
        <p>
            revisa los datos adjuntos para poder descargar y ver mas detalles.
        </p>
        <div class="card-container">

            <div>
                <h3 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-bottom: 20px;">Cursos Incluidos en Compra</h3>

                @if ($sale->items->count() > 0)
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                        <thead>
                            <tr style="background-color: #3498db; color: white;">
                                <th style="padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0;">Curso</th>
                                <th style="padding: 12px 15px; text-align: left; border-bottom: 1px solid #e0e0e0;">Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sale->items as $item)
                            <tr style="border-bottom: 1px solid #e0e0e0;">
                                @if ($item)
                                    <td style="padding: 12px 15px;">{{ $item->decription_product }}</td>
                                    <td style="padding: 12px 15px; color: #27ae60; font-weight: bold;">S/. {{ $item->mto_total }}</td>
                                @else
                                    <td colspan="2" style="padding: 12px 15px; color: #e74c3c; font-weight: bold;">
                                        ⚠️ No se encontró el curso asociado (ID de Item: {{ $item->product_id }}).
                                    </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="text-align: center; color: #7f8c8d; font-style: italic; padding: 20px;">
                        La venta no contiene elementos detallados.
                    </p>
                @endif

                <div style="background-color: #2c3e50; color: white; padding: 15px; border-radius: 8px; text-align: right; font-size: 1.2em; font-weight: bold;">
                    Importe total: {{ $sale->overall_total }}
                </div>
            </div>

        </div>
        <div class="card-container">

            <a href="https://academy.globalcpaperu.com/login" style="margin-top: 20px;">
                <button class="boton-degradado-campus">Ingresar a la plataforma</button>
            </a>
        </div>
        <br>
        <p>
            En nuestra plataforma encontrarás todo lo necesario para aprovechar al máximo esta experiencia: material de
            estudio,
            foros de interacción y acceso directo a nuestros instructores. Te invitamos a iniciar sesión cuanto antes y
            familiarizarte con las herramientas que hemos preparado para ti.
        </p>
        <p style="line-height: 22px;">
            Estamos seguros de que este será un paso transformador para tu desarrollo. <br>
            <b>¡Te deseamos mucho éxito en esta nueva etapa con nosotros!</b>
        </p>
        <p>
            <b>Atentamente,</b><br>
            Equipo de GLOBAL CPA BUSINESS SCHOOL
        </p>
        <p style="text-align: center; font-size: 14px;">
            GLOBAL CPA BUSINESS SCHOOL, Jirón Pedro Conde Nro. 514, Oficina 203., Distrito de Lince, Provincia de Lima,
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
