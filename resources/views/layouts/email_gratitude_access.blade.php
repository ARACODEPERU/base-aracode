<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global CPA - Business School</title>

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

        p{
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
            background: linear-gradient(45deg, #ee0000, #ee6030);
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s, background 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .boton-degradado-campus:hover {
            transform: translateY(-3px);
            background: linear-gradient(45deg, #ff0000, #ee3010);
        }


        @media (max-width: 768px) {
            .card {
                flex: 1 1 calc(50% - 40px);
            }
        }
        @media (max-width: 480px) {
            .card {
                flex: 1 1 100%;
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
                &nbsp; ¡Gracias por tu preferencia!  &nbsp;
            <img style="width: 25px;" data-emoji="🎉" class="an1" alt="🎉" aria-label="🎉" draggable="false"
                src="https://fonts.gstatic.com/s/e/notoemoji/16.0/1f389/32.png" loading="lazy">
        </h1>
        <p>Estimado(a) {{ $person->full_name }},<br>
            Te agradecemos por confiar en nosotros. Es una excelente elección que estamos seguros marcará la diferencia en tu camino profesional.
        </p>

        <br>
        <p>
            <b>Tu acceso ya está habilitado.</b> <br>
            Puedes ingresar ahora y comenzar a vivir la experiencia.<br><br>
            <div class="card-container">
                <p>
                    👤 Usuario: {{ $person->email }}
                    <br>
                    🔑 Tu contraseña de acceso
                </p>
                <a href="https://academy.globalcpaperu.com/login" style="margin-top: 20px;">
                    <button class="boton-degradado-campus">Ingresar a la plataforma</button>
                </a>
            </div>

            <b>Dentro de la plataforma podrás:</b><br>
            – Acceder y descargar los materiales de trabajo<br>
            – Revisar las grabaciones sin fecha de caducidad<br>
            – Tu Certificado <br><br>

            <b>Confianza y respaldo técnico</b><br>
            Formarás parte de una escuela respaldada por profesionales con experiencia en <b>firmas líderes</b>, y reconocida como <b>Approved Learning Partner (ALP) de ACCA</b>. Este estándar garantiza rigurosidad técnica y aplicabilidad práctica desde el primer día.
            <br><br>
            <b>Soporte inmediato</b><br>
            Si necesitas asistencia, puedes escribirnos en todo momento al WhatsApp: <b>+51 967 052 506</b>.<br><br><br>
            Gracias por confiar en <b>CPA Academy</b>. Estamos comprometidos en acompañarte en cada etapa de tu avance profesional.<br><br>

            Atentamente,<br><br><br>


            Equipo de CPA Academy<br>
            academy.globalcpaperu.com<br>

        </p>
        <p style="text-align: center; font-size: 14px;">
            CPA Academy, Jirón Pedro Conde Nro. 514, Oficina 204., Distrito de Lince, Provincia de Lima, Perú, +51 967052506
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