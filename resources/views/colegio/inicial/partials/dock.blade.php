{{--
    Dock inferior flotante: Inicio, Cursos, Juegos y Ayuda. El elemento activo
    lo decide cada vista con $dockActive.
--}}
<nav class="aula-dock" aria-label="Navegación del aula">
    <a href="{{ route('colegio.inicial.dashboard') }}"
       class="aula-dock__item {{ ($dockActive ?? '') === 'inicio' ? 'is-active' : '' }}"
       @if (($dockActive ?? '') === 'inicio') aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 10.6 12 4l8.5 6.6V19a1.4 1.4 0 0 1-1.4 1.4H4.9A1.4 1.4 0 0 1 3.5 19z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.6 20.4v-6h4.8v6"/>
        </svg>
        Inicio
    </a>

    <a href="{{ route('colegio.inicial.dashboard') }}#cursos"
       class="aula-dock__item {{ ($dockActive ?? '') === 'cursos' ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <rect x="3.5" y="3.5" width="7" height="7" rx="2.2"/>
            <rect x="13.5" y="3.5" width="7" height="7" rx="2.2"/>
            <rect x="3.5" y="13.5" width="7" height="7" rx="2.2"/>
            <rect x="13.5" y="13.5" width="7" height="7" rx="2.2"/>
        </svg>
        Cursos
    </a>

    <a href="{{ route('colegio.inicial.games') }}"
       class="aula-dock__item {{ ($dockActive ?? '') === 'juegos' ? 'is-active' : '' }}"
       @if (($dockActive ?? '') === 'juegos') aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.4 7.5h9.2a5 5 0 0 1 4.9 4l.5 3.4a3 3 0 0 1-5.4 2.3l-1.2-1.7H8.6l-1.2 1.7a3 3 0 0 1-5.4-2.3l.5-3.4a5 5 0 0 1 4.9-4z"/>
            <path stroke-linecap="round" d="M7.6 11v3M6.1 12.5h3M16 11.4h.01M18 13.6h.01"/>
        </svg>
        Juegos
    </a>

    <button type="button" class="aula-dock__item" data-help-open aria-haspopup="dialog">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="8.6"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.6 9.4a2.5 2.5 0 1 1 3.7 2.2c-.8.5-1.3 1-1.3 1.9"/>
            <path stroke-linecap="round" d="M12 17h.01"/>
        </svg>
        Ayuda
    </button>
</nav>
