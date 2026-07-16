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
        .page1 {
            margin-bottom: 30px;
        }
        .page2 {
            page-break-before: always;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #1e3a8a;
            padding-bottom: 10px;
            margin-bottom: 14px;
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
        .match-banner {
            background: #f3f4f6;
            padding: 10px;
            text-align: center;
            border-radius: 6px;
            margin-bottom: 14px;
            border: 1px solid #e5e7eb;
        }
        .match-title {
            font-size: 15px;
            font-weight: bold;
            color: #111827;
        }
        .match-details {
            font-size: 11px;
            color: #6b7280;
            margin-top: 3px;
        }
        .team-header {
            background: #f3f4f6;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
            border: 1px solid #e5e7eb;
        }
        .team-info {
            width: 100%;
            border-collapse: collapse;
        }
        .team-info td {
            padding: 2px 4px;
            font-size: 12px;
        }
        .team-info .label {
            font-weight: bold;
            color: #4b5563;
            width: 80px;
        }
        .team-info .name {
            font-weight: bold;
            color: #1e3a8a;
            font-size: 14px;
        }
        .sub-section {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #374151;
            background: #e5e7eb;
            padding: 4px 10px;
            margin: 12px 0 6px 0;
            border-left: 3px solid #1e3a8a;
        }
        .players-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .players-table th {
            background: #374151;
            color: white;
            padding: 5px;
            font-size: 9px;
            text-transform: uppercase;
            text-align: center;
            border: 1px solid #374151;
        }
        .players-table td {
            border: 1px solid #d1d5db;
            padding: 4px;
            text-align: center;
            height: 24px;
            font-size: 11px;
        }
        .players-table tr:nth-child(even) td {
            background: #f9fafb;
        }
        .claims-box {
            margin-top: 18px;
            margin-bottom: 12px;
            border: 1.5px dashed #9ca3af;
            border-radius: 6px;
            padding: 10px;
            min-height: 80px;
        }
        .claims-box .claims-label {
            font-size: 10px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            display: block;
            margin-bottom: 6px;
        }
        .signature-box {
            margin-top: 16px;
            text-align: right;
        }
        .signature-box .line {
            display: inline-block;
            border-top: 1px solid #000;
            padding-top: 6px;
            width: 260px;
            text-align: center;
            font-size: 11px;
        }
        .footer-text {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    {{-- PAGE 1: Equipo Local --}}
    <div class="page1">
        <div class="header">
            <div class="event-name">{{ $data['event_name'] }}</div>
            <div class="edition-name">{{ $data['edition_name'] }}</div>
        </div>

        <div class="match-banner">
            <div class="match-title">{{ $data['local_team'] }} VS {{ $data['visitor_team'] }}</div>
            <div class="match-details">
                Fecha: {{ $data['match_date'] ?? 'Por definir' }}
                @if(!empty($data['location']))
                    | Lugar: {{ $data['location'] }}
                @endif
            </div>
        </div>

        <div class="team-header">
            <table class="team-info">
                <tr>
                    <td class="label">Equipo:</td>
                    <td class="name">{{ $data['local_team'] }}</td>
                </tr>
                <tr>
                    <td class="label">Delegado:</td>
                    <td>{{ $data['local_manager'] }}</td>
                </tr>
            </table>
        </div>

        <div class="sub-section">Titulares</div>
        <table class="players-table">
            <thead>
                <tr>
                    <th style="width: 8%;">N</th>
                    <th style="width: 42%;">Apellidos y Nombres</th>
                    <th style="width: 22%;">N Documento</th>
                    <th style="width: 20%;">Posicion</th>
                    <th style="width: 8%;">Firma</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= 6; $i++)
                <tr>
                    <td>{{ $i }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                @endfor
            </tbody>
        </table>

        <div class="sub-section">Suplentes</div>
        <table class="players-table">
            <thead>
                <tr>
                    <th style="width: 8%;">N</th>
                    <th style="width: 42%;">Apellidos y Nombres</th>
                    <th style="width: 22%;">N Documento</th>
                    <th style="width: 20%;">Posicion</th>
                    <th style="width: 8%;">Firma</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= 6; $i++)
                <tr>
                    <td>{{ $i }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                @endfor
            </tbody>
        </table>

        <div class="claims-box">
            <span class="claims-label">Espacio para reclamos del delegado</span>
        </div>

        <div class="signature-box">
            <div class="line">
                Firma del Delegado<br>
                <small>{{ $data['local_manager'] }}</small>
            </div>
        </div>

        <div class="footer-text">
            Documento generado por el sistema de gestion deportiva.
        </div>
    </div>

    {{-- PAGE 2: Equipo Visitante --}}
    <div class="page2">
        <div class="header">
            <div class="event-name">{{ $data['event_name'] }}</div>
            <div class="edition-name">{{ $data['edition_name'] }}</div>
        </div>

        <div class="match-banner">
            <div class="match-title">{{ $data['local_team'] }} VS {{ $data['visitor_team'] }}</div>
            <div class="match-details">
                Fecha: {{ $data['match_date'] ?? 'Por definir' }}
                @if(!empty($data['location']))
                    | Lugar: {{ $data['location'] }}
                @endif
            </div>
        </div>

        <div class="team-header">
            <table class="team-info">
                <tr>
                    <td class="label">Equipo:</td>
                    <td class="name">{{ $data['visitor_team'] }}</td>
                </tr>
                <tr>
                    <td class="label">Delegado:</td>
                    <td>{{ $data['visitor_manager'] }}</td>
                </tr>
            </table>
        </div>

        <div class="sub-section">Titulares</div>
        <table class="players-table">
            <thead>
                <tr>
                    <th style="width: 8%;">N</th>
                    <th style="width: 42%;">Apellidos y Nombres</th>
                    <th style="width: 22%;">N Documento</th>
                    <th style="width: 20%;">Posicion</th>
                    <th style="width: 8%;">Firma</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= 6; $i++)
                <tr>
                    <td>{{ $i }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                @endfor
            </tbody>
        </table>

        <div class="sub-section">Suplentes</div>
        <table class="players-table">
            <thead>
                <tr>
                    <th style="width: 8%;">N</th>
                    <th style="width: 42%;">Apellidos y Nombres</th>
                    <th style="width: 22%;">N Documento</th>
                    <th style="width: 20%;">Posicion</th>
                    <th style="width: 8%;">Firma</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= 6; $i++)
                <tr>
                    <td>{{ $i }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                @endfor
            </tbody>
        </table>

        <div class="claims-box">
            <span class="claims-label">Espacio para reclamos del delegado</span>
        </div>

        <div class="signature-box">
            <div class="line">
                Firma del Delegado<br>
                <small>{{ $data['visitor_manager'] }}</small>
            </div>
        </div>

        <div class="footer-text">
            Documento generado por el sistema de gestion deportiva.
        </div>
    </div>

</body>
</html>
