@php
    $profile = $profile ?? null;
    $team = $profile['team'] ?? null;
    $record = $profile['record'] ?? [];
    $squad = $profile['squad'] ?? [];
    $squadSummary = $profile['squad_summary'] ?? ['registered' => 0, 'played' => 0];
    $leaders = $profile['leaders'] ?? [];
    $honours = $profile['honours'] ?? [];
    $discipline = $profile['discipline'] ?? [
        'rows' => [],
        'suspensions' => [],
        'cards' => 0,
        'players' => 0,
        'suspension_count' => 0,
        'yellow' => 0,
        'red' => 0,
        'double_yellow' => 0,
    ];
    $matches = $profile['matches'] ?? [];
    $timeline = $profile['timeline'] ?? [];

    $eventTitle = $eventTitle ?? ($edition->evento->title ?? 'Torneo');
    $landingUrl = $edition->landingUrl();
    $teamName = $team['name'] ?? 'Equipo no encontrado';
    $seoTitle = $seoTitle ?? ($teamName . ' | ' . $eventTitle);
    $canonicalUrl = $canonicalUrl ?? $landingUrl;
    $seoImage = $seoImage ?? ($team['logo'] ?? ($heroImage ?? null));

    $statCards = $team ? [
        ['label' => 'Partidos', 'value' => $record['played'], 'icon' => 'fa-shirt', 'tone' => 'blue'],
        ['label' => 'Ganados', 'value' => $record['won'], 'icon' => 'fa-trophy', 'tone' => 'green'],
        ['label' => 'Empatados', 'value' => $record['drawn'], 'icon' => 'fa-equals', 'tone' => 'gold'],
        ['label' => 'Perdidos', 'value' => $record['lost'], 'icon' => 'fa-arrow-trend-down', 'tone' => 'red'],
        ['label' => 'Goles a favor', 'value' => $record['goals_for'], 'icon' => 'fa-futbol', 'tone' => 'green'],
        ['label' => 'Goles en contra', 'value' => $record['goals_against'], 'icon' => 'fa-shield-halved', 'tone' => 'red'],
        ['label' => 'Diferencia', 'value' => ($record['goal_difference'] > 0 ? '+' : '') . $record['goal_difference'], 'icon' => 'fa-scale-balanced', 'tone' => 'teal'],
        ['label' => 'Puntos', 'value' => $record['points'], 'icon' => 'fa-star', 'tone' => 'gold'],
        ['label' => 'Sanciones', 'value' => $record['sanctions'], 'icon' => 'fa-square', 'tone' => 'red'],
    ] : [];

    $honourTone = [
        'gold' => 'se-tf-honour--gold',
        'silver' => 'se-tf-honour--silver',
        'bronze' => 'se-tf-honour--bronze',
    ];

    $badgeClass = match ($team['honour'] ?? null) {
        'Campeón' => 'se-tf-hero__badge',
        'Subcampeón' => 'se-tf-hero__badge se-tf-hero__badge--silver',
        'Tercer puesto' => 'se-tf-hero__badge se-tf-hero__badge--bronze',
        default => null,
    };
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
    @vite(['Modules/Socialevents/Resources/assets/js/torneos-team.js'])
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
                    <i class="fas fa-shield-halved"></i>
                </div>
                <h1>Equipo no encontrado</h1>
                <p>Este equipo no forma parte de la edición <strong>{{ $edition->name }}</strong> o su participación todavía no fue registrada.</p>
                <a class="se-pf-btn se-pf-btn--primary" href="{{ $landingUrl }}">
                    <i class="fas fa-trophy" aria-hidden="true"></i> Ver el torneo
                </a>
            </div>
        </main>
    @else
        <main>
            {{-- Cabecera: identidad del equipo sobre una cancha dibujada con CSS. --}}
            <section class="se-pf-hero">
                <div class="se-pf-hero__pitch" aria-hidden="true">
                    <span class="se-pf-hero__circle"></span>
                    <span class="se-pf-hero__box se-pf-hero__box--left"></span>
                    <span class="se-pf-hero__box se-pf-hero__box--right"></span>
                </div>

                <div class="se-pf-shell se-pf-hero__inner">
                    <div @class(['se-tf-crest', 'se-tf-crest--champion' => $team['is_champion']])>
                        @if (filled($team['logo']))
                            <img src="{{ $team['logo'] }}" alt="Escudo de {{ $teamName }}" loading="eager" decoding="async">
                        @else
                            <span class="se-tf-crest__initials" aria-hidden="true">{{ $team['initials'] }}</span>
                        @endif
                    </div>

                    <div class="se-pf-identity">
                        <p class="se-pf-eyebrow">
                            <i class="fas fa-shield-halved" aria-hidden="true"></i>
                            Historia del equipo
                        </p>
                        <h1 class="se-pf-name">{{ $teamName }}</h1>

                        <ul class="se-pf-chips">
                            <li class="se-pf-chip se-pf-chip--team">
                                <i class="fas fa-trophy" aria-hidden="true"></i>
                                {{ $eventTitle }}
                            </li>
                            <li class="se-pf-chip">
                                <i class="fas fa-calendar-days" aria-hidden="true"></i>
                                {{ $edition->name }}
                            </li>
                            <li class="se-pf-chip">
                                <i class="fas fa-users" aria-hidden="true"></i>
                                {{ $squadSummary['registered'] }} {{ $squadSummary['registered'] === 1 ? 'jugador' : 'jugadores' }}
                            </li>
                            @if ($team['rank'])
                                <li class="se-pf-chip">
                                    <i class="fas fa-ranking-star" aria-hidden="true"></i>
                                    {{ $team['rank_label'] }} de la tabla
                                </li>
                            @endif
                        </ul>

                        @if (filled($badgeClass))
                            <span class="{{ $badgeClass }}">
                                <i class="fas fa-trophy" aria-hidden="true"></i>
                                {{ $team['honour'] }}
                            </span>
                        @endif
                    </div>

                    @if ($team['rank'])
                        <div class="se-pf-rank">
                            <span class="se-pf-rank__value">{{ $team['rank_label'] }}</span>
                            <span class="se-pf-rank__label">{{ $team['position_label'] }}</span>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Totales oficiales del equipo en la edición. --}}
            <section class="se-pf-shell se-tf-stats" aria-label="Resumen estadístico del equipo">
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
                {{-- Logros: solo títulos y premios registrados. --}}
                <section class="se-pf-card" aria-labelledby="se-tf-honours-title">
                    <header class="se-pf-card__head">
                        <h2 id="se-tf-honours-title">Logros de la edición</h2>
                        <p>Reconocimientos oficiales registrados para este equipo.</p>
                    </header>

                    @if (count($honours) > 0)
                        <ul class="se-tf-honours">
                            @foreach ($honours as $honour)
                                <li class="se-tf-honour {{ $honourTone[$honour['tone']] ?? '' }}">
                                    <span class="se-tf-honour__icon" aria-hidden="true">
                                        <i class="fas {{ $honour['icon'] }}"></i>
                                    </span>
                                    <span class="se-tf-honour__body">
                                        <span class="se-tf-honour__label">{{ $honour['label'] }}</span>
                                        <span class="se-tf-honour__note">{{ $honour['description'] }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="se-pf-empty">
                            <i class="fas fa-medal" aria-hidden="true"></i>
                            Todavía no hay títulos ni premios oficiales registrados para este equipo en la edición.
                        </p>
                    @endif
                </section>

                {{-- Jugadores destacados por criterios verificables. --}}
                <section class="se-pf-card" aria-labelledby="se-tf-leaders-title">
                    <header class="se-pf-card__head">
                        <h2 id="se-tf-leaders-title">Jugadores destacados</h2>
                        <p>Según goles, asistencias y premios MVP registrados en las actas.</p>
                    </header>

                    @if (count($leaders) > 0)
                        <div class="se-tf-leaders">
                            @foreach ($leaders as $leader)
                                <article class="se-tf-leader se-tf-leader--{{ $leader['tone'] }}">
                                    <p class="se-tf-leader__head">
                                        <i class="fas {{ $leader['icon'] }}" aria-hidden="true"></i>
                                        {{ $leader['label'] }}
                                    </p>
                                    <ul class="se-tf-leader__players">
                                        @foreach ($leader['players'] as $player)
                                            <li class="se-tf-leader__player">
                                                <span class="se-tf-leader__avatar" aria-hidden="true">
                                                    @if (filled($player['photo']))
                                                        <img src="{{ $player['photo'] }}" alt="" loading="lazy">
                                                    @else
                                                        {{ $player['initials'] }}
                                                    @endif
                                                </span>
                                                @if (filled($player['profile_url']))
                                                    <a class="se-tf-leader__name" href="{{ $player['profile_url'] }}">{{ $player['name'] }}</a>
                                                @else
                                                    <span class="se-tf-leader__name">{{ $player['name'] }}</span>
                                                @endif
                                                <span class="se-tf-leader__value">{{ $leader['value'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="se-pf-empty">
                            <i class="fas fa-chart-simple" aria-hidden="true"></i>
                            Todavía no hay goles, asistencias ni MVP registrados para este equipo.
                        </p>
                    @endif
                </section>
            </div>

            {{-- Plantilla inscrita en esta edición. --}}
            <section class="se-pf-shell se-pf-card" aria-labelledby="se-tf-squad-title">
                <header class="se-pf-card__head">
                    <h2 id="se-tf-squad-title">Jugadores que defendieron estos colores</h2>
                    <p>
                        {{ $squadSummary['registered'] }} inscritos en la edición ·
                        {{ $squadSummary['played'] }} con partidos disputados.
                    </p>
                </header>

                @if (count($squad) > 0)
                    <div class="se-tf-squad">
                        @foreach ($squad as $player)
                            <article class="se-tf-player">
                                @if (filled($player['jersey_number']))
                                    <span class="se-tf-player__number" aria-label="Dorsal {{ $player['jersey_number'] }}">#{{ $player['jersey_number'] }}</span>
                                @endif
                                <span class="se-tf-player__avatar" aria-hidden="true">
                                    @if (filled($player['photo']))
                                        <img src="{{ $player['photo'] }}" alt="" loading="lazy">
                                    @else
                                        {{ $player['initials'] }}
                                    @endif
                                </span>
                                <div class="se-tf-player__body">
                                    @if (filled($player['profile_url']))
                                        <a class="se-tf-player__name" href="{{ $player['profile_url'] }}">{{ $player['name'] }}</a>
                                    @else
                                        <span class="se-tf-player__name">{{ $player['name'] }}</span>
                                    @endif
                                    <span class="se-tf-player__position">
                                        {{ $player['position'] ?: 'Jugador' }}
                                        @if ($player['played'] === 0)
                                            · sin partidos
                                        @endif
                                    </span>
                                    <p class="se-tf-player__stats">
                                        <span><strong>{{ $player['played'] }}</strong> PJ</span>
                                        <span><strong>{{ $player['goals'] }}</strong> goles</span>
                                        <span><strong>{{ $player['assists'] }}</strong> asist.</span>
                                        <span><strong>{{ $player['mvp'] }}</strong> MVP</span>
                                        @if ($player['sanctions'] > 0)
                                            <span class="is-card"><i class="fas fa-square" aria-hidden="true"></i> {{ $player['sanctions'] }}</span>
                                        @endif
                                    </p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="se-pf-empty">
                        <i class="fas fa-users" aria-hidden="true"></i>
                        Todavía no hay jugadores inscritos para este equipo en la edición.
                    </p>
                @endif
            </section>

            {{-- Disciplina: tarjetas por partido y suspensiones registradas. --}}
            <section class="se-pf-shell se-pf-card" aria-labelledby="se-tf-disc-title">
                <header class="se-pf-card__head">
                    <h2 id="se-tf-disc-title">Historial disciplinario</h2>
                    <p>Tarjetas y suspensiones registradas para los jugadores del equipo.</p>
                </header>

                @if ($discipline['cards'] === 0 && $discipline['suspension_count'] === 0)
                    <p class="se-pf-empty">
                        <i class="fas fa-shield-halved" aria-hidden="true"></i>
                        Sin sanciones registradas en esta edición.
                    </p>
                @else
                    <div class="se-tf-disc__summary">
                        <div class="se-tf-disc__stat">
                            <span class="se-tf-disc__value">{{ $discipline['cards'] }}</span>
                            <span class="se-tf-disc__label">Tarjetas</span>
                        </div>
                        <div class="se-tf-disc__stat">
                            <span class="se-tf-disc__value">{{ $discipline['yellow'] }}</span>
                            <span class="se-tf-disc__label">Amarillas</span>
                        </div>
                        <div class="se-tf-disc__stat">
                            <span class="se-tf-disc__value">{{ $discipline['red'] + $discipline['double_yellow'] }}</span>
                            <span class="se-tf-disc__label">Rojas</span>
                        </div>
                        <div class="se-tf-disc__stat">
                            <span class="se-tf-disc__value">{{ $discipline['players'] }}</span>
                            <span class="se-tf-disc__label">Jugadores</span>
                        </div>
                        <div class="se-tf-disc__stat">
                            <span class="se-tf-disc__value">{{ $discipline['suspension_count'] }}</span>
                            <span class="se-tf-disc__label">Suspensiones</span>
                        </div>
                    </div>

                    @foreach ($discipline['rows'] as $row)
                        <article class="se-tf-disc__row">
                            <span class="se-pf-tag {{ $row['type'] === 'yellow' ? 'se-pf-tag--yellow' : ($row['type'] === 'red' ? 'se-pf-tag--red' : 'se-pf-tag--double') }}">
                                {{ $row['label'] }}
                            </span>
                            <span class="se-tf-disc__player">{{ $row['player'] }}</span>
                            <span class="se-tf-disc__meta">
                                {{ $row['match'] ?? 'Partido por definir' }}
                                @if ($row['date'])
                                    · {{ $row['date'] }}
                                @endif
                                @if ($row['minute'] !== null)
                                    · {{ $row['minute'] }}′
                                @endif
                                @if ($row['phase'])
                                    · {{ $row['phase'] }}
                                @endif
                            </span>
                        </article>
                    @endforeach

                    @foreach ($discipline['suspensions'] as $suspension)
                        <article class="se-tf-suspension">
                            <span class="se-pf-tag se-pf-tag--red">{{ $suspension['label'] }}</span>
                            <strong>{{ $suspension['player'] }}</strong>
                            <span class="se-pf-muted">
                                {{ $suspension['status'] }}
                                @if ($suspension['from'] || $suspension['to'])
                                    · {{ $suspension['from'] ?? '—' }} a {{ $suspension['to'] ?? '—' }}
                                @endif
                            </span>
                        </article>
                    @endforeach
                @endif
            </section>

            {{-- Historial oficial, agrupado por la fase real del torneo. --}}
            <section class="se-pf-shell se-pf-card" aria-labelledby="se-tf-matches-title">
                <header class="se-pf-card__head">
                    <h2 id="se-tf-matches-title">Nuestro camino partido a partido</h2>
                    <p>Resultados oficiales de la edición, incluidos alargue y penales cuando se jugaron.</p>
                </header>

                @if (count($matches) > 0)
                    @php $currentPhase = null; @endphp
                    @foreach ($matches as $row)
                        @if ($row['phase'] !== $currentPhase)
                            @php $currentPhase = $row['phase']; @endphp
                            <h3 class="se-tf-phase-title">{{ $row['phase'] }}</h3>
                        @endif

                        <article class="se-tf-fixture se-tf-fixture--{{ $row['outcome'] }}">
                            <div class="se-tf-fixture__when">
                                <strong>{{ $row['date'] ?? 'Por definir' }}</strong>
                                @if ($row['time'])
                                    <span>{{ $row['time'] }}</span>
                                @endif
                            </div>

                            <div class="se-tf-fixture__main">
                                <p class="se-tf-fixture__rival">
                                    @if (filled($row['rival_logo']))
                                        <img src="{{ $row['rival_logo'] }}" alt="" loading="lazy">
                                    @else
                                        <i class="fas fa-shield" aria-hidden="true"></i>
                                    @endif
                                    @if (filled($row['rival_url']))
                                        <a href="{{ $row['rival_url'] }}">{{ $row['rival'] }}</a>
                                    @else
                                        <span>{{ $row['rival'] }}</span>
                                    @endif
                                </p>
                                <p class="se-tf-fixture__meta">
                                    <span>{{ $row['side_label'] }}</span>
                                    @if ($row['group'])
                                        <span>{{ $row['group'] }}</span>
                                    @endif
                                    @if ($row['round'])
                                        <span>Fecha {{ $row['round'] }}</span>
                                    @endif
                                    @if ($row['venue'])
                                        <span>{{ $row['venue'] }}</span>
                                    @endif
                                    @if ($row['shootout'])
                                        <span>Definido por penales</span>
                                    @endif
                                </p>
                            </div>

                            <div class="se-tf-fixture__score">
                                @if ($row['played'])
                                    <span class="se-tf-fixture__numbers">{{ $row['team_score'] }} - {{ $row['rival_score'] }}</span>
                                    <span class="se-tf-fixture__result">{{ $row['outcome_label'] }}</span>
                                    @if ($row['shootout'])
                                        <span class="se-pf-muted" style="font-size:.66rem;">Oficial: {{ $row['score'] }}</span>
                                    @endif
                                @else
                                    <span class="se-tf-fixture__numbers">vs</span>
                                    <span class="se-tf-fixture__result">{{ $row['outcome_label'] }}</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                @else
                    <p class="se-pf-empty">
                        <i class="fas fa-calendar-xmark" aria-hidden="true"></i>
                        Todavía no hay partidos programados para este equipo en la edición.
                    </p>
                @endif
            </section>

            {{-- Cronología construida solo con resultados confirmados. --}}
            <section class="se-pf-shell se-pf-card" aria-labelledby="se-tf-history-title">
                <header class="se-pf-card__head">
                    <h2 id="se-tf-history-title">El camino hacia la gloria</h2>
                    <p>Los hitos confirmados de su participación: debut, victorias, fases y final.</p>
                </header>

                @if (count($timeline) > 0)
                    <ol class="se-tf-timeline">
                        @foreach ($timeline as $milestone)
                            <li class="se-tf-milestone se-tf-milestone--{{ $milestone['tone'] }}">
                                <span class="se-tf-milestone__dot" aria-hidden="true">
                                    <i class="fas {{ $milestone['icon'] }}"></i>
                                </span>
                                <h3 class="se-tf-milestone__title">{{ $milestone['title'] }}</h3>
                                <p class="se-tf-milestone__desc">{{ $milestone['description'] }}</p>
                                <p class="se-tf-milestone__meta">
                                    @if ($milestone['date'])
                                        <span>{{ $milestone['date'] }}</span>
                                    @endif
                                    @if ($milestone['phase'])
                                        <span>{{ $milestone['phase'] }}</span>
                                    @endif
                                    @if ($milestone['score'])
                                        <span>{{ $milestone['score'] }}</span>
                                    @endif
                                </p>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="se-pf-empty">
                        <i class="fas fa-route" aria-hidden="true"></i>
                        La cronología se mostrará cuando haya partidos confirmados en la edición.
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
