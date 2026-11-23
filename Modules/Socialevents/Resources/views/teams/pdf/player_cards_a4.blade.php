<html>
    <head>
        <meta charset="UTF-8">
        <style>
            @page { margin: 10mm; }
            body {
                font-family: 'Helvetica', 'Arial', sans-serif;
                color: #111827;
                font-size: 11px;
                margin: 0;
                padding: 0;
            }
            /* Cada fila de la tabla agrupa tarjetas; DomPDF no soporta grid, por eso se usa tabla. */
            .sheet {
                width: 100%;
                border-collapse: separate;
                border-spacing: 6mm 6mm;
            }
            .sheet td {
                width: 33%;
                vertical-align: top;
                padding: 0;
            }
            /* Tamaño reducido para que tres tarjetas quepan en una fila A4. */
            .card {
                width: 180px;
                height: 250px;
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
                width: 90px;
                height: 105px;
                border: 2px solid #1e3a8a;
                border-radius: 6px;
            }
            .photo-empty {
                width: 90px;
                height: 105px;
                border: 2px dashed #9ca3af;
                border-radius: 6px;
                color: #9ca3af;
                font-size: 10px;
                line-height: 105px;
                margin: 0 auto;
            }
            .player-names {
                font-size: 12px;
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
        <table class="sheet">
            @foreach (array_chunk($cards, 3) as $fila)
                <tr>
                    @foreach ($fila as $card)
                        <td>
                            <div class="card">
                                <div class="header">
                                    <div class="event-name">{{ $card['event_name'] }}</div>
                                    <div class="edition-name">{{ $card['edition_name'] }}</div>
                                </div>

                                <div class="photo-wrap">
                                    @if ($card['photo'])
                                        <img class="photo" src="{{ $card['photo'] }}" alt="Foto del jugador">
                                    @else
                                        <div class="photo-empty">Sin foto</div>
                                    @endif
                                </div>

                                <div class="player-names">
                                    {{ $card['names'] }}<br>{{ $card['last_names'] }}
                                </div>

                                <div>
                                    <span class="team-name">{{ $card['team_name'] }}</span>
                                </div>
                            </div>
                        </td>
                    @endforeach
                    {{-- Completa la fila con celdas vacías para mantener la cuadrícula alineada. --}}
                    @for ($i = count($fila); $i < 3; $i++)
                        <td></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    </body>
</html>
