{{--
    Cabecera del sitio KAPTA LMS.

    Empieza transparente sobre la banda azul marino de cada página y, al
    desplazar, pasa a blanco con borde y sombra (clase is-scrolled, la pone
    el JS). Los enlaces y el hueco del logotipo cambian de color solos porque
    leen las variables de superficie de .ka-nav.
--}}
@php
    $navLinks = [
        ['label' => 'Funcionalidades', 'href' => route('kapta.home') . '#funcionalidades', 'route' => null],
        ['label' => 'Soluciones', 'href' => route('kapta.home') . '#soluciones', 'route' => null],
        ['label' => 'Planes', 'href' => route('kapta.planes'), 'route' => 'kapta.planes'],
        ['label' => 'Preguntas frecuentes', 'href' => route('kapta.home') . '#faq', 'route' => null],
    ];
    $campusUrl = config('kapta.campus_url');
    $demoHref = config('kapta.contacto.whatsapp_url') . '?text=' . urlencode(config('kapta.mensajes.demo_whatsapp'));
@endphp

<nav class="ka-nav" id="kaNav" aria-label="Navegación principal">
    <div class="ka-container">
        <div class="ka-nav-inner">
            {{-- Marca --}}
            <a href="{{ route('kapta.home') }}" class="ka-brand" aria-label="KAPTA LMS, ir al inicio">
                @include('kapta.components.brand', ['variant' => 'dark', 'toggle' => true])
            </a>

            {{-- Navegación de escritorio --}}
            <div class="hidden items-center gap-7 lg:flex">
                @foreach ($navLinks as $link)
                    @php $active = $link['route'] && request()->routeIs($link['route']); @endphp
                    <a href="{{ $link['href'] }}"
                       class="ka-nav-link {{ $active ? 'is-active' : '' }}"
                       @if ($active) aria-current="page" @endif>{{ $link['label'] }}</a>
                @endforeach
            </div>

            {{-- Acciones: tema, enlaces de escritorio y menú móvil.
                 El interruptor de tema va siempre visible (también en móvil); los
                 enlaces solo en escritorio, porque en móvil viven en el menú. --}}
            <div class="flex items-center gap-2">
                @include('kapta.components.theme-switch')

                <div class="hidden items-center gap-4 lg:flex">
                    @if ($campusUrl)
                        <a href="{{ $campusUrl }}" target="_blank" rel="noopener" class="ka-nav-link">
                            Campus virtual
                        </a>
                    @endif
                    <a href="{{ $demoHref }}" target="_blank" rel="noopener" class="ka-btn ka-btn-primary ka-btn-sm">
                        Solicitar una demo
                    </a>
                </div>

                {{-- Botón del menú móvil --}}
                <button type="button" class="ka-nav-toggle lg:hidden" id="kaMenuBtn"
                        aria-label="Abrir menú" aria-controls="kaMobileMenu" aria-expanded="false">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</nav>

{{-- Menú móvil --}}
<div class="ka-mobile-menu ka-surface-dark" id="kaMobileMenu" aria-hidden="true">
    <div class="ka-container py-5">
        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('kapta.home') }}" class="ka-brand" aria-label="KAPTA LMS, ir al inicio">
                @include('kapta.components.brand', ['variant' => 'dark'])
            </a>
            <div class="flex items-center gap-2">
                {{-- El interruptor se repite aquí porque el panel tapa la cabecera --}}
                @include('kapta.components.theme-switch')

                <button type="button" class="ka-nav-toggle" id="kaMenuClose" aria-label="Cerrar menú">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <nav class="mt-8" aria-label="Secciones del sitio">
            @foreach ($navLinks as $link)
                @php $active = $link['route'] && request()->routeIs($link['route']); @endphp
                <a href="{{ $link['href'] }}"
                   class="ka-mobile-link {{ $active ? 'is-active' : '' }}"
                   @if ($active) aria-current="page" @endif>
                    {{ $link['label'] }}
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endforeach
            <a href="{{ route('kapta.contacto') }}" class="ka-mobile-link">Contacto
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @if ($campusUrl)
                <a href="{{ $campusUrl }}" target="_blank" rel="noopener" class="ka-mobile-link">Campus virtual
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            @endif
        </nav>

        <a href="{{ $demoHref }}" target="_blank" rel="noopener" class="ka-btn ka-btn-primary ka-btn-block mt-8">
            Solicitar una demo
        </a>
    </div>
</div>
