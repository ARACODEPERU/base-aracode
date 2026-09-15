<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo suscriptor al blog</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f7fa; font-family: 'Inter', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f7fa; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                    {{-- Header --}}
                    <tr>
                        <td style="background-color: #060E2D; padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 20px; margin: 0;">📬 Nuevo Suscriptor al Blog</h1>
                        </td>
                    </tr>
                    
                    {{-- Content --}}
                    <tr>
                        <td style="padding: 30px;">
                            <p style="color: #334155; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Se ha registrado un nuevo suscriptor en el newsletter del blog:
                            </p>
                            
                            {{-- Info Card --}}
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 8px; margin-bottom: 20px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="color: #64748b; font-size: 12px; margin: 0 0 5px 0; text-transform: uppercase;">Nombre</p>
                                        <p style="color: #1e293b; font-size: 16px; font-weight: 600; margin: 0 0 15px 0;">{{ $name }}</p>
                                        
                                        <p style="color: #64748b; font-size: 12px; margin: 0 0 5px 0; text-transform: uppercase;">Email</p>
                                        <p style="color: #0188EE; font-size: 16px; font-weight: 600; margin: 0 0 15px 0;">{{ $email }}</p>
                                        
                                        <p style="color: #64748b; font-size: 12px; margin: 0 0 5px 0; text-transform: uppercase;">Fecha</p>
                                        <p style="color: #1e293b; font-size: 14px; margin: 0;">{{ now()->format('d/m/Y H:i') }}</p>
                                    </td>
                                </tr>
                            </table>
                            
                            {{-- CTA Button --}}
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/admin/blog-subscribers') }}" style="display: inline-block; background-color: #0188EE; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 13px;">
                                            Ver Suscriptores
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="color: #94a3b8; font-size: 11px; margin: 0;">
                                Este es un email automático del sistema ARACODE.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
