{{--
    Fila de una llave de cuartos de final jugada a ida y vuelta.

    Recibe `$tie` con las dos piernas reales del cruce: `ida` (leg = 1) y
    `vuelta` (la que apunta a la ida con `tie_id`). Los equipos, la localía y
    las fechas se leen tal como están guardados: la fila solo los presenta.

    Variables: $tie['ida'] (EventEditionMatch), $tie['vuelta'] (EventEditionMatch|null)
--}}
@php
    $ida = $tie['ida'] ?? null;
    $vuelta = $tie['vuelta'] ?? null;
    // Los equipos de la fila son los de la ida; la vuelta los tiene invertidos.
    $local = $ida?->equipolocal;
    $visitante = $ida?->equipovisitante;

    $legs = [
        ['label' => 'Ida', 'match' => $ida],
        ['label' => 'Vuelta', 'match' => $vuelta],
    ];

    $statusLabels = [
        'finished' => 'Finalizado',
        'closed' => 'Finalizado',
        'no_points' => 'Jugado sin puntos',
        'live' => 'En vivo',
        'cancelled' => 'Cancelado',
    ];
@endphp
<article class="se-tie">
    <div class="se-tie__team">
        <div class="se-tie__crest">
            @if ($local && $local->logo_path)
                <img src="{{ asset('storage/' . $local->logo_path) }}" alt="{{ $local->name }}" loading="lazy">
            @elseif ($local)
                <i class="fas fa-shield-alt" aria-hidden="true"></i>
            @else
                <i class="fas fa-question" aria-hidden="true"></i>
            @endif
        </div>
        <div class="se-tie__team-info">
            <span class="se-tie__name">{{ $local?->name ?? 'Por definir' }}</span>
        </div>
    </div>

    <span class="se-tie__vs" aria-hidden="true">VS</span>

    <div class="se-tie__team se-tie__team--away">
        <div class="se-tie__team-info">
            <span class="se-tie__name">{{ $visitante?->name ?? 'Por definir' }}</span>
        </div>
        <div class="se-tie__crest">
            @if ($visitante && $visitante->logo_path)
                <img src="{{ asset('storage/' . $visitante->logo_path) }}" alt="{{ $visitante->name }}" loading="lazy">
            @elseif ($visitante)
                <i class="fas fa-shield-alt" aria-hidden="true"></i>
            @else
                <i class="fas fa-question" aria-hidden="true"></i>
            @endif
        </div>
    </div>

    @foreach ($legs as $leg)
        @php
            $match = $leg['match'];
            $status = $match?->status;
            $played = $match && in_array($status, ['finished', 'closed', 'no_points'], true);
            $statusLabel = $statusLabels[$status] ?? null;
        @endphp
        <div class="se-tie__leg {{ $leg['label'] === 'Vuelta' ? 'se-tie__leg--return' : 'se-tie__leg--first' }}">
            <span class="se-tie__leg-label">{{ $leg['label'] }}</span>

            @if ($match && $match->match_date)
                <span class="se-tie__leg-line">
                    <i class="far fa-calendar" aria-hidden="true"></i>
                    {{ \Modules\Socialevents\Support\TournamentDateLabels::shortDate($match->match_date) }}
                </span>
                <span class="se-tie__leg-line">
                    <i class="far fa-clock" aria-hidden="true"></i>
                    {{ \Modules\Socialevents\Support\TournamentDateLabels::time($match->match_date) }}
                </span>
            @else
                <span class="se-tie__leg-line se-tie__leg-line--empty">
                    <i class="far fa-calendar" aria-hidden="true"></i>
                    Fecha por definir
                </span>
            @endif

            @if ($played)
                <span class="se-tie__leg-score">{{ (int) $match->score_h }} <small>-</small> {{ (int) $match->score_a }}</span>
            @endif

            @if ($statusLabel)
                <span class="se-tie__leg-status se-tie__leg-status--{{ $status }}">{{ $statusLabel }}</span>
            @endif
        </div>
    @endforeach
</article>
