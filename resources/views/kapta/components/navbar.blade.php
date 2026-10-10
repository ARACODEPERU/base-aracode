{{-- Navbar del sitio KAPTA --}}
<nav class="ka-nav" id="kaNav">
    <div class="ka-container">
        <div class="ka-nav-inner">
            {{-- Logo --}}
            <a href="{{ route('kapta.home') }}" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-ka-indigo to-ka-violet shadow-lg shadow-ka-indigo/30">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 4l9 4.5-9 4.5-9-4.5L12 4z" fill="#fff"/>
                        <path d="M6.5 10.7V16c0 1.7 2.5 3 5.5 3s5.5-1.3 5.5-3v-5.3" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="text-lg font-extrabold tracking-tight text-white">
                    KAPTA <span class="ka-gradient-text">LMS</span>
                </span>
            </a>

            {{-- Navegación escritorio --}}
            <div class="hidden lg:flex items-center gap-7">
                <a href="{{ route('kapta.home') }}"
                   class="ka-nav-link {{ request()->routeIs('kapta.home') ? 'is-active' : '' }}">Inicio</a>
                <a href="{{ route('kapta.home') }}#beneficios" class="ka-nav-link">Beneficios</a>
                <a href="{{ route('kapta.home') }}#como-funciona" class="ka-nav-link">Cómo funciona</a>
                <a href="{{ route('kapta.planes') }}"
                   class="ka-nav-link {{ request()->routeIs('kapta.planes') ? 'is-active' : '' }}">Planes</a>
                <a href="{{ route('kapta.contacto') }}"
                   class="ka-nav-link {{ request()->routeIs('kapta.contacto') ? 'is-active' : '' }}">Contacto</a>
            </div>

            {{-- CTA escritorio --}}
            <div class="hidden lg:block">
                <a href="{{ route('kapta.contacto') }}" class="ka-btn ka-btn-primary ka-btn-sm">
                    Solicitar demo
                </a>
            </div>

            {{-- Botón menú móvil --}}
            <button type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-ka-line text-slate-200 lg:hidden"
                    id="kaMenuBtn" aria-label="Abrir menú" aria-controls="kaMobileMenu" aria-expanded="false">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>
</nav>

{{-- Menú móvil --}}
<div class="ka-mobile-menu" id="kaMobileMenu" aria-hidden="true">
    <div class="ka-container py-5">
        <div class="flex items-center justify-between">
            <a href="{{ route('kapta.home') }}" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-ka-indigo to-ka-violet">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 4l9 4.5-9 4.5-9-4.5L12 4z" fill="#fff"/>
                    </svg>
                </span>
                <span class="text-lg font-extrabold text-white">KAPTA <span class="ka-gradient-text">LMS</span></span>
            </a>
            <button type="button" class="h-10 w-10 rounded-lg border border-ka-line text-slate-200"
                    id="kaMenuClose" aria-label="Cerrar menú">
                <svg class="mx-auto h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="mt-8">
            <a href="{{ route('kapta.home') }}" class="ka-mobile-link">Inicio</a>
            <a href="{{ route('kapta.home') }}#beneficios" class="ka-mobile-link">Beneficios</a>
            <a href="{{ route('kapta.home') }}#como-funciona" class="ka-mobile-link">Cómo funciona</a>
            <a href="{{ route('kapta.planes') }}" class="ka-mobile-link">Planes</a>
            <a href="{{ route('kapta.contacto') }}" class="ka-mobile-link">Contacto</a>
        </nav>

        <a href="{{ route('kapta.contacto') }}" class="ka-btn ka-btn-primary mt-8 w-full">
            Solicitar demo
        </a>
    </div>
</div>
