{{-- Navbar del sitio Pichanguero (propia, sin dependencias de ARACODE) --}}
@php
    // La app Android aún no está publicada: mientras `app_disponible` sea false
    // la cabecera no ofrece la página de descargas y su acción principal pasa a
    // ser crear cuenta en la plataforma de torneos.
    $appDisponible = (bool) config('pichanguero.app_disponible', false);
    $registroUrl = config('pichanguero.registro_url');

    // Enlaces principales de la propuesta A: solo las secciones de la portada.
    // «Contacto» no va aquí: se llega con el botón «Solicitar demo» y desde el
    // pie, así la cabecera queda más corta y con los dos CTA como protagonistas.
    $navLinks = [
        ['route' => 'pichanguero.home', 'label' => 'Producto', 'active' => false, 'hash' => '#producto'],
        ['route' => 'pichanguero.home', 'label' => 'Cómo funciona', 'active' => false, 'hash' => '#como-funciona'],
        ['route' => 'pichanguero.home', 'label' => 'Para quién', 'active' => false, 'hash' => '#para-quien'],
        ['route' => 'pichanguero.home', 'label' => 'Preguntas', 'active' => false, 'hash' => '#preguntas'],
    ];

    if ($appDisponible) {
        $navLinks[] = ['route' => 'pichanguero.descargas', 'label' => 'Descargas', 'active' => request()->routeIs('pichanguero.descargas'), 'hash' => ''];
    }
@endphp

<nav class="pg-nav pg-surface-dark" id="pgNav">
    {{-- Cinta informativa de la publicidad; se retira al hacer scroll (CSS). --}}
    <div class="pg-cinta">
        <div class="pg-container pg-cinta-inner">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Torneos de fútbol y más</span>
            <span class="pg-cinta-sep" aria-hidden="true">·</span>
            <span class="pg-cinta-secondary">Gestiona tu campeonato desde el navegador, sin instalar nada</span>
        </div>
    </div>

    <div class="pg-container">
        <div class="pg-nav-inner">
            {{-- Marca: logotipo del proyecto, sin deformar --}}
            <a href="{{ route('pichanguero.home') }}" class="flex shrink-0 items-center">
                {{-- <img src="{{ asset('themes/pichanguero/images/logo.svg') }}" alt="Pichanguero"
                     class="pg-brand-mark" width="260" height="48"> --}}
                <img src="{{ asset('themes/pichanguero/images/Logo-vertical.png') }}" alt="Pichanguero"
                     class="pg-brand-mark" width="260" height="48">
            </a>

            {{-- Navegación escritorio --}}
            <div class="hidden items-center gap-6 xl:flex">
                @foreach($navLinks as $link)
                    <a href="{{ route($link['route']) }}{{ $link['hash'] }}"
                       class="pg-nav-link {{ $link['active'] ? 'is-active' : '' }}"
                       @if($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
                @endforeach
            </div>

            {{-- Acciones: tema, CTA de escritorio y menú móvil.
                 El interruptor de tema va siempre visible (también en móvil); el
                 CTA solo en escritorio, porque en móvil se ofrece dentro del menú. --}}
            <div class="flex items-center gap-2">
                @include('pichanguero.components.theme-switch')

                <div class="hidden items-center gap-2 xl:flex">
                    <a href="{{ route('pichanguero.contacto') }}" class="pg-btn pg-btn-ghost pg-btn-sm">
                        Solicitar demo
                    </a>

                    @if ($appDisponible)
                        <a href="{{ route('pichanguero.descargas') }}" class="pg-btn pg-btn-primary pg-btn-sm">
                            Descargar la app
                        </a>
                    @else
                        <a href="{{ $registroUrl }}" class="pg-btn pg-btn-primary pg-btn-sm">
                            Crear cuenta
                        </a>
                    @endif
                </div>

                {{-- Botón menú móvil --}}
                <button type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-pg-line text-white transition-colors hover:border-pg-green xl:hidden"
                        id="pgMenuBtn" aria-label="Abrir menú" aria-controls="pgMobileMenu" aria-expanded="false">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</nav>

{{-- Menú móvil --}}
<div class="pg-mobile-menu pg-surface-dark" id="pgMobileMenu" aria-hidden="true">
    <div class="pg-container py-5">
        <div class="flex items-center justify-between">
            <a href="{{ route('pichanguero.home') }}" class="flex items-center">
                <img src="{{ asset('themes/pichanguero/images/Logo-vertical.png') }}" alt="Pichanguero"
                     class="pg-brand-mark" width="260" height="48">
            </a>
            <div class="flex items-center gap-2">
                {{-- El interruptor se repite aquí porque el panel tapa la cabecera --}}
                @include('pichanguero.components.theme-switch')

                <button type="button" class="h-10 w-10 rounded-lg border border-pg-line text-white"
                        id="pgMenuClose" aria-label="Cerrar menú">
                    <svg class="mx-auto h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <nav class="mt-8" aria-label="Secciones">
            @foreach($navLinks as $link)
                <a href="{{ route($link['route']) }}{{ $link['hash'] }}"
                   class="pg-mobile-link {{ $link['active'] ? 'is-active' : '' }}"
                   @if($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="mt-8 grid gap-3">
            <a href="{{ route('pichanguero.contacto') }}" class="pg-btn pg-btn-ghost w-full">
                Solicitar demo
            </a>

            @if ($appDisponible)
                <a href="{{ route('pichanguero.descargas') }}" class="pg-btn pg-btn-primary w-full">
                    Descargar la app
                </a>
            @else
                <a href="{{ $registroUrl }}" class="pg-btn pg-btn-primary w-full">
                    Crear cuenta
                </a>
            @endif
        </div>
    </div>
</div>
