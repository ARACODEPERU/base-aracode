{{--
    Cabecera del aula de Secundaria: identidad del colegio, nivel del alumno,
    su nombre y el cierre de sesión. Mantiene el mismo lenguaje visual que el
    resto de las vistas juveniles.
--}}
<header class="sec-topbar">
    <a href="{{ route('colegio.secundaria.dashboard') }}" class="sec-brand" aria-label="Ir al inicio de mi aula">
        <span class="sec-brand__logo">
            @if ($portal['school']['logo_url'])
                <img src="{{ $portal['school']['logo_url'] }}" alt="{{ $portal['school']['name'] }}" class="h-full w-full object-cover">
            @else
                <span>{{ $portal['school']['initial'] }}</span>
            @endif
        </span>
        <span class="sec-brand__copy">
            <span class="sec-brand__eyebrow">Aula Virtual · Secundaria</span>
            <span class="sec-brand__name">{{ $portal['school']['name'] }}</span>
        </span>
    </a>

    <div class="sec-topbar__chips" aria-label="Datos del estudiante">
        <span class="sec-pill sec-pill--level">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.5 20 8l-8 4.5L4 8z"/>
                <path stroke-linecap="round" d="M6.5 10.4v4.8c0 1.7 2.5 3.3 5.5 3.3s5.5-1.6 5.5-3.3v-4.8"/>
            </svg>
            {{ $portal['grade']['level'] }} · {{ $portal['grade']['label'] }}
        </span>
        @if ($portal['grade']['year'])
            <span class="sec-pill sec-pill--soft">Año {{ $portal['grade']['year'] }}</span>
        @endif
    </div>

    <div class="sec-topbar__actions">
        <span class="sec-avatar" aria-hidden="true">
            @if ($portal['student']['avatar_url'])
                <img src="{{ $portal['student']['avatar_url'] }}" alt="">
            @else
                {{ mb_strtoupper(mb_substr($portal['student']['name'], 0, 1)) }}
            @endif
        </span>

        <span class="sec-topbar__user">
            <span class="sec-topbar__user-name">{{ $portal['student']['name'] }}</span>
            <span class="sec-topbar__user-meta">Estudiante</span>
        </span>

        <form method="POST" action="{{ route('colegio.secundaria.logout') }}">
            @csrf
            <button type="submit" class="sec-icon-btn" aria-label="Cerrar sesión" title="Cerrar sesión">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 8l4 4-4 4M14 12H3"/>
                </svg>
            </button>
        </form>
    </div>
</header>
