@php
    $profile = $profile ?? null;
    $player = $profile['player'] ?? null;
    $scorers = $profile['scorers'] ?? [
        'in_table' => false,
        'position' => null,
        'total' => 0,
        'criteria' => [],
        'has_goals' => false,
    ];
    $awards = $profile['awards'] ?? [];
    $matches = $profile['matches'] ?? [];
    $mvpMatches = $profile['mvp_matches'] ?? [];
    $scoringMatches = $profile['scoring_matches'] ?? [];

    $eventTitle = $eventTitle ?? ($edition->evento->title ?? 'Torneo');
    $landingUrl = $edition->landingUrl();
    $playerName = $player['name'] ?? 'Jugador no encontrado';
    $seoTitle = $seoTitle ?? ($playerName . ' | ' . $eventTitle);
    $canonicalUrl = $canonicalUrl ?? $landingUrl;
    $seoImage = $seoImage ?? ($player['photo'] ?? ($heroImage ?? null));

    $statCards = $player ? [
        ['label' => 'Partidos jugados', 'value' => $player['matches_played'], 'icon' => 'fa-shirt', 'tone' => 'blue'],
        ['label' => 'Goles', 'value' => $player['goals'], 'icon' => 'fa-futbol', 'tone' => 'green'],
        ['label' => 'Asistencias', 'value' => $player['assists'], 'icon' => 'fa-handshake', 'tone' => 'teal'],
        ['label' => 'MVP', 'value' => $player['mvp'], 'icon' => 'fa-star', 'tone' => 'gold'],
        ['label' => 'Sanciones', 'value' => $player['sanctions'], 'icon' => 'fa-square', 'tone' => 'red'],
    ] : [];

    $toneClass = [
        'yellow' => 'se-pf-tag--yellow',
        'red' => 'se-pf-tag--red',
        'double_yellow' => 'se-pf-tag--double',
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $seoDescription ?? ($eventTitle . ' — ' . $edition->name) }}">
    <title>{{ $seoTitle }}</title>
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="profile">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription ?? ($eventTitle . ' — ' . $edition->name) }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if (filled($seoImage))
        <meta property="og:image" content="{{ $seoImage }}">
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @vite(['Modules/Socialevents/Resources/assets/js/torneos-player.js'])
</head>
<body class="se-pf">

    <header class="se-pf-top">
        <div class="se-pf-shell se-pf-top__inner">
            <a class="se-pf-back" href="{{ $landingUrl }}">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span>Volver al torneo</span>
            </a>
            <span class="se-pf-top__edition">{{ $eventTitle }} · {{ $edition->name }}</span>
        </div>
    </header>

    @if (!$profile)
        <main class="se-pf-notfound">
            <div class="se-pf-shell">
                <div class="se-pf-notfound__icon" aria-hidden="true">
                    <i class="fas fa-futbol"></i>
                </div>
                <h1>Jugador no encontrado</h1>
                <p>Este jugador no forma parte de la edición <strong>{{ $edition->name }}</strong> o sus estadísticas todavía no fueron registradas.</p>
                <a class="se-pf-btn se-pf-btn--primary" href="{{ $landingUrl }}">
                    <i class="fas fa-trophy" aria-hidden="true"></i> Ver el torneo
                </a>
            </div>
        </main>
    @else
        <main>
            {{-- Cabecera: identidad del jugador sobre una cancha dibujada con CSS. --}}
            <section class="se-pf-hero">
                <div class="se-pf-hero__pitch" aria-hidden="true">
                    <span class="se-pf-hero__circle"></span>
                    <span class="se-pf-hero__box se-pf-hero__box--left"></span>
                    <span class="se-pf-hero__box se-pf-hero__box--right"></span>
                </div>

                <div class="se-pf-shell se-pf-hero__inner">
                    <div class="se-pf-avatar">
                        @if (filled($player['photo']))
                            <img src="{{ $player['photo'] }}" alt="{{ $playerName }}" loading="eager" decoding="async">
                        @else
                            <span class="se-pf-avatar__initials" aria-hidden="true">{{ $player['initials'] }}</span>
                        @endif
                    </div>

                    <div class="se-pf-identity">
                        <p class="se-pf-eyebrow">
                            <i class="fas fa-shield-halved" aria-hidden="true"></i>
                            Ficha del jugador
                        </p>
                        <h1 class="se-pf-name">{{ $playerName }}</h1>

                        <ul class="se-pf-chips">
                            @if (filled($player['team_name']))
                                <li class="se-pf-chip se-pf-chip--team">
                                    @if (filled($player['team_logo']))
                                        <img src="{{ $player['team_logo'] }}" alt="" width="18" height="18" loading="lazy">
                                    @else
                                        <i class="fas fa-shield" aria-hidden="true"></i>
                                    @endif
                                    {{ $player['team_name'] }}
                                </li>
                            @endif
                            @if (filled($player['jersey_number']))
                                <li class="se-pf-chip">
                                    <i class="fas fa-hashtag" aria-hidden="true"></i>
                                    {{ $player['jersey_number'] }}
                                </li>
                            @endif
                            @if (filled($player['position']))
                                <li class="se-pf-chip">{{ $player['position'] }}</li>
                            @endif
                        </ul>

                        @if (count($awards) > 0)
                            <ul class="se-pf-awards" aria-label="Reconocimientos del jugador">
                                @foreach ($awards as $award)
                                    <li class="se-pf-award">
                                        <i class="fas fa-medal" aria-hidden="true"></i>
                                        {{ $award['label'] }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    @if ($scorers['in_table'])
                        <div class="se-pf-rank">
                            <span class="se-pf-rank__value">#{{ $scorers['position'] }}</span>
                            <span class="se-pf-rank__label">Tabla de goleadores</span>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Totales del jugador en la edición. --}}
            <section class="se-pf-shell se-pf-stats" aria-label="Estadísticas del jugador">
                @foreach ($statCards as $card)
                    <article class="se-pf-stat se-pf-stat--{{ $card['tone'] }}">
                        <span class="se-pf-stat__icon" aria-hidden="true">
                            <i class="fas {{ $card['icon'] }}"></i>
                        </span>
                        <span class="se-pf-stat__value">{{ $card['value'] }}</span>
                        <span class="se-pf-stat__label">{{ $card['label'] }}</span>
                    </article>
                @endforeach
            </section>

            <div class="se-pf-shell se-pf-grid">
                {{-- Explicación del puesto en la tabla de goleadores. --}}
                <section class="se-pf-card se-pf-card--scorers" aria-labelledby="se-pf-scorers-title">
                    <header class="se-pf-card__head">
                        <h2 id="se-pf-scorers-title">¿Por qué está en la tabla de goleadores?</h2>
                        <p>Criterios que aplica el torneo: goles, menos partidos jugados y asistencias.</p>
                    </header>

                    @if ($scorers['in_table'])
                        <div class="se-pf-rankline">
                            <span class="se-pf-rankpill">#{{ $scorers['position'] }}</span>
                            <span>de {{ $scorers['total'] }} jugadores en la tabla</span>
                        </div>
                    @elseif ($scorers['has_goals'])
                        <p class="se-pf-note">
                            <i class="fas fa-circle-info" aria-hidden="true"></i>
                            Suma goles en esta edición, pero no figura en la tabla publicada.
                        </p>
                    @else
                        <p class="se-pf-note">
                            <i class="fas fa-circle-info" aria-hidden="true"></i>
                            Todavía no registra goles en esta edición, por eso no aparece en la tabla de goleadores.
                        </p>
                    @endif

                    @if ($scorers['in_table'] || $scorers['has_goals'])
                        <ul class="se-pf-criteria">
                            @foreach ($scorers['criteria'] as $criterion)
                                <li>
                                    <span class="se-pf-criteria__concept">{{ $criterion['concept'] }}</span>
                                    <strong class="se-pf-criteria__value">{{ $criterion['value'] }}</strong>
                                    <small class="se-pf-criteria__note">{{ $criterion['note'] }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                {{-- Partidos en los que fue elegido MVP. --}}
                <section class="se-pf-card se-pf-card--mvp" aria-labelledby="se-pf-mvp-title">
                    <header class="se-pf-card__head">
                        <h2 id="se-pf-mvp-title">Partidos donde fue MVP</h2>
                        <p>{{ count($mvpMatches) }} {{ count($mvpMatches) === 1 ? 'reconocimiento' : 'reconocimientos' }} en esta edición.</p>
                    </header>

                    @forelse ($mvpMatches as $row)
                        <article class="se-pf-mvp">
                            <span class="se-pf-mvp__badge" aria-label="MVP del partido">
                                <i class="fas fa-star" aria-hidden="true"></i> MVP
                            </span>
                            <div class="se-pf-mvp__match">
                                <span>{{ $row['home'] }}</span>
                                <strong>{{ $row['score'] ?? 'vs' }}</strong>
                                <span>{{ $row['away'] }}</span>
                            </div>
                            <p class="se-pf-mvp__meta">
                                @if ($row['date'])
                                    <span>{{ $row['date'] }}</span>
                                @endif
                                <span>{{ $row['phase'] }}@if ($row['round']) · Fecha {{ $row['round'] }}@endif</span>
                            </p>
                        </article>
                    @empty
                        <p class="se-pf-empty">
                            <i class="fas fa-star-half-stroke" aria-hidden="true"></i>
                            Aún no fue elegido MVP en un partido de esta edición.
                        </p>
                    @endforelse
                </section>
            </div>

            {{-- Partidos en los que anotó. --}}
            <section class="se-pf-shell se-pf-card se-pf-card--goals" aria-labelledby="se-pf-goals-title">
                <header class="se-pf-card__head">
                    <h2 id="se-pf-goals-title">Partidos donde anotó</h2>
                    <p>{{ $player['goals'] }} {{ $player['goals'] === 1 ? 'gol' : 'goles' }} acumulados en la edición.</p>
                </header>

                @forelse ($scoringMatches as $row)
                    <article class="se-pf-goal">
                        <span class="se-pf-goal__badge">
                            <i class="fas fa-futbol" aria-hidden="true"></i>
                            {{ $row['goals'] }} {{ $row['goals'] === 1 ? 'gol' : 'goles' }}
                        </span>
                        <div class="se-pf-goal__match">
                            <span>{{ $row['home'] }}</span>
                            <strong>{{ $row['score'] ?? 'vs' }}</strong>
                            <span>{{ $row['away'] }}</span>
                        </div>
                        <span class="se-pf-goal__date">{{ $row['date'] }}</span>
                    </article>
                @empty
                    <p class="se-pf-empty">
                        <i class="fas fa-futbol" aria-hidden="true"></i>
                        Todavía no registra goles en esta edición.
                    </p>
                @endforelse
            </section>

            {{-- Historial partido a partido. --}}
            <section class="se-pf-shell se-pf-card se-pf-card--history" aria-labelledby="se-pf-history-title">
                <header class="se-pf-card__head">
                    <h2 id="se-pf-history-title">Partido a partido</h2>
                    <p>Goles, asistencias, MVP y sanciones registrados en cada encuentro.</p>
                </header>

                @if (count($matches) > 0)
                    <div class="se-pf-table-wrap">
                        <table class="se-pf-table">
                            <caption class="se-pf-sr">Participación del jugador en cada partido de la edición</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Partido</th>
                                    <th scope="col" class="se-pf-table__num">Goles</th>
                                    <th scope="col" class="se-pf-table__num">Asistencias</th>
                                    <th scope="col" class="se-pf-table__center">MVP</th>
                                    <th scope="col">Sanciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($matches as $row)
                                    <tr @class(['is-mvp' => $row['is_mvp']])>
                                        <td data-label="Fecha">
                                            <span class="se-pf-date">{{ $row['date'] ?? 'Por definir' }}</span>
                                            @if ($row['time'])
                                                <small class="se-pf-time">{{ $row['time'] }}</small>
                                            @endif
                                        </td>
                                        <td data-label="Partido">
                                            <div class="se-pf-versus">
                                                <span>{{ $row['home'] }}</span>
                                                <strong>{{ $row['score'] ?? 'vs' }}</strong>
                                                <span>{{ $row['away'] }}</span>
                                            </div>
                                            <small class="se-pf-phase">
                                                {{ $row['phase'] }}@if ($row['round']) · Fecha {{ $row['round'] }}@endif
                                                @if ($row['shootout']) · Penales @endif
                                            </small>
                                        </td>
                                        <td data-label="Goles" class="se-pf-table__num">
                                            @if ($row['goals'] > 0)
                                                <strong class="se-pf-num">{{ $row['goals'] }}</strong>
                                            @else
                                                <span class="se-pf-muted">—</span>
                                            @endif
                                        </td>
                                        <td data-label="Asistencias" class="se-pf-table__num">
                                            @if ($row['assists'] > 0)
                                                <strong class="se-pf-num">{{ $row['assists'] }}</strong>
                                            @else
                                                <span class="se-pf-muted">—</span>
                                            @endif
                                        </td>
                                        <td data-label="MVP" class="se-pf-table__center">
                                            @if ($row['is_mvp'])
                                                <span class="se-pf-tag se-pf-tag--mvp"><i class="fas fa-star" aria-hidden="true"></i> MVP</span>
                                            @else
                                                <span class="se-pf-muted">—</span>
                                            @endif
                                        </td>
                                        <td data-label="Sanciones">
                                            @forelse ($row['sanctions'] as $sanction)
                                                <span class="se-pf-tag {{ $toneClass[$sanction['type']] ?? '' }}">{{ $sanction['label'] }}</span>
                                            @empty
                                                <span class="se-pf-muted">—</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="se-pf-empty">
                        <i class="fas fa-shirt" aria-hidden="true"></i>
                        Todavía no hay partidos registrados para este jugador en la edición.
                    </p>
                @endif
            </section>
        </main>

        <footer class="se-pf-foot">
            <div class="se-pf-shell se-pf-foot__inner">
                <p>© {{ now()->year }} {{ $eventTitle }} · {{ $edition->name }}</p>
                <a href="{{ $landingUrl }}">Ver fixture, tabla y goleadores</a>
            </div>
        </footer>
    @endif
</body>
</html>
