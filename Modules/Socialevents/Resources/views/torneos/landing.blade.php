@php
    $eventTitle = $eventTitle ?? ($edition->evento->title ?? 'Torneo');
    $teamCount = $teamCount ?? count($edition->equipos);
    $heroImage = $heroImage ?? \Modules\Socialevents\Support\TournamentLandingPresenter::resolveHeroImage($edition);
    $seoTitle = $seoTitle ?? ($eventTitle . ' | ' . $edition->name);
    $seoDescription = $seoDescription ?? ($eventTitle . ' — ' . $edition->name . '. Fixture, posiciones y estadísticas.');
    $canonicalUrl = $canonicalUrl ?? $edition->landingUrl();
    $seoImage = $seoImage ?? $heroImage;
    $prizeSummary = $prizeSummary ?? null;
    $inscriptionLabel = $inscriptionLabel ?? (filled($edition->inscription_fee) ? 'S/ ' . number_format((float) $edition->inscription_fee, 0) : null);
    $prizePlaces = $prizePlaces ?? [];
    $hasPrizeSection = $hasPrizeSection ?? (count($prizePlaces) > 0);
    $accentColor = $accentColor ?? $edition->accentColor();
    $heroStatValue = $prizeSummary ?? ($inscriptionLabel ?? '—');
