{{--
    Barra de navegación del sitio KIRAFACT.

    Arranca transparente sobre la banda azul marino del hero y pasa a fondo
    blanco al desplazar (la clase is-scrolled la pone el JS). El color de los
    enlaces y del hueco del logotipo lo resuelven las variables de .kf-nav, así
    que no hay que duplicar estilos para los dos fondos.
--}}
@php
    $inicio = route('kirafact.home');

    $enlaces = [
        ['texto' => 'Inicio', 'url' => $inicio, 'ancla' => 'inicio', 'ruta' => 'kirafact.home'],
        ['texto' => 'Funcionalidades', 'url' => $inicio.'#funcionalidades', 'ancla' => 'funcionalidades'],
        ['texto' => 'Beneficios', 'url' => $inicio.'#beneficios', 'ancla' => 'beneficios'],
        ['texto' => 'Preguntas frecuentes', 'url' => $inicio.'#preguntas', 'ancla' => 'preguntas'],
        ['texto' => 'Contacto', 'url' => route('kirafact.contacto'), 'ruta' => 'kirafact.contacto'],
    ];
@endphp

<header class="kf-nav" id="kfNav">
    <div class="kf-container">
        <div class="kf-nav-inner">
            <a href="{{ $inicio }}" class="kf-brand" aria-label="KIRAFACT — inicio">
                @include('kirafact.components.marca', ['superficie' => 'auto'])
            </a>

            {{-- Navegación de escritorio --}}
            <nav class="hidden items-center gap-7 lg:flex" aria-label="Navegación principal">
                @foreach ($enlaces as $enlace)
                    <a href="{{ $enlace['url'] }}"
                       class="kf-nav-link {{ isset($enlace['ruta']) && request()->routeIs($enlace['ruta']) ? 'is-active' : '' }}"
                       @isset($enlace['ancla']) data-ancla="{{ $enlace['ancla'] }}" @endisset>{{ $enlace['texto'] }}</a>
                @endforeach
            </nav>

            {{-- Acciones: tema, solicitud de demo y menú móvil.
                 El interruptor de tema va siempre visible (también en móvil);
                 el botón de demo solo en escritorio, porque en móvil se ofrece
                 dentro del menú, a lo ancho. --}}
            <div class="flex items-center gap-2">
                @include('kirafact.components.tema-switch')

                <div class="hidden lg:block">
                    @include('kirafact.components.btn-demo', ['class' => 'kf-btn-primary kf-btn-sm'])
                </div>

                {{-- Botón del menú móvil --}}
                <button type="button" class="kf-nav-toggle lg:hidden" id="kfMenuBtn"
                        aria-label="Abrir menú" aria-controls="kfMobileMenu" aria-expanded="false">
                    @include('kirafact.components.icon', ['icono' => 'menu', 'iconoClase' => 'h-5 w-5'])
                </button>
            </div>
        </div>
    </div>
</header>

{{-- Menú móvil: panel azul marino a pantalla completa --}}
<div class="kf-mobile-menu" id="kfMobileMenu" aria-hidden="true">
    <div class="kf-container py-5">
        <div class="flex items-center justify-between gap-4">
            <a href="{{ $inicio }}" class="kf-brand" aria-label="KIRAFACT — inicio">
                @include('kirafact.components.marca', ['superficie' => 'oscura'])
            </a>
            <div class="flex items-center gap-2">
                {{-- El interruptor se repite aquí porque el panel tapa la cabecera --}}
                @include('kirafact.components.tema-switch')
                <button type="button" class="kf-nav-toggle" id="kfMenuClose" aria-label="Cerrar menú">
                    @include('kirafact.components.icon', ['icono' => 'cerrar', 'iconoClase' => 'h-5 w-5'])
                </button>
            </div>
        </div>

        <nav class="mt-6" aria-label="Navegación principal (móvil)">
            @foreach ($enlaces as $enlace)
                <a href="{{ $enlace['url'] }}" class="kf-mobile-link"
                   @isset($enlace['ancla']) data-ancla="{{ $enlace['ancla'] }}" @endisset>
                    <span>{{ $enlace['texto'] }}</span>
                    @include('kirafact.components.icon', ['icono' => 'flecha', 'iconoClase' => 'h-5 w-5'])
                </a>
            @endforeach
        </nav>

        <div class="mt-8">
            @include('kirafact.components.btn-demo', ['class' => 'kf-btn-primary kf-btn-block'])
        </div>
    </div>
</div>
