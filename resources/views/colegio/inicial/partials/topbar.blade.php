{{--
    Cabecera del aula de Inicial. Muestra la identidad del colegio (el mismo
    logo de la página institucional), el nivel gamificado, el botón de audio
    para escuchar la pantalla y el acceso a la cuenta del alumno.
--}}
<header class="aula-topbar">
    <a href="{{ route('colegio.inicial.dashboard') }}" class="aula-brand" aria-label="Ir al inicio del aula">
        <span class="aula-brand__logo">
            @if ($portal['school']['logo_url'])
                <img src="{{ $portal['school']['logo_url'] }}" alt="{{ $portal['school']['name'] }}" class="h-full w-full object-cover">
            @else
                <span class="font-display text-xl font-semibold leading-none">{{ $portal['school']['initial'] }}</span>
            @endif
        </span>
        <span class="hidden sm:block">
            <span class="aula-brand__eyebrow">Aula Virtual</span>
            <span class="aula-brand__name">{{ $portal['school']['name'] }}</span>
        </span>
    </a>

    <div class="aula-chips">
        <span class="aula-chip aula-chip--mint">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3M12 18v3M4.2 7.2l2.1 2.1M17.7 14.7l2.1 2.1M3 12h3M18 12h3M4.2 16.8l2.1-2.1M17.7 9.3l2.1-2.1"/>
                <circle cx="12" cy="12" r="3.2"/>
            </svg>
            Inicial · 3 a 5 años
        </span>
        <span class="aula-chip aula-chip--sun">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2.6l2.7 5.9 6.4.7-4.8 4.3 1.3 6.3-5.6-3.2-5.6 3.2 1.3-6.3L3 8.5l6.4-.7z"/>
            </svg>
            Nivel {{ $portal['progress']['level'] }}
        </span>
    </div>

    <div class="aula-topbar__actions">
        <button type="button" id="btnEscuchar" class="aula-icon-btn"
                data-listen-target="[data-listen]"
                aria-label="Escuchar esta pantalla" title="Escuchar esta pantalla">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5 6.5 9H3v6h3.5L11 19z"/>
                <path stroke-linecap="round" d="M15 9.2a4 4 0 010 5.6M17.8 6.4a8 8 0 010 11.2"/>
            </svg>
        </button>

        <button type="button" class="aula-user" data-profile-open aria-haspopup="dialog"
                aria-label="Abrir mi perfil y cambiar mi contraseña">
            <span class="aula-avatar">
                @if ($portal['student']['avatar_url'])
                    <img src="{{ $portal['student']['avatar_url'] }}" alt="{{ $portal['student']['full_name'] }}" class="h-full w-full object-cover">
                @else
                    @include('colegio.components.aula.fox', ['class' => 'h-8 w-8'])
                @endif
            </span>
            <span class="hidden md:block">
                <span class="aula-user__name">{{ $portal['student']['name'] }}</span>
                <span class="aula-user__meta">{{ $portal['grade']['label'] }}</span>
            </span>
        </button>

        <button type="button" class="aula-icon-btn" data-profile-open aria-haspopup="dialog"
                aria-label="Mi perfil" title="Mi perfil">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="8.5" cy="12" r="3.2"/>
                <path stroke-linecap="round" d="M11.7 12h8.3M17 12v3M19.5 12v2.2"/>
            </svg>
        </button>

        <form method="POST" action="{{ route('colegio.inicial.logout') }}">
            @csrf
            <button type="submit" class="aula-icon-btn" aria-label="Cerrar sesión" title="Cerrar sesión">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 4h3a2 2 0 012 2v12a2 2 0 01-2 2h-3M10 8l4 4-4 4M14 12H3"/>
                </svg>
            </button>
        </form>
    </div>
</header>

@include('colegio.inicial.partials.profile-dialog')
