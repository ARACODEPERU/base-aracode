<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenidos a {{ config('app.name') }}</title>

    <!--Google Fonts-->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        .bienvenida {
            padding: 60px 10px;
        }

        /* Establece el ancho al 100% y la altura a 250px */
        .banner {
            width: 100%;
            background-color: #3498db;
            /* Cambia el color de fondo según tus preferencias */
            /* Puedes agregar más estilos según tus necesidades */
        }

        .subTitle {
            text-align: center;
            font-size: 25px;
            color: #808080;
        }

        .title {
            margin-bottom: -5px;
            text-align: center;
            font-size: 50px;
            font-weight: 700;
            color: #0c161f;
        }

        /* Estilos para la línea */
        .linea {
            border: 2px solid #8b0e06;
            /* Cambia el grosor y el color de la línea según tus preferencias */
            margin: 20px auto;
            /* Centra la línea horizontalmente y agrega espacio vertical */
            width: 5%;
            /* Establece el ancho de la línea al 50% de la página */
        }


        /* Estilos para la línea */
        .lineaCurso {
            border: 2px solid #8b0e06;
            /* Cambia el grosor y el color de la línea según tus preferencias */
            width: 8%;
            /* Establece el ancho de la línea al 50% de la página */
        }

        .contenedor {
            margin: 0px auto;
            width: 45%;
            display: flex;
            flex-wrap: wrap;
        }

        .columna {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
        }

        .btn {
            border: none;
            color: white;
            padding: 14px 28px;
            cursor: pointer;
            border-radius: 5px;
        }

        .primary {
            background-color: #007bff;
        }

        .primary:hover {
            background: #0b7dda;
        }

        .primary a {
            color: #ffffff;
            text-decoration: none;
        }

        footer {
            padding: 15px;
            text-align: center;
            background: #0c161f;
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

        /* Estilos adicionales para hacerlo adaptable y estilizado */
        @media (max-width: 768px) {
            .contenedor {
                flex-direction: column;
                margin: 0px auto;
                width: 95%;
            }

            .columna {
                flex: 1;
                margin: 5px;
            }
        }
    </style>
</head>

@php
    // El logo sale de la empresa configurada, igual que en el resto de correos del
    // proyecto: antes venia fijado a mano y apuntaba al servidor de otro proyecto.
    $company = \App\Models\Company::first();
@endphp

<body>
    <section style="text-align: center; padding: 20px 0;">
        @if ($company && $company->logo)
            @if ($company->logo == '/img/logo176x32.png')
                <img src="{{ asset($company->logo) }}" style="width: 300px;" alt="{{ $company->name }}">
            @else
                <img src="{{ asset('storage/' . $company->logo) }}" style="width: 300px" alt="{{ $company->name }}">
            @endif
        @endif
    </section>

    <section class="bienvenida">
        <h6 class="subTitle">Bienvenido a</h6>
        <h1 class="title">{{ $company->name ?? config('app.name') }}</h1>
        <hr class="linea">
    </section>

    @foreach ($data['courses'] as $course)
        <section style="padding-bottom: 15px;">
            <div class="contenedor">
                <div class="columna">
                    <img width="100%;" src="{{ $course['image'] }}" alt="">
                </div>
                <div class="columna" style="text-align: left; padding: 15px;">
                    <h1 style="margin-bottom: 5px;">
                        {{ $course['name'] }}
                    </h1>
                    <hr class="lineaCurso">
                    <br>
                    <p>
                        {{ $course['description'] }}
                    </p>
                    <br>

                </div>
            </div>
        </section>
    @endforeach
    <section style="padding-bottom: 15px;">
        <div class="contenedor">
            <div class="columna" style="text-align: center; padding: 15px; background-color: #F9FAFD;">
                <p> Hola {{ $data['names'] }}:
                    Estos son tus accesos al campus virtual:
                </p>
                <br>
                <p style="padding: 1px 0px;">
                    {{-- Segun el flujo, el usuario del campus llega como 'user' o como 'email'. --}}
                    <b style="color: #8b0e06;">Usuario: </b>{{ $data['user'] ?? ($data['email'] ?? '') }}
                </p>
                <p style="padding: 1px 0px;">
                    <b style="color: #8b0e06;">Contraseña: </b>{{ $data['password'] }}
                </p>
                <br>
                <button class="btn primary"><a href="{{ route('login') }}">Ingresar al Campus Virtual</a></button>
            </div>
        </div>
    </section>
    <footer>
        <p>
            &copy; Derechos Reservados a {{ config('app.name') }}| Desarrollado por <a href="https://aracodeperu.com/"
                style="">Aracode Smart Solutions</a>
        </p>
    </footer>
</body>

</html>
