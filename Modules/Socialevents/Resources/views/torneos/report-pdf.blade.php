<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de temporada · {{ $report['edition']['name'] }}</title>
    <style>
        @page { margin: 13mm 11mm 15mm 11mm; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            color: #1f2937;
            line-height: 1.35;
        }

        .cover {
            border-bottom: 3px solid #0b1f2b;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .cover__eyebrow {
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: #4d7c0f;
        }

        .cover h1 {
            margin: 2px 0 0;
            font-size: 21px;
            line-height: 1.1;
            color: #0b1f2b;
        }

        .cover__event {
            margin-top: 2px;
            font-size: 11px;
            font-weight: bold;
            color: #374151;
        }

        .cover__meta {
            margin-top: 4px;
            font-size: 8px;
            color: #6b7280;
        }

        .status {
            display: inline-block;
            margin-top: 5px;
            padding: 2px 7px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #374151;
        }

        .warning {
            margin-top: 8px;
            padding: 5px 7px;
            border: 1px solid #fcd34d;
            background: #fffbeb;
            color: #92400e;
            font-size: 8px;
        }

        .section {
            margin-top: 13px;
        }

        .section h2 {
            margin: 0 0 6px;
            padding: 3px 0 3px 7px;
            border-left: 5px solid #b8f34a;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #0b1f2b;
            page-break-after: avoid;
        }

        .section__sub {
            margin: -3px 0 6px;
            color: #6b7280;
            font-size: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #0b1f2b;
            color: #ffffff;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 3px 4px;
            border: 1px solid #0b1f2b;
            text-align: center;
        }

        th.left, td.left { text-align: left; }

        td {
            padding: 2.5px 4px;
            border: 1px solid #d1d5db;
            text-align: center;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) td { background: #f9fafb; }

        .strong { font-weight: bold; color: #111827; }
        .accent { font-weight: bold; color: #4d7c0f; }
        .muted { color: #6b7280; }
        .small { font-size: 7px; }

        .kpi-table td {
            width: 12.5%;
            padding: 6px 4px;
            background: #f3f4f6;
        }

        .kpi-table .kpi__label {
            font-size: 6.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #6b7280;
        }

        .kpi-table .kpi__value {
            margin-top: 2px;
            font-size: 13px;
            font-weight: bold;
            color: #0b1f2b;
        }

        .team-block, .player-block {
            margin-top: 8px;
            padding: 6px 7px;
            border: 1px solid #d1d5db;
            page-break-inside: avoid;
        }

        .block-head {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .block-head__title {
            display: table-cell;
            font-size: 10px;
            font-weight: bold;
            color: #0b1f2b;
        }

        .block-head__meta {
            display: table-cell;
            text-align: right;
            font-size: 7.5px;
            color: #4b5563;
        }

        .badge {
            display: inline-block;
            padding: 1px 5px;
            margin-right: 4px;
            border-radius: 8px;
            background: #ecfccb;
            color: #3f6212;
            font-size: 6.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .highlights td { text-align: left; }
        .highlights .label { width: 24%; font-weight: bold; color: #374151; }

        .footer {
            margin-top: 14px;
            padding-top: 5px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            font-size: 7px;
            color: #9ca3af;
        }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    @php
        $show = fn (string $key) => in_array($key, $sections, true);
        $edition = $report['edition'];
        $summary = $report['summary'];
    @endphp

    <header class="cover">
        <div class="cover__eyebrow">Reporte resumen de fin de temporada</div>
        <h1>{{ $edition['name'] }} · {{ $edition['year'] }}</h1>
        <div class="cover__event">{{ $edition['event_title'] }}</div>
        <div class="cover__meta">
            {{ $edition['format_label'] }} ·
            @if ($edition['start_date']) Del {{ \Illuminate\Support\Carbon::parse($edition['start_date'])->format('d/m/Y') }} @endif
            @if ($edition['end_date']) al {{ \Illuminate\Support\Carbon::parse($edition['end_date'])->format('d/m/Y') }} @endif
            · Generado el {{ $report['generated_at'] }}
        </div>
        <span class="status">{{ $edition['status_label'] }}</span>
    </header>

    @if (! $edition['status_finished'])
        <div class="warning">
            La edición aún no está finalizada. Este reporte es una previsualización y los datos pueden cambiar.
        </div>
    @endif

    @if ($show('summary'))
        <section class="section">
            <h2>Resumen general</h2>
            <table class="kpi-table">
                <tr>
                    <td><div class="kpi__label">Equipos</div><div class="kpi__value">{{ $summary['teams'] }}</div></td>
                    <td><div class="kpi__label">Jugadores</div><div class="kpi__value">{{ $summary['players'] }}</div></td>
                    <td><div class="kpi__label">Partidos jugados</div><div class="kpi__value">{{ $summary['matches_played'] }}/{{ $summary['matches_total'] }}</div></td>
                    <td><div class="kpi__label">Goles</div><div class="kpi__value">{{ $summary['goals'] }}</div></td>
                    <td><div class="kpi__label">Promedio por partido</div><div class="kpi__value">{{ number_format((float) $summary['goals_average'], 2) }}</div></td>
                    <td><div class="kpi__label">Amarillas</div><div class="kpi__value">{{ $summary['yellow_cards'] }}</div></td>
                    <td><div class="kpi__label">Rojas</div><div class="kpi__value">{{ $summary['red_cards'] }}</div></td>
                    <td><div class="kpi__label">Campeón</div><div class="kpi__value" style="font-size:9px;">{{ $summary['champion_name'] ?: 'Por definir' }}</div></td>
                </tr>
            </table>
        </section>
    @endif

    @if ($show('standings'))
        <section class="section">
            <h2>Tabla de posiciones</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width:4%;">#</th>
                        <th class="left">Equipo</th>
                        <th style="width:6%;">PJ</th>
                        <th style="width:6%;">PG</th>
                        <th style="width:6%;">PE</th>
                        <th style="width:6%;">PP</th>
                        <th style="width:6%;">GF</th>
                        <th style="width:6%;">GC</th>
                        <th style="width:7%;">DG</th>
                        <th style="width:8%;">PTS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['standings'] as $row)
                        <tr>
                            <td class="accent">{{ $row['position'] }}</td>
                            <td class="left strong">{{ $row['team_name'] }}@if ($row['has_sanction']) <span class="small muted">*</span>@endif</td>
                            <td>{{ $row['matches_played'] }}</td>
                            <td>{{ $row['matches_won'] }}</td>
                            <td>{{ $row['matches_drawn'] }}</td>
                            <td>{{ $row['matches_lost'] }}</td>
                            <td>{{ $row['goals_for'] }}</td>
                            <td>{{ $row['goals_against'] }}</td>
                            <td>{{ $row['goal_difference'] > 0 ? '+' : '' }}{{ $row['goal_difference'] }}</td>
                            <td class="accent">{{ $row['points'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if (collect($report['standings'])->contains(fn ($row) => $row['has_sanction']))
                <p class="section__sub" style="margin-top:4px;">* Los puntos incluyen ajustes administrativos por sanción.</p>
            @endif
        </section>
    @endif

    @if ($show('teams'))
        <section class="section">
            <h2>Equipos participantes</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width:4%;">#</th>
                        <th class="left">Equipo</th>
                        <th class="left">Director técnico</th>
                        <th style="width:10%;">Jugadores</th>
                        <th style="width:8%;">Puesto</th>
                        <th style="width:8%;">Puntos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['teams'] as $team)
                        <tr>
                            <td>{{ $team['position'] }}</td>
                            <td class="left strong">{{ $team['team_name'] }}</td>
                            <td class="left">{{ $team['manager'] ?: 'Sin registrar' }}</td>
                            <td>{{ $team['players_count'] }}</td>
                            <td>{{ $team['position'] }}</td>
                            <td class="accent">{{ $team['points'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    @if ($show('podium'))
        <section class="section">
            <h2>Podio de la temporada</h2>
            <p class="section__sub">1.º y 2.º salen de la final; 3.º y 4.º del partido por el tercer puesto. Si no se jugó o terminó 0-0 sin penales, se ordenan por los puntos del torneo.</p>
            <table>
                <thead>
                    <tr>
                        <th class="left" style="width:16%;">Puesto</th>
                        <th class="left">Equipo</th>
                        <th class="left">Director técnico</th>
                        <th>PJ</th>
                        <th>G</th>
                        <th>E</th>
                        <th>P</th>
                        <th>GF</th>
                        <th>GC</th>
                        <th>DG</th>
                        <th>PTS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['podium'] as $spot)
                        <tr>
                            <td class="left strong">{{ $spot['label'] }}@if ($spot['is_champion']) · Campeón @endif</td>
                            <td class="left strong">{{ $spot['team_name'] }}</td>
                            <td class="left">{{ $spot['manager'] ?: '—' }}</td>
                            <td>{{ $spot['matches_played'] }}</td>
                            <td>{{ $spot['matches_won'] }}</td>
                            <td>{{ $spot['matches_drawn'] }}</td>
                            <td>{{ $spot['matches_lost'] }}</td>
                            <td>{{ $spot['goals_for'] }}</td>
                            <td>{{ $spot['goals_against'] }}</td>
                            <td>{{ $spot['goal_difference'] > 0 ? '+' : '' }}{{ $spot['goal_difference'] }}</td>
                            <td class="accent">{{ $spot['points'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    @if ($show('team_history'))
        <section class="section">
            <h2>Historia de partidos por equipo</h2>
            <p class="section__sub">Resultados, fechas, fase y tarjetas de cada encuentro.</p>

            @foreach ($report['team_history'] as $team)
                <div class="team-block">
                    <div class="block-head">
                        <div class="block-head__title">{{ $team['team_name'] }}</div>
                        <div class="block-head__meta">
                            {{ $team['totals']['played'] }} PJ ·
                            {{ $team['totals']['won'] }}G {{ $team['totals']['drawn'] }}E {{ $team['totals']['lost'] }}P ·
                            {{ $team['totals']['goals_for'] }} GF / {{ $team['totals']['goals_against'] }} GC ·
                            Amarillas {{ $team['totals']['yellow'] }} · Rojas {{ $team['totals']['red'] }}
                        </div>
                    </div>

                    @if (count($team['matches']) === 0)
                        <p class="small muted" style="margin:0;">Sin partidos jugados registrados.</p>
                    @else
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:9%;">Fecha</th>
                                    <th class="left" style="width:16%;">Fase</th>
                                    <th class="left">Rival</th>
                                    <th style="width:7%;">Cond.</th>
                                    <th style="width:6%;">Res.</th>
                                    <th style="width:13%;">Marcador</th>
                                    <th style="width:6%;">Amar.</th>
                                    <th style="width:6%;">Rojas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($team['matches'] as $line)
                                    <tr>
                                        <td>{{ $line['date'] ?: '—' }}</td>
                                        <td class="left">{{ $line['phase_label'] }}@if ($line['round']) · F{{ $line['round'] }}@endif</td>
                                        <td class="left">{{ $line['opponent'] }}</td>
                                        <td>{{ $line['venue'] }}</td>
                                        <td class="strong">{{ $line['result'] }}</td>
                                        <td>{{ $line['score'] }}</td>
                                        <td>{{ $line['yellow'] }}</td>
                                        <td>{{ $line['red'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    @if ($show('player_ranking'))
        <section class="section">
            <h2>Ranking de jugadores</h2>
            <p class="section__sub">Los cinco mejores jugadores y los cinco mejores porteros, con su detalle partido a partido.</p>

            @foreach ([
                ['key' => 'players', 'title' => 'Mejores jugadores', 'rows' => $report['player_ranking']['players']],
                ['key' => 'goalkeepers', 'title' => 'Mejores porteros', 'rows' => $report['player_ranking']['goalkeepers']],
            ] as $group)
                <div style="margin-top:8px;">
                    <div class="strong" style="font-size:10px; color:#0b1f2b; margin-bottom:3px;">{{ $group['title'] }}</div>

                    @if (count($group['rows']) === 0)
                        <p class="small muted" style="margin:0;">Sin datos suficientes para este ranking.</p>
                    @else
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:4%;">#</th>
                                    <th class="left">Jugador</th>
                                    <th class="left">Equipo</th>
                                    <th>PJ</th>
                                    <th>{{ $group['key'] === 'goalkeepers' ? 'Atajadas' : 'Goles' }}</th>
                                    <th>{{ $group['key'] === 'goalkeepers' ? 'Valla invicta' : 'Asistencias' }}</th>
                                    <th>MVP</th>
                                    <th>Amar.</th>
                                    <th>Rojas</th>
                                    <th>PTS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group['rows'] as $index => $player)
                                    <tr>
                                        <td class="accent">{{ $index + 1 }}</td>
                                        <td class="left strong">{{ $player['name'] }}@if ($player['jersey_number']) <span class="small muted">#{{ $player['jersey_number'] }}</span>@endif</td>
                                        <td class="left">{{ $player['team_name'] ?: '—' }}</td>
                                        <td>{{ $player['summary']['matches_played'] ?? 0 }}</td>
                                        <td class="strong">{{ $group['key'] === 'goalkeepers' ? ($player['summary']['saves'] ?? 0) : ($player['summary']['goals'] ?? 0) }}</td>
                                        <td>{{ $group['key'] === 'goalkeepers' ? ($player['summary']['clean_sheet'] ?? 0) : ($player['summary']['assists'] ?? 0) }}</td>
                                        <td>{{ $player['summary']['mvp'] ?? 0 }}</td>
                                        <td>{{ $player['cards']['yellow'] }}</td>
                                        <td>{{ $player['cards']['red'] }}</td>
                                        <td class="accent">{{ number_format((float) $player['points'], 1) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @foreach ($group['rows'] as $index => $player)
                            <div class="player-block">
                                <div class="block-head">
                                    <div class="block-head__title">{{ $index + 1 }}. {{ $player['name'] }} · {{ $player['team_name'] ?: 'Sin equipo' }}</div>
                                    <div class="block-head__meta">
                                        {{ $player['summary']['matches_played'] ?? 0 }} PJ ·
                                        Goles {{ $player['summary']['goals'] ?? 0 }} ·
                                        Asist. {{ $player['summary']['assists'] ?? 0 }} ·
                                        Atajadas {{ $player['summary']['saves'] ?? 0 }} ·
                                        MVP {{ $player['summary']['mvp'] ?? 0 }} ·
                                        {{ number_format((float) $player['points'], 1) }} pts
                                    </div>
                                </div>

                                @if (count($player['breakdown']) === 0)
                                    <p class="small muted" style="margin:0;">Sin partidos con estadísticas registradas.</p>
                                @else
                                    <table>
                                        <thead>
                                            <tr>
                                                <th class="left" style="width:26%;">Partido</th>
                                                <th style="width:11%;">Fecha</th>
                                                <th style="width:6%;">Goles</th>
                                                <th style="width:7%;">Asist.</th>
                                                <th style="width:7%;">Atajadas</th>
                                                <th style="width:5%;">Min.</th>
                                                <th style="width:5%;">MVP</th>
                                                <th style="width:5%;">VI</th>
                                                <th style="width:6%;">Amar.</th>
                                                <th style="width:6%;">Rojas</th>
                                                <th style="width:7%;">Pts</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($player['breakdown'] as $line)
                                                <tr>
                                                    <td class="left">
                                                        <span class="strong">{{ $line['label'] }}</span>
                                                        <span class="small muted"> · {{ $line['phase_label'] }}@if ($line['round']) (F{{ $line['round'] }})@endif</span>
                                                    </td>
                                                    <td>{{ $line['date'] ?: '—' }}</td>
                                                    <td>{{ $line['goals'] }}</td>
                                                    <td>{{ $line['assists'] }}</td>
                                                    <td>{{ $line['saves'] }}</td>
                                                    <td>{{ $line['minutes_played'] }}</td>
                                                    <td>{{ $line['is_mvp'] ? 'Sí' : '—' }}</td>
                                                    <td>{{ $line['clean_sheet'] ? 'Sí' : '—' }}</td>
                                                    <td>{{ $line['yellow'] }}</td>
                                                    <td>{{ $line['red'] }}</td>
                                                    <td class="accent">{{ $line['points'] ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    @if ($show('scorers'))
        <section class="section">
            <h2>Goleadores</h2>
            @if (count($report['scorers']) === 0)
                <p class="small muted">Todavía no hay goles registrados.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="width:4%;">#</th>
                            <th class="left">Jugador</th>
                            <th class="left">Equipo</th>
                            <th>PJ</th>
                            <th>Goles</th>
                            <th>Asistencias</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['scorers'] as $index => $scorer)
                            <tr>
                                <td class="accent">{{ $index + 1 }}</td>
                                <td class="left strong">{{ $scorer['name'] }}</td>
                                <td class="left">{{ $scorer['team_name'] ?: '—' }}</td>
                                <td>{{ $scorer['matches_played'] }}</td>
                                <td class="accent">{{ $scorer['goals'] }}</td>
                                <td>{{ $scorer['assists'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endif

    @if ($show('discipline'))
        <section class="section">
            <h2>Disciplina</h2>
            <p class="section__sub">
                {{ $report['discipline']['totals']['yellow'] }} amarillas ·
                {{ $report['discipline']['totals']['red'] }} rojas ·
                {{ $report['discipline']['totals']['total'] }} tarjetas en total
            </p>

            <table>
                <thead>
                    <tr><th class="left">Equipo</th><th>Amarillas</th><th>Rojas</th><th>Total</th></tr>
                </thead>
                <tbody>
                    @foreach ($report['discipline']['by_team'] as $team)
                        <tr>
                            <td class="left strong">{{ $team['team_name'] }}</td>
                            <td>{{ $team['yellow'] }}</td>
                            <td>{{ $team['red'] }}</td>
                            <td class="accent">{{ $team['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table style="margin-top:8px;">
                <thead>
                    <tr><th class="left">Jugador</th><th class="left">Equipo</th><th>Amarillas</th><th>Rojas</th></tr>
                </thead>
                <tbody>
                    @forelse ($report['discipline']['top_players'] as $player)
                        <tr>
                            <td class="left strong">{{ $player['name'] }}</td>
                            <td class="left">{{ $player['team_name'] ?: '—' }}</td>
                            <td>{{ $player['yellow'] }}</td>
                            <td>{{ $player['red'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">Sin tarjetas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>

        </section>
    @endif

    @if ($show('highlights'))
        <section class="section">
            <h2>Datos destacados</h2>
            <table class="highlights">
                <tbody>
                    <tr>
                        <td class="label">Mejor ataque</td>
                        <td>{{ $report['highlights']['best_attack']['team_name'] ?? 'Sin datos' }}@if (! empty($report['highlights']['best_attack'])) · {{ $report['highlights']['best_attack']['value'] }} goles a favor @endif</td>
                    </tr>
                    <tr>
                        <td class="label">Mejor defensa</td>
                        <td>{{ $report['highlights']['best_defense']['team_name'] ?? 'Sin datos' }}@if (! empty($report['highlights']['best_defense'])) · {{ $report['highlights']['best_defense']['value'] }} goles recibidos @endif</td>
                    </tr>
                    <tr>
                        <td class="label">Equipo más disciplinado</td>
                        <td>{{ $report['highlights']['most_disciplined']['team_name'] ?? 'Sin datos' }}@if (! empty($report['highlights']['most_disciplined'])) · {{ $report['highlights']['most_disciplined']['value'] }} tarjetas @endif</td>
                    </tr>
                    <tr>
                        <td class="label">Mayor goleada</td>
                        <td>
                            @if (! empty($report['highlights']['biggest_win']))
                                {{ $report['highlights']['biggest_win']['label'] }} · {{ $report['highlights']['biggest_win']['phase'] }} · {{ $report['highlights']['biggest_win']['date'] ?: 'Sin fecha' }}
                            @else
                                Sin datos
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Goleador</td>
                        <td>
                            @if (! empty($report['highlights']['top_scorer']))
                                {{ $report['highlights']['top_scorer']['name'] }} · {{ $report['highlights']['top_scorer']['goals'] }} goles · {{ $report['highlights']['top_scorer']['team_name'] ?: 'Sin equipo' }}
                            @else
                                Sin datos
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Más MVP</td>
                        <td>
                            @if (! empty($report['highlights']['mvp_leader']))
                                {{ $report['highlights']['mvp_leader']['name'] }} · {{ $report['highlights']['mvp_leader']['value'] }} premios · {{ $report['highlights']['mvp_leader']['team_name'] ?: 'Sin equipo' }}
                            @else
                                Sin datos
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    @endif

    <div class="footer">
        Reporte generado desde el panel de torneos · {{ $report['edition']['event_title'] }} · {{ $report['edition']['name'] }} {{ $report['edition']['year'] }}
    </div>
</body>
</html>
