<html>
    <head>
        <meta charset="UTF-8">
        <style>
            /* Configuracion de la hoja */
            @page { margin: 1cm; }
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                color: #333;
                line-height: 1.4;
                margin: 0;
                padding: 0;
                font-size: 12px;
            }

            /* Encabezado */
            .header-table { width: 100%; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 20px; }
            .org-name { font-size: 18px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; }
            .edition-name { font-size: 12px; color: #666; }
            .acta-code { background: #1e3a8a; color: white; padding: 5px 15px; border-radius: 4px; font-weight: bold; }

            /* Banner de Acuerdo */
            .accordance-banner {
                background: #f3f4f6;
                padding: 15px;
                text-align: center;
                border-radius: 8px;
                margin-bottom: 20px;
                border: 1px solid #e5e7eb;
            }
            .accordance-title { font-size: 16px; font-weight: bold; color: #111827; margin-bottom: 5px; }
            .accordance-date { font-size: 11px; color: #6b7280; }

            /* Secciones de Contenido */
            .section-title {
                background: #e5e7eb;
                padding: 5px 10px;
                font-weight: bold;
                font-size: 11px;
                text-transform: uppercase;
                margin-bottom: 8px;
                border-left: 4px solid #1e3a8a;
            }
            .content-box {
                border: 1px solid #e5e7eb;
                padding: 10px;
                min-height: 60px;
                margin-bottom: 15px;
                border-radius: 4px;
                background: #fafafa;
            }

            /* Tabla de Información */
            .info-table { width: 100%; margin-bottom: 20px; }
            .info-table td { padding: 5px; border-bottom: 1px solid #f3f4f6; }
            .label { font-weight: bold; color: #4b5563; width: 30%; }

            /* Firmas */
            .signature-section { margin-top: 40px; width: 100%; }
            .signature-box { border-top: 1px solid #000; text-align: center; padding-top: 5px; width: 45%; font-size: 10px; }
            .spacer { width: 10%; }

            /* Pie de pagina */
            .footer {
                position: absolute;
                bottom: 0;
                width: 100%;
                text-align: center;
                font-size: 10px;
                color: #9ca3af;
                border-top: 1px solid #e5e7eb;
                padding-top: 10px;
            }
        </style>
    </head>
    <body>

        <table class="header-table" style="width: 100%; table-layout: fixed; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 20px; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: top; text-align: left; padding-right: 10px;">
                    <div class="org-name" style="font-size: 16px; font-weight: bold; color: #1e3a8a; word-wrap: break-word; line-height: 1.2;">
                        {{ $acta['event_name'] ?? 'Evento' }}
                    </div>
                    <div class="edition-name" style="font-size: 11px; color: #666; margin-top: 4px;">
                        {{ $acta['edition_name'] ?? 'Edición' }}
                    </div>
                </td>

                <td style="width: 120px; vertical-align: top; text-align: right;">
                    <div class="acta-code" style="background: #1e3a8a; color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block; white-space: nowrap;">
                        ACTA Nº {{ $acta['minutes_code'] ?? $acta['id'] }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="accordance-banner">
            <div class="accordance-title">{{ $acta['minutes_subject'] }}</div>
            <div class="accordance-date">Fecha: {{ $acta['meeting_date'] ? \Carbon\Carbon::parse($acta['meeting_date'])->format('d/m/Y H:i') : 'N/A' }}</div>
        </div>

        <div class="section-title">Descripción del Acuerdo</div>
        <div class="content-box">
            @if($acta['minutes_body'])
                {!! $acta['minutes_body'] !!}
            @else
                Sin descripción registrada.
            @endif
        </div>
        @if($acta['observations'])
            <div class="section-title">Observaciones Adicionales</div>
            <div class="content-box">
                {!! $acta['observations'] !!}
            </div>
        @endif
        @if($acta['participants'])
        <div class="section-title">Participantes</div>
        <table class="signature-section" style="border: 1px solid #000; border-collapse: collapse; width: 100%;">
            @foreach($acta['participants'] as $participant)
            <tr>
                <td style="border: 1px solid #000; padding: 8px;">
                    DNI: {{ $participant['number'] }} - {{ $participant['full_name'] }}
                </td>
                <td style="border: 1px solid #000; padding: 8px; text-align: left;width: 25%;">
                    Firma:
                </td>
            </tr>
            @endforeach
        </table>
        @endif

        <div class="footer">
            Este documento es un acta oficial generada por el sistema de gestión deportiva.<br>
            Organizado por: <strong>Nombre de tu Empresa/Liga</strong> | Todos los derechos reservados &copy; 2026
        </div>

    </body>
</html>
