<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo mensaje de contacto</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7fa; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #060E2D 0%, #0188EE 100%); padding: 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 600; }
        .header p { color: rgba(255,255,255,0.8); margin: 10px 0 0; font-size: 14px; }
        .content { padding: 30px; }
        .field { margin-bottom: 20px; }
        .field-label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 6px; font-weight: 600; }
        .field-value { font-size: 16px; color: #1e293b; line-height: 1.6; padding: 12px; background: #f8fafc; border-radius: 8px; border-left: 3px solid #0188EE; }
        .message-box { background: #f8fafc; border-radius: 8px; padding: 16px; border-left: 3px solid #0188EE; }
        .footer { background: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { margin: 0; font-size: 13px; color: #64748b; }
        .footer a { color: #0188EE; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📩 Nuevo Mensaje de Contacto</h1>
            <p>ARACODE Smart Solutions</p>
        </div>
        
        <div class="content">
            <div class="field">
                <div class="field-label">Nombre</div>
                <div class="field-value">{{ $data['name'] }}</div>
            </div>
            
            <div class="field">
                <div class="field-label">Email</div>
                <div class="field-value"><a href="mailto:{{ $data['email'] }}">{{ $data['email'] }}</a></div>
            </div>
            
            @if($data['phone'] ?? null)
            <div class="field">
                <div class="field-label">Teléfono</div>
                <div class="field-value">{{ $data['phone'] }}</div>
            </div>
            @endif
            
            @if($data['company'] ?? null)
            <div class="field">
                <div class="field-label">Empresa</div>
                <div class="field-value">{{ $data['company'] }}</div>
            </div>
            @endif
            
            <div class="field">
                <div class="field-label">Servicio de interés</div>
                <div class="field-value">{{ ucfirst($data['service']) }}</div>
            </div>
            
            <div class="field">
                <div class="field-label">Mensaje</div>
                <div class="message-box">{!! nl2br(e($data['message'])) !!}</div>
            </div>
        </div>
        
        <div class="footer">
            <p>Este mensaje fue enviado desde el formulario de contacto de <a href="https://aracodeperu.com">aracodeperu.com</a></p>
            <p style="margin-top: 10px; font-size: 12px; color: #94a3b8;">{{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
