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
                border-bottom: 3px solid #b91c1c;
                padding-bottom: 12px;
                margin-bottom: 20px;
            }
            .event-name {
                font-size: 18px;
                font-weight: bold;
                color: #b91c1c;
                text-transform: uppercase;
            }
            .edition-name {
                font-size: 13px;
                color: #555;
                margin-top: 4px;
            }
            .title-section {
                font-size: 14px;
                font-weight: bold;
                text-align: center;
                text-transform: uppercase;
                color: #b91c1c;
                margin-bottom: 15px;
                letter-spacing: 2px;
            }
            .sanctions-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 25px;
            }
            .sanctions-table th {
                background: #b91c1c;
                color: white;
                padding: 8px 6px;
                font-size: 10px;
                text-transform: uppercase;
                text-align: center;
                border: 1px solid #b91c1c;
            }
            .sanctions-table td {
                border: 1px solid #d1d5db;
                padding: 6px;
                text-align: center;
                font-size: 11px;
            }
            .sanctions-table td.left {
                text-align: left;
            }
            .sanctions-table tr:nth-child(even) td {
                background: #f9fafb;
            }
            .total-row {
                text-align: right;
                font-size: 13px;
                font-weight: bold;
                margin-top: 10px;
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
            <div class="event-name">{{ $event_name }}</div>
            <div class="edition-name">{{ $edicion->name }}</div>
        </div>

        <div class="title-section">Listado de Sanciones Pendientes</div>

        <table class="sanctions-table">
            <thead>
                <tr>
                    <th style="width: 6%;">N°</th>
                    <th style="width: 38%;">Jugador</th>
                    <th style="width: 26%;">Equipo</th>
                    <th style="width: 18%;">Tarjeta</th>
                    <th style="width: 12%;">Precio (S/)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="left">{{ $row['player_name'] }}</td>
                    <td class="left">{{ $row['team_name'] }}</td>
                    <td>{{ $row['card'] }}</td>
                    <td>{{ $row['price'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">Sin sanciones pendientes</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="total-row">
            Total a cobrar: S/ {{ $total }}
        </div>

        <div class="footer">
            Documento generado por el sistema de gesti&oacute;n deportiva.
        </div>
    </body>
</html>
