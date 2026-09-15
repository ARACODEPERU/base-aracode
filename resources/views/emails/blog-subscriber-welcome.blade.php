<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido al Blog de ARACODE</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f7fa; font-family: 'Inter', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f7fa; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #060E2D 0%, #0188EE 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 24px; margin: 0 0 10px 0;">¡Bienvenido, {{ $name }}!</h1>
                            <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin: 0;">Gracias por suscribirte al blog de ARACODE</p>
                        </td>
                    </tr>
                    
                    {{-- Content --}}
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="color: #334155; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Hola <strong>{{ $name }}</strong>,
                            </p>
                            <p style="color: #334155; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Te damos la bienvenida a nuestra comunidad. A partir de ahora recibirás los mejores artículos sobre tecnología, automatización, inteligencia artificial y transformación digital directo en tu correo.
                            </p>
                            <p style="color: #334155; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;">
                                Mientras tanto, te invitamos a explorar nuestro blog:
                            </p>
                            
                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/blog') }}" style="display: inline-block; background-color: #0188EE; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                                            Explorar el Blog
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f8fafc; padding: 30px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="color: #64748b; font-size: 12px; margin: 0 0 10px 0;">
                                ARACODE Smart Solutions S.A.C.
                            </p>
                            <p style="color: #94a3b8; font-size: 11px; margin: 0;">
                                Si no solicitaste esta suscripción, puedes ignorar este mensaje.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
