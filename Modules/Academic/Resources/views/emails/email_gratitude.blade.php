<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global CPA - Business School</title>

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
            &nbsp; ¡Bienvenido(a) a Global CPA Business School! &nbsp;
            <img style="width: 25px;" data-emoji="🎉" class="an1" alt="🎉" aria-label="🎉" draggable="false"
                src="https://fonts.gstatic.com/s/e/notoemoji/16.0/1f389/32.png" loading="lazy">
        </h1>
        <p>{{ $data->clie_full_name }},
            Has tomado una decisión valiosa al invertir en tu crecimiento profesional, y estamos aquí para acompañarte
            en este
            camino de aprendizaje y desarrollo. ¡Felicidades por dar este importante paso hacia tus metas!
        </p>
        <p>
            Para comenzar, aquí tienes tus datos de acceso a nuestra plataforma
        </p>
        <div class="card-container">
            @foreach ($data->details as $course)
                <div class="card">
                    <img width="100%" src="{{ $course->image }}" alt="product" />
                    <h4 style="margin-top: 5px;">{{ $course->description }}</h4>
                    {{-- <p style="color: #4f46e5; font-size: 16px; font-weight: 700; margin-top: -10px;">
                        S/. {{ $course->price }}
                    </p> --}}
                </div>
            @endforeach
            {{-- <div class="card">
                <img width="100%" src="{{ asset('img/curso.jpeg') }}" alt="product" />
                <h4 style="margin-top: 5px;">Título del curso y/o programa educativo</h4>
                <p style="color: #4f46e5; font-size: 16px; font-weight: 700; margin-top: -10px;">
                    S/. 395.00
                </p>
            </div>
            <div class="card">
                <img width="100%" src="{{ asset('img/curso.jpeg') }}" alt="product" />
                <h4 style="margin-top: 5px;">Título del curso y/o programa educativo</h4>
                <p style="color: #4f46e5; font-size: 16px; font-weight: 700; margin-top: -10px;">
                    S/. 395.00
                </p>
            </div> --}}
        </div>
        <div class="card-container">
            <p>
                👤 Usuario: {{ $data->email }}
                <br>
                🔑
            </p>
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
