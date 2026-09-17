<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuesta a tu consulta</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7fa; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #060E2D 0%, #0188EE 100%); padding: 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; font-weight: 600; }
        .header p { color: rgba(255,255,255,0.8); margin: 10px 0 0; font-size: 14px; }
        .content { padding: 30px; }
        .saludo { font-size: 16px; color: #1e293b; margin: 0 0 16px; }
        .reply { font-size: 16px; color: #1e293b; line-height: 1.7; padding: 18px; background: #f8fafc; border-radius: 8px; border-left: 3px solid #0188EE; white-space: pre-line; }
        .divider { border-top: 1px solid #e2e8f0; margin: 28px 0 20px; }
        .quote-title { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 10px; font-weight: 600; }
        .quote { font-size: 14px; color: #475569; line-height: 1.6; padding: 14px; background: #f8fafc; border-radius: 8px; border-left: 3px solid #cbd5e1; white-space: pre-line; }
        .meta { font-size: 13px; color: #64748b; margin-top: 12px; line-height: 1.8; }
        .footer { background: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { margin: 0; font-size: 13px; color: #64748b; }
        .footer a { color: #0188EE; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Respuesta a tu consulta</h1>
            <p>ARACODE Smart Solutions</p>
        </div>

        <div class="content">
            <p class="saludo">Hola {{ $original['name'] ?? 'estimado(a)' }},</p>

            <div class="reply">{!! nl2br(e($body)) !!}</div>

            <div class="divider"></div>

            <div class="quote-title">Tu consulta original</div>
            <div class="quote">{{ $original['message'] ?? '' }}</div>

            <div class="meta">
                <strong>Servicio consultado:</strong> {{ ucfirst($original['service'] ?? 'no especificado') }}<br>
                <strong>Enviada el:</strong> {{ $original['created_at'] ?? '' }}
            </div>
        </div>

        <div class="footer">
            <p>Si necesitas algo más, responde a este correo y te atenderemos.</p>
            <p style="margin-top: 10px; font-size: 12px; color: #94a3b8;">
                <a href="https://aracodeperu.com">aracodeperu.com</a> · contacto@aracodeperu.com · (+51) 917 295 856
            </p>
        </div>
    </div>
</body>
</html>
