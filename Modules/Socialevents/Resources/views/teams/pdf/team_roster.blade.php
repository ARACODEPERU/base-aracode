<html>
    <head>
        <meta charset="UTF-8">
        <style>
            @page { margin: 1.5cm; }
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                color: #333;
                font-size: 12px;
                margin: 0;
                padding: 0;
            }
            .header {
                text-align: center;
                border-bottom: 3px solid #1e3a8a;
                padding-bottom: 12px;
                margin-bottom: 20px;
            }
            .event-name {
                font-size: 18px;
                font-weight: bold;
                color: #1e3a8a;
                text-transform: uppercase;
            }
            .edition-name {
                font-size: 13px;
                color: #555;
                margin-top: 4px;
            }
            .team-info {
                margin-bottom: 20px;
                padding: 12px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
            }
            .team-info table {
                width: 100%;
                border-collapse: collapse;
            }
            .team-info td {
                padding: 4px 8px;
                font-size: 13px;
            }
            .team-info .label {
                font-weight: bold;
                color: #4b5563;
                width: 120px;
            }
            .team-info .value {
                color: #111827;
                font-weight: bold;
            }
            .title-section {
                font-size: 14px;
                font-weight: bold;
                text-align: center;
                text-transform: uppercase;
                color: #1e3a8a;
                margin-bottom: 15px;
                letter-spacing: 2px;
            }
            .roster-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 25px;
            }
            .roster-table th {
                background: #1e3a8a;
                color: white;
                padding: 8px 6px;
                font-size: 10px;
                text-transform: uppercase;
                text-align: center;
                border: 1px solid #1e3a8a;
            }
            .roster-table td {
                border: 1px solid #d1d5db;
                padding: 6px;
                text-align: center;
                height: 28px;
                font-size: 11px;
            }
            .roster-table td.blank {
                color: #9ca3af;
            }
            .roster-table tr:nth-child(even) td {
                background: #f9fafb;
            }
            .footer {
                position: absolute;
                bottom: 0;
                width: 100%;
                text-align: center;
                font-size: 9px;
                color: #9ca3af;
                border-top: 1px solid #e5e7eb;
                padding-top: 8px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="event-name">{{ $data['event_name'] }}</div>
            <div class="edition-name">{{ $data['edition_name'] }}</div>
        </div>

        <div class="team-info">
            <table>
                <tr>
                    <td class="label">Equipo:</td>
                    <td class="value">{{ $data['team_name'] }}</td>
                </tr>
                <tr>
                    <td class="label">Delegado:</td>
                    <td class="value">{{ $data['manager_name'] }}</td>
                </tr>
            </table>
        </div>

        <div class="title-section">Ficha de Inscripci&oacute;n</div>

        <table class="roster-table">
            <thead>
                <tr>
                    <th style="width: 8%;">N°</th>
                    <th style="width: 30%;">Apellidos y Nombres</th>
                    <th style="width: 16%;">Tipo Doc.</th>
                    <th style="width: 18%;">N° Documento</th>
                    <th style="width: 20%;">Posici&oacute;n</th>
                    <th style="width: 8%;">Firma</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= 12; $i++)
                <tr>
                    <td>{{ $i }}</td>
                    <td class="blank">&nbsp;</td>
                    <td class="blank">&nbsp;</td>
                    <td class="blank">&nbsp;</td>
                    <td class="blank">&nbsp;</td>
                    <td class="blank">&nbsp;</td>
                </tr>
                @endfor
            </tbody>
        </table>

        <div style="margin-top: 90px; text-align: right; font-size: 11px;">
            <div style="border-top: 1px solid #000; display: inline-block; padding-top: 6px; width: 250px; text-align: center;">
                Firma del Delegado
            </div>
        </div>

        <div class="footer">
            Documento generado por el sistema de gesti&oacute;n deportiva.
        </div>
    </body>
</html>
