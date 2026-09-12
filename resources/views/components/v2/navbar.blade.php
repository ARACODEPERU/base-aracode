{{-- Navbar V2 - ARACODE --}}
<nav class="ara-nav" id="navbar">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            {{-- Logo --}}
            <a href="{{ route('index_main') }}" class="flex items-center gap-2">
                <img src="{{ asset('themes/webpage/images/logo.png') }}" alt="ARACODE" class="h-10">
            </a>

            {{-- Desktop Navigation --}}
            <div class="hidden lg:flex items-center gap-1">
                <a href="{{ route('index_main') }}" class="nav-link">Inicio</a>
                <a href="{{ route('soluciones') }}" class="nav-link">Soluciones</a>
                <a href="{{ route('empresa') }}" class="nav-link">Empresa</a>
                <a href="{{ route('blog_principal') }}" class="nav-link">Blog</a>
                <a href="{{ route('contacto') }}" class="nav-link">Contacto</a>
            </div>

            {{-- CTA & Theme Toggle --}}
            <div class="hidden lg:flex items-center gap-3">
                {{-- Dark Mode Toggle --}}
                <button class="ara-theme-toggle" id="theme-toggle" aria-label="Cambiar tema" title="Cambiar modo día/noche">
                    {{-- Moon Icon (shown in light mode) --}}
                    <svg class="icon-moon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    {{-- Sun Icon (shown in dark mode) --}}
                    <svg class="icon-sun" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>

                <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary ara-btn-sm">
                    Solicitar Asesoría
                </a>
            </div>

            {{-- Mobile Menu Button --}}
            <div class="flex lg:hidden items-center gap-2">
                <button class="ara-theme-toggle" id="theme-toggle-mobile" aria-label="Cambiar tema">
                    <svg class="icon-moon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg class="icon-sun" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>
                <button class="ara-menu-btn p-2 text-white" aria-label="Menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</nav>

{{-- Mobile Menu --}}
<div class="ara-mobile-menu">
    <button class="ara-menu-close absolute top-6 right-6 text-white p-2" aria-label="Close menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
    <a href="{{ route('index_main') }}" class="nav-link">Inicio</a>
    <a href="{{ route('soluciones') }}" class="nav-link">Soluciones</a>
    <a href="{{ route('empresa') }}" class="nav-link">Empresa</a>
    <a href="{{ route('blog_principal') }}" class="nav-link">Blog</a>
    <a href="{{ route('contacto') }}" class="nav-link">Contacto</a>
    <div class="mt-8">
        <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary w-full">
            Solicitar Asesoría
        </a>
    </div>
</div>
