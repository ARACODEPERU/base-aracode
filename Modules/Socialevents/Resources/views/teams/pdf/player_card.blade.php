<html>
    <head>
        <meta charset="UTF-8">
        <style>
            @page { margin: 0; }
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                color: #111827;
                font-size: 11px;
                margin: 0;
                padding: 0;
            }
            .card {
                width: 243px;
                height: 340px;
                border: 2px solid #1e3a8a;
                border-radius: 8px;
                text-align: center;
                overflow: hidden;
            }
            .header {
                background: #1e3a8a;
                color: #ffffff;
                padding: 10px 8px;
            }
            .event-name {
                font-size: 11px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .edition-name {
                font-size: 9px;
                margin-top: 3px;
                color: #dbeafe;
            }
            .photo-wrap {
                padding-top: 12px;
            }
            .photo {
                width: 110px;
                height: 130px;
                border: 2px solid #1e3a8a;
                border-radius: 6px;
            }
            .photo-empty {
                width: 110px;
                height: 130px;
                border: 2px dashed #9ca3af;
                border-radius: 6px;
                color: #9ca3af;
                font-size: 10px;
                line-height: 130px;
                margin: 0 auto;
            }
            .player-names {
                font-size: 14px;
                font-weight: bold;
                text-transform: uppercase;
                color: #1e3a8a;
                margin-top: 10px;
                padding: 0 10px;
            }
            .team-name {
                display: inline-block;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 4px;
                font-size: 11px;
                font-weight: bold;
                text-transform: uppercase;
                color: #111827;
                padding: 4px 10px;
                margin-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="header">
                <div class="event-name">{{ $data['event_name'] }}</div>
                <div class="edition-name">{{ $data['edition_name'] }}</div>
            </div>

            <div class="photo-wrap">
                @if ($data['photo'])
                    <img class="photo" src="{{ $data['photo'] }}" alt="Foto del jugador">
                @else
                    <div class="photo-empty">Sin foto</div>
                @endif
            </div>

            <div class="player-names">
                {{ $data['names'] }}<br>{{ $data['last_names'] }}
            </div>

            <div>
                <span class="team-name">{{ $data['team_name'] }}</span>
            </div>
        </div>
    </body>
</html>
