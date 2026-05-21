<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Notificación de mensaje</title>
    <style>
        /* Reset */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table, td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            height: 100% !important;
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f7fb;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px 10px;
        }
        .email-card {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }
        .email-header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            padding: 32px 28px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .email-header .header-icon {
            font-size: 40px;
            display: block;
            margin-bottom: 12px;
        }
        .email-body {
            padding: 28px;
        }
        .info-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #eef2f7;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .info-label {
            min-width: 90px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7a8f;
            padding-top: 2px;
        }
        .info-value {
            flex: 1;
            font-size: 15px;
            color: #1e293b;
            line-height: 1.5;
        }
        .info-value strong {
            color: #0f172a;
        }
        .message-bubble {
            background: #f0f4fe;
            border-radius: 12px;
            padding: 16px 18px;
            margin-top: 4px;
            font-size: 15px;
            line-height: 1.6;
            color: #1e293b;
            border-left: 4px solid #2563eb;
        }
        .sender-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #6366f1);
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            margin-right: 8px;
            vertical-align: middle;
        }
        .timestamp-badge {
            display: inline-block;
            background: #eef2f7;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            color: #6b7a8f;
            font-weight: 500;
        }
        .email-footer {
            background: #f8fafc;
            padding: 20px 28px;
            text-align: center;
            border-top: 1px solid #eef2f7;
        }
        .email-footer p {
            margin: 0;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.6;
        }
        .email-footer a {
            color: #2563eb;
            text-decoration: none;
        }
        .btn-reply {
            display: inline-block;
            background: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            padding: 10px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 8px;
        }
        .btn-reply:hover {
            background: #1d4ed8;
        }
        @media only screen and (max-width: 480px) {
            .email-body {
                padding: 20px 16px;
            }
            .info-row {
                flex-direction: column;
                gap: 4px;
            }
            .info-label {
                min-width: auto;
            }
            .email-header {
                padding: 24px 16px;
            }
            .email-header h1 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-card">
            {{-- Header --}}
            <div class="email-header">
                <span class="header-icon">💬</span>
                <h1>Nuevo mensaje recibido</h1>
            </div>

            {{-- Body --}}
            <div class="email-body">
                {{-- De --}}
                <div class="info-row">
                    <div class="info-label">De</div>
                    <div class="info-value">
                        <span class="sender-avatar">
                            {{ substr($data['fullName'] ?? '?', 0, 1) }}
                        </span>
                        <strong>{{ $data['fullName'] ?? 'Alumno' }}</strong>
                    </div>
                </div>

                {{-- Para --}}
                <div class="info-row">
                    <div class="info-label">Para</div>
                    <div class="info-value">
                        <strong>{{ $data['recipients'] ?? 'Administración' }}</strong>
                    </div>
                </div>

                {{-- Fecha/Hora --}}
                <div class="info-row">
                    <div class="info-label">Fecha</div>
                    <div class="info-value">
                        <span class="timestamp-badge">🕐 {{ $data['created_at'] ?? now()->format('d/m/Y h:i A') }}</span>
                    </div>
                </div>

                {{-- Mensaje --}}
                <div class="info-row" style="border-bottom: none; flex-direction: column;">
                    <div class="info-label" style="margin-bottom: 8px;">Mensaje</div>
                    <div class="message-bubble">
                        {{ $data['message'] ?? '—' }}
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="email-footer">
                <p style="margin-bottom: 4px;">
                    Este mensaje fue enviado desde el chat de alumnos.
                </p>
                <p>
                    © {{ date('Y') }} Global CPA · Todos los derechos reservados
                </p>
            </div>
        </div>
    </div>
</body>
</html>
