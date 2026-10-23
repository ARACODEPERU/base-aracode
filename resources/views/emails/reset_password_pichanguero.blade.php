<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Restablece tu contraseña · Pichanguero</title>
    <style>
        /* Los estilos van en línea; esto solo ajusta los márgenes en móvil. */
        @media only screen and (max-width: 600px) {
            .pg-email-body { padding: 26px 22px 8px !important; }
            .pg-email-header { padding: 22px !important; }
            .pg-email-footer { padding: 18px 22px 22px !important; }
            .pg-email-title { font-size: 23px !important; }
        }
    </style>
</head>

<body style="margin:0;padding:0;background-color:#101D2D;">
    {{-- Texto de vista previa que no se ve en el cuerpo del correo. --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        Restablece la contraseña de tu cuenta de organizador en Pichanguero.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           bgcolor="#101D2D" style="background-color:#101D2D;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                       style="width:100%;max-width:600px;background-color:#FFFFFF;border-radius:18px;overflow:hidden;">

                    {{-- Cabecera: azul noche con el logotipo y el filo lima del sitio. --}}
                    <tr>
                        <td class="pg-email-header" bgcolor="#101D2D"
                            style="padding:26px 32px;background-color:#101D2D;border-bottom:4px solid #B8F34A;">
                            <img src="{{ asset('themes/pichanguero/images/Logo-vertical.png') }}"
                                 alt="Pichanguero" width="185" height="38"
                                 style="display:block;width:185px;height:38px;border:0;">
                        </td>
                    </tr>

                    {{-- Cuerpo --}}
                    <tr>
                        <td class="pg-email-body"
                            style="padding:34px 32px 8px;background-color:#FFFFFF;font-family:Arial,Helvetica,sans-serif;color:#0B2239;">
                            <p style="margin:0 0 10px;font-family:'Trebuchet MS',Arial,sans-serif;font-size:11px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;color:#087443;">
                                Acceso de organizadores
                            </p>

                            <h1 class="pg-email-title"
                                style="margin:0 0 16px;font-family:'Trebuchet MS',Arial,sans-serif;font-size:26px;line-height:1.25;font-weight:bold;color:#0B2239;">
                                Hola, {{ $user->name }}
                            </h1>

                            <p style="margin:0 0 18px;font-size:15px;line-height:1.65;color:#5A6B7D;">
                                Recibimos una solicitud para restablecer la contraseña de tu cuenta de organizador
                                en Pichanguero. Pulsa el botón para elegir una nueva.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;">
                                <tr>
                                    <td bgcolor="#B8F34A" style="border-radius:10px;background-color:#B8F34A;">
                                        <a href="{{ $url }}" target="_blank" rel="noopener"
                                           style="display:inline-block;padding:14px 26px;border-radius:10px;background-color:#B8F34A;font-family:'Trebuchet MS',Arial,sans-serif;font-size:13px;font-weight:bold;letter-spacing:1px;line-height:1.2;text-transform:uppercase;color:#101D2D;text-decoration:none;">
                                            Restablecer contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#5A6B7D;">
                                El enlace caduca en <strong style="color:#0B2239;">60 minutos</strong>.
                            </p>

                            <p style="margin:0 0 22px;font-size:14px;line-height:1.6;color:#5A6B7D;">
                                Si no solicitaste este cambio, ignora este correo. Tu contraseña no cambiará.
                            </p>

                            <p style="margin:0 0 6px;font-size:12px;line-height:1.5;color:#93A3B3;">
                                Si el botón no funciona, copia y pega este enlace en tu navegador:
                            </p>
                            <p style="margin:0 0 24px;font-size:12px;line-height:1.5;word-break:break-all;">
                                <a href="{{ $url }}" target="_blank" rel="noopener"
                                   style="color:#087443;text-decoration:underline;">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>

                    {{-- Pie --}}
                    <tr>
                        <td class="pg-email-footer"
                            style="padding:20px 32px 26px;background-color:#F2F5F8;border-top:1px solid #DCE6EE;font-family:Arial,Helvetica,sans-serif;">
                            <p style="margin:0 0 6px;font-size:12px;line-height:1.5;color:#5A6B7D;">
                                Este correo se envió a
                                <strong style="color:#0B2239;">{{ $user->email }}</strong>.
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.5;color:#93A3B3;">
                                Pichanguero · Torneos de fútbol y más
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin:18px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.5;color:#7E8FA3;text-align:center;">
                    © {{ date('Y') }} Pichanguero. Todos los derechos reservados.
                </p>
            </td>
        </tr>
    </table>
</body>

</html>