@endphp
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $seoDescription }}">
    <title>{{ $seoTitle }}</title>
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    @if (!empty($accentColor))
        <style>
            :root { --se-accent: {{ $accentColor }}; }
        </style>
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @vite(['Modules/Socialevents/Resources/assets/js/torneos-landing.js'])
</head>
<body class="se-landing">
    <div class="se-ambient" aria-hidden="true">
        <div class="se-ambient__grid"></div>
        <div class="se-ambient__orb se-ambient__orb--1"></div>
        <div class="se-ambient__orb se-ambient__orb--2"></div>
        <div class="se-ambient__orb se-ambient__orb--3"></div>
    </div>

    <header class="se-header">
        <div class="se-container se-header__inner">
            <a href="#inicio" class="se-brand">
                <div class="se-brand__icon" aria-hidden="true">
                    <i class="fas fa-trophy"></i>
                </div>
                <span class="se-brand__title">{{ $eventTitle }}<span>.</span></span>
            </a>

            <nav class="se-nav" aria-label="Secciones">
                <a href="#inicio">Inicio</a>
                <a href="#equipos">Equipos</a>
                @if ($hasPrizeSection)
                    <a href="#premios">Premios</a>
                @endif
                <a href="#fixture">Fixture</a>
                <a href="#posiciones">Posiciones</a>
                <a href="#contacto">Contacto</a>
            </nav>

            <div class="se-header__actions">
                <a href="#contacto" class="se-btn se-btn--primary se-btn--contact-desktop">Contacto</a>
                <button type="button" class="se-menu-toggle" data-se-menu-toggle aria-expanded="false" aria-controls="se-mobile-nav" aria-label="Abrir menú">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <nav id="se-mobile-nav" class="se-mobile-nav" data-se-mobile-nav aria-label="Menú móvil">
        <a href="#inicio">Inicio</a>
        <a href="#equipos">Equipos</a>
        @if ($hasPrizeSection)
            <a href="#premios">Premios</a>
        @endif
        <a href="#fixture">Fixture</a>
        <a href="#posiciones">Posiciones</a>
        <a href="#contacto">Contacto</a>
    </nav>

    <main>
        <section id="inicio" class="se-hero">
            <div class="se-hero__bg">
                <img src="{{ $heroImage }}" alt="{{ $eventTitle }}" loading="eager">
            </div>
            <div class="se-container se-hero__grid">
                <div class="se-hero__content">
                    <span class="se-badge" data-se-hero>{{ $edition->name }}</span>
                    <h1 class="se-hero__title" data-se-hero>
                        <span class="se-gradient">{{ $eventTitle }}</span>
                    </h1>
                    <div class="se-hero__desc" data-se-hero>
                        {!! $edition->evento->description ?? '<p>Seguí el torneo en vivo: fixture, tabla y mejores jugadores.</p>' !!}
                    </div>
                    <div class="se-hero__cta" data-se-hero>
                        <a href="#fixture" class="se-btn se-btn--primary se-btn--lg">Ver fixture</a>
                        <a href="#posiciones" class="se-btn se-btn--ghost se-btn--lg">Tabla de posiciones</a>
                    </div>
                </div>

                <div class="se-stat-grid">
                    <div class="se-stat-card" data-se-hero>
                        <i class="fas fa-users se-stat-card__icon" aria-hidden="true"></i>
                        <div class="se-stat-card__value" data-se-count="{{ $teamCount }}">{{ $teamCount }}</div>
                        <div class="se-stat-card__label">Equipos</div>
                    </div>
                    <div class="se-stat-card" data-se-hero>
                        <i class="fas fa-trophy se-stat-card__icon" aria-hidden="true"></i>
                        <div class="se-stat-card__value">{{ $heroStatValue }}</div>
                        <div class="se-stat-card__label">
                            @if ($prizeSummary && $inscriptionLabel)
                                Premio 1.º · Inscripción {{ $inscriptionLabel }}
                            @elseif ($prizeSummary)
                                Premio 1.er puesto
                            @elseif ($inscriptionLabel)
                                Inscripción
                            @else
                                Premios e inscripción
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="equipos" class="se-section se-section--alt">
            <div class="se-container">
                <header class="se-section__head" data-se-reveal>
                    <h2 class="se-section__title">Equipos <span>participantes</span></h2>
                    <p class="se-section__sub">{{ $teamCount }} equipos compitiendo en esta edición</p>
                </header>

                <div class="se-teams-grid">
                    @foreach ($edition->equipos as $equipo)
                        <article class="se-team-card" data-se-reveal>
                            <div class="se-team-card__logo">
                                @if ($equipo->equipo->logo_path)
                                    <img src="{{ asset('storage/' . $equipo->equipo->logo_path) }}" alt="{{ $equipo->equipo->name }}" loading="lazy">
                                @else
                                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                @endif
                            </div>
                            <div class="se-team-card__name">{{ $equipo->equipo->name }}</div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($hasPrizeSection)
            <section id="premios" class="se-section">
                <div class="se-container">
                    <header class="se-section__head" data-se-reveal>
                        <h2 class="se-section__title">Premios <span>del torneo</span></h2>
                        <p class="se-section__sub">Reconocimientos configurados para esta edición</p>
                    </header>
                    <div class="se-prizes-grid">
                        @foreach ($prizePlaces as $place)
                            <article class="se-prize-card" data-se-reveal>
                                <div class="se-prize-card__place">{{ $place['title'] }}</div>
                                <div class="se-prize-card__value">{{ $place['label_text'] }}</div>
                            </article>
                        @endforeach
                    </div>
                    @if ($inscriptionLabel)
                        <p class="se-section__sub se-prizes-inscription" data-se-reveal>
                            Inscripción por equipo: <strong>{{ $inscriptionLabel }}</strong>
                        </p>
                    @endif
                </div>
            </section>
        @endif

        <section id="fixture" class="se-section">
            <div class="se-container se-fixture-wrap">
                <header class="se-section__head" data-se-reveal>
                    <h2 class="se-section__title">Calendario <span>de partidos</span></h2>
                    <p class="se-section__sub">Resultados, horarios y fases del torneo</p>
                </header>

                @forelse ($matches as $phase => $rounds)
                    <div class="se-phase" data-se-reveal>
                        <h3 class="se-phase-title">{{ $phaseLabels[$phase] ?? \Modules\Socialevents\Support\TournamentPhaseLabels::label($phase) }}</h3>
                        @foreach ($rounds as $round => $roundMatches)
                            <details class="se-round">
                                <summary>
                                    Fecha {{ $round }}
                                    <i class="fas fa-chevron-down" aria-hidden="true"></i>
                                </summary>
                                <div class="se-round__body">
                                    @foreach ($roundMatches as $match)
                                        <article class="se-match">
                                            <div class="se-match__side">
                                                @if ($match->equipolocal)
                                                    @if ($match->equipolocal->logo_path)
                                                        <img src="{{ asset('storage/' . $match->equipolocal->logo_path) }}" alt="">
                                                    @else
                                                        <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                                    @endif
                                                    <span class="se-match__name">{{ $match->equipolocal->name }}</span>
                                                @else
                                                    <i class="fas fa-question" aria-hidden="true"></i>
                                                    <span class="se-match__name">Por definir</span>
                                                @endif
                                            </div>

                                            <div class="se-match__center">
                                                @if (in_array($match->status, ['finished', 'closed']))
                                                    <div class="se-match__score">{{ $match->score_h ?? 0 }} - {{ $match->score_a ?? 0 }}</div>
                                                    <div class="se-match__meta se-match__meta--done">Finalizado</div>
                                                @elseif ($match->status === 'live')
                                                    <div class="se-match__score se-match__meta--live">EN VIVO</div>
                                                    <div class="se-match__meta se-match__meta--live">Jugando</div>
                                                @else
                                                    <div class="se-match__score" style="font-size:0.85rem;color:var(--se-muted)">VS</div>
                                                    <div class="se-match__meta se-match__meta--pending">
                                                        {{ $match->match_date ? $match->match_date->format('d/m H:i') : 'Por definir' }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="se-match__side se-match__side--away">
                                                @if ($match->equipovisitante)
                                                    <span class="se-match__name">{{ $match->equipovisitante->name }}</span>
                                                    @if ($match->equipovisitante->logo_path)
                                                        <img src="{{ asset('storage/' . $match->equipovisitante->logo_path) }}" alt="">
                                                    @else
                                                        <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                                    @endif
                                                @else
                                                    <span class="se-match__name">Por definir</span>
                                                    <i class="fas fa-question" aria-hidden="true"></i>
                                                @endif
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                @empty
                    <p class="se-section__sub" data-se-reveal style="text-align:center">Aún no hay partidos programados.</p>
                @endforelse
            </div>
        </section>

        <section id="posiciones" class="se-section se-section--alt">
            <div class="se-container se-standings-layout">
                <div>
                    <h3 class="se-section__title" data-se-reveal style="text-align:left;margin-bottom:1.5rem">
                        Tabla de <span>posiciones</span>
                    </h3>
                    <div class="se-table-wrap" data-se-reveal>
                        <table class="se-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Equipo</th>
                                    <th>PTS</th>
                                    <th>PJ</th>
                                    <th>PG</th>
                                    <th>PE</th>
                                    <th>PP</th>
                                    <th>DG</th>
                                    <th>GF</th>
                                    <th>GC</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($currentEquipment as $index => $team)
                                    <tr class="{{ $index === 0 ? 'is-leader' : '' }}" data-se-reveal>
                                        <td>#{{ $index + 1 }}</td>
                                        <td>
                                            <div class="se-table__team">
                                                @if ($team->equipo->logo_path)
                                                    <img src="{{ asset('storage/' . $team->equipo->logo_path) }}" alt="">
                                                @else
                                                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                                @endif
                                                {{ $team->equipo->name }}
                                            </div>
                                        </td>
                                        <td class="pts">{{ $team->points }}</td>
                                        <td>{{ $team->matches_played }}</td>
                                        <td style="color:var(--se-green)">{{ $team->matches_won }}</td>
                                        <td style="color:var(--se-amber)">{{ $team->matches_drawn }}</td>
                                        <td style="color:var(--se-red)">{{ $team->matches_lost }}</td>
                                        <td style="color:{{ $team->goal_difference >= 0 ? 'var(--se-green)' : 'var(--se-red)' }}">{{ $team->goal_difference }}</td>
                                        <td>{{ $team->goals_for }}</td>
                                        <td>{{ $team->goals_against }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside>
                    <div class="se-ranking-block">
                        <h4 data-se-reveal>Jugadores <span>top</span></h4>
                        @forelse ($playersRanking as $index => $player)
                            <div class="se-rank-card {{ $index === 0 ? 'is-top' : '' }}" data-se-reveal>
                                <div class="se-rank-card__avatar">
                                    @if ($player['player']['person']->image ?? null)
                                        <img src="{{ asset('storage/' . $player['player']['person']->image) }}" alt="">
                                    @else
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($player['player']['person']->full_name) }}&background=1e293b&color=fff" alt="">
                                    @endif
                                    <span class="se-rank-card__pos">{{ $index + 1 }}</span>
                                </div>
                                <div>
                                    <div class="se-rank-card__name">{{ $player['player']['person']->full_name }}</div>
                                    <div class="se-rank-card__stats">
                                        {{ $player['stats']['goals'] }} goles · {{ $player['stats']['assists'] }} asist. · {{ $player['stats']['mvp'] }} MVP
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="se-section__sub" data-se-reveal>Sin datos de jugadores aún.</p>
                        @endforelse
                    </div>

                    <div class="se-ranking-block se-ranking-block--gk">
                        <h4 data-se-reveal>Porteros <span>top</span></h4>
                        @forelse ($goalkeepersRanking as $index => $gk)
                            <div class="se-rank-card {{ $index === 0 ? 'is-top' : '' }}" data-se-reveal>
                                <div class="se-rank-card__avatar">
                                    @if ($gk['player']['person']->image ?? null)
                                        <img src="{{ asset('storage/' . $gk['player']['person']->image) }}" alt="">
                                    @else
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($gk['player']['person']->full_name) }}&background=14532d&color=fff" alt="">
                                    @endif
                                    <span class="se-rank-card__pos">{{ $index + 1 }}</span>
                                </div>
                                <div>
                                    <div class="se-rank-card__name">{{ $gk['player']['person']->full_name }}</div>
                                    <div class="se-rank-card__stats">
                                        {{ $gk['stats']['saves'] }} atajadas · {{ $gk['stats']['clean_sheet'] }} valla invicta · {{ $gk['stats']['mvp'] }} MVP
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="se-section__sub" data-se-reveal>Sin datos de porteros aún.</p>
                        @endforelse
                    </div>
                </aside>
            </div>
        </section>

        <section id="contacto" class="se-section">
            <div class="se-container">
                <header class="se-section__head" data-se-reveal>
                    <h2 class="se-section__title">Contacto <span>del torneo</span></h2>
                    <p class="se-section__sub">Información y consultas con el organizador</p>
                </header>

                <div class="se-contact-grid">
                    <article class="se-contact-card" data-se-reveal>
                        <div class="se-contact-card__icon se-contact-card__icon--blue">
                            <i class="fas fa-user" aria-hidden="true"></i>
                        </div>
                        <h3>Organizador</h3>
                        <p class="se-contact-card__value">{{ $edition->contact_name ?? 'Por confirmar' }}</p>
                        <p class="se-contact-card__hint">Contacto principal</p>
                    </article>

                    <article class="se-contact-card" data-se-reveal>
                        <div class="se-contact-card__icon se-contact-card__icon--green">
                            <i class="fas fa-phone" aria-hidden="true"></i>
                        </div>
                        <h3>Teléfono</h3>
                        <p class="se-contact-card__value">{{ $edition->contact_phone ?? '—' }}</p>
                        <p class="se-contact-card__hint">WhatsApp o llamada</p>
                    </article>

                    <article class="se-contact-card" data-se-reveal>
                        <div class="se-contact-card__icon se-contact-card__icon--violet">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                        </div>
                        <h3>Edición</h3>
                        <p class="se-contact-card__value">{{ $edition->name }}</p>
                        <p class="se-contact-card__hint">
                            {{ $teamCount }} equipos
                            @if ($inscriptionLabel)
                                · Inscripción {{ $inscriptionLabel }}
                            @endif
                        </p>
                    </article>
                </div>

                @if ($edition->contact_phone || $edition->contact_whatsapp)
                    <div class="se-contact-cta" data-se-reveal>
                        @if ($edition->contact_phone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $edition->contact_phone) }}" class="se-btn se-btn--primary se-btn--lg">
                                <i class="fas fa-phone" aria-hidden="true"></i> Llamar ahora
                            </a>
                        @endif
                        @if ($edition->contact_whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/\D+/', '', $edition->contact_whatsapp) }}" target="_blank" rel="noopener" class="se-btn se-btn--ghost se-btn--lg" style="margin-left:0.5rem">
                                <i class="fab fa-whatsapp" aria-hidden="true"></i> WhatsApp
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </section>
    </main>

    <footer class="se-footer">
        <div class="se-container se-footer__inner">
            <a href="#inicio" class="se-brand">
                <div class="se-brand__icon" aria-hidden="true">
                    <i class="fas fa-trophy"></i>
                </div>
                <span class="se-brand__title">{{ $eventTitle }}<span>.</span></span>
            </a>
            <p class="se-footer__copy">
                © {{ now()->year }} {{ $eventTitle }} · {{ $edition->name }}
            </p>
        </div>
    </footer>
</body>
</html>
