{{-- Navbar del sitio KiraFact --}}
<nav class="kf-nav" id="kfNav">
    <div class="kf-container">
        <div class="kf-nav-inner">
            {{-- Logo --}}
            <a href="{{ route('kirafact.home') }}" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-kf-teal to-kf-emerald shadow-lg shadow-kf-teal/30">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="5" y="3" width="14" height="18" rx="2.5" stroke="#062018" stroke-width="1.8"/>
                        <path d="M9 8h6M9 12h4" stroke="#062018" stroke-width="1.6" stroke-linecap="round"/>
                        <path d="M9.5 16.2l1.7 1.7 3.3-3.4" stroke="#062018" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="text-lg font-extrabold tracking-tight text-white">
                    Kira<span class="kf-gradient-text">Fact</span>
                </span>
            </a>

            {{-- Navegación escritorio --}}
            <div class="hidden lg:flex items-center gap-7">
                <a href="{{ route('kirafact.home') }}"
                   class="kf-nav-link {{ request()->routeIs('kirafact.home') ? 'is-active' : '' }}">Inicio</a>
                <a href="{{ route('kirafact.home') }}#caracteristicas" class="kf-nav-link">Características</a>
                <a href="{{ route('kirafact.home') }}#como-funciona" class="kf-nav-link">Cómo funciona</a>
                <a href="{{ route('kirafact.planes') }}"
                   class="kf-nav-link {{ request()->routeIs('kirafact.planes') ? 'is-active' : '' }}">Planes</a>
                <a href="{{ route('kirafact.contacto') }}"
                   class="kf-nav-link {{ request()->routeIs('kirafact.contacto') ? 'is-active' : '' }}">Contacto</a>
            </div>

            {{-- CTA escritorio --}}
            <div class="hidden lg:block">
                <a href="{{ route('kirafact.contacto') }}" class="kf-btn kf-btn-primary kf-btn-sm">
                    Solicitar asesoría
                </a>
            </div>

            {{-- Botón menú móvil --}}
            <button type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-kf-line text-slate-200 lg:hidden"
                    id="kfMenuBtn" aria-label="Abrir menú" aria-controls="kfMobileMenu" aria-expanded="false">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>
</nav>

{{-- Menú móvil --}}
<div class="kf-mobile-menu" id="kfMobileMenu" aria-hidden="true">
    <div class="kf-container py-5">
        <div class="flex items-center justify-between">
            <a href="{{ route('kirafact.home') }}" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-kf-teal to-kf-emerald">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="5" y="3" width="14" height="18" rx="2.5" stroke="#062018" stroke-width="1.8"/>
                        <path d="M9.5 16.2l1.7 1.7 3.3-3.4" stroke="#062018" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="text-lg font-extrabold text-white">Kira<span class="kf-gradient-text">Fact</span></span>
            </a>
            <button type="button" class="h-10 w-10 rounded-lg border border-kf-line text-slate-200"
                    id="kfMenuClose" aria-label="Cerrar menú">
                <svg class="mx-auto h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="mt-8">
            <a href="{{ route('kirafact.home') }}" class="kf-mobile-link">Inicio</a>
            <a href="{{ route('kirafact.home') }}#caracteristicas" class="kf-mobile-link">Características</a>
            <a href="{{ route('kirafact.home') }}#como-funciona" class="kf-mobile-link">Cómo funciona</a>
            <a href="{{ route('kirafact.planes') }}" class="kf-mobile-link">Planes</a>
            <a href="{{ route('kirafact.contacto') }}" class="kf-mobile-link">Contacto</a>
        </nav>

        <a href="{{ route('kirafact.contacto') }}" class="kf-btn kf-btn-primary mt-8 w-full">
            Solicitar asesoría
        </a>
    </div>
</div>
