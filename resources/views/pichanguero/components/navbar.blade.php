{{-- Navbar del sitio Pichanguero (propia, sin dependencias de ARACODE) --}}
@php
    // Enlaces principales: mismo destino que antes, en un solo lugar.
    $navLinks = [
        ['route' => 'pichanguero.home', 'label' => 'Inicio', 'active' => request()->routeIs('pichanguero.home'), 'hash' => ''],
        ['route' => 'pichanguero.home', 'label' => 'Beneficios', 'active' => false, 'hash' => '#beneficios'],
        ['route' => 'pichanguero.home', 'label' => 'Cómo funciona', 'active' => false, 'hash' => '#como-funciona'],
        ['route' => 'pichanguero.descargas', 'label' => 'Descargas', 'active' => request()->routeIs('pichanguero.descargas'), 'hash' => ''],
        ['route' => 'pichanguero.contacto', 'label' => 'Contacto', 'active' => request()->routeIs('pichanguero.contacto'), 'hash' => ''],
    ];
@endphp

<nav class="pg-nav pg-surface-dark" id="pgNav">
    <div class="pg-container">
        <div class="pg-nav-inner">
            {{-- Marca: logotipo del proyecto, sin deformar --}}
            <a href="{{ route('pichanguero.home') }}" class="flex shrink-0 items-center">
                <img src="{{ asset('themes/pichanguero/images/logo.svg') }}" alt="Pichanguero"
                     class="pg-brand-mark" width="260" height="48">
            </a>

            {{-- Navegación escritorio --}}
            <div class="hidden items-center gap-7 lg:flex">
                @foreach($navLinks as $link)
                    <a href="{{ route($link['route']) }}{{ $link['hash'] }}"
                       class="pg-nav-link {{ $link['active'] ? 'is-active' : '' }}"
                       @if($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
                @endforeach
            </div>

            {{-- CTA escritorio --}}
            <div class="hidden lg:block">
                <a href="{{ route('pichanguero.descargas') }}" class="pg-btn pg-btn-primary pg-btn-sm">
                    Descargar la app
                </a>
            </div>

            {{-- Botón menú móvil --}}
            <button type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-pg-line text-white transition-colors hover:border-pg-green lg:hidden"
                    id="pgMenuBtn" aria-label="Abrir menú" aria-controls="pgMobileMenu" aria-expanded="false">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>
</nav>

{{-- Menú móvil --}}
<div class="pg-mobile-menu pg-surface-dark" id="pgMobileMenu" aria-hidden="true">
    <div class="pg-container py-5">
        <div class="flex items-center justify-between">
            <a href="{{ route('pichanguero.home') }}" class="flex items-center">
                <img src="{{ asset('themes/pichanguero/images/logo.svg') }}" alt="Pichanguero"
                     class="pg-brand-mark" width="260" height="48">
            </a>
            <button type="button" class="h-10 w-10 rounded-lg border border-pg-line text-white"
                    id="pgMenuClose" aria-label="Cerrar menú">
                <svg class="mx-auto h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="mt-8" aria-label="Secciones">
            @foreach($navLinks as $link)
                <a href="{{ route($link['route']) }}{{ $link['hash'] }}"
                   class="pg-mobile-link {{ $link['active'] ? 'is-active' : '' }}"
                   @if($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <a href="{{ route('pichanguero.descargas') }}" class="pg-btn pg-btn-primary mt-8 w-full">
            Descargar la app
        </a>
    </div>
</div>
