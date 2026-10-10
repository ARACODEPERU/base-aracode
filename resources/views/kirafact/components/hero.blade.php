{{--
    Hero principal.

    Fondo azul marino con gradientes sutiles y rejilla decorativa. Dos columnas
    en escritorio: el mensaje comercial y la acción principal a la izquierda, la
    composición del software a la derecha.

    La composición es referencial (ver components/mock.blade.php): siempre lleva
    su rótulo, nunca se presenta como una captura real del sistema.
--}}
@php
    $hero = config('kirafact.hero');
@endphp

<section id="inicio" class="kf-surface-dark relative overflow-hidden pt-28 pb-16 sm:pt-32 lg:pt-36 lg:pb-24">
    {{-- Decoración de fondo: rejilla muy tenue y un halo azul --}}
    <div class="kf-grid-lines absolute inset-0" aria-hidden="true"></div>
    <div class="kf-halo -right-40 -top-32 h-[28rem] w-[28rem]" aria-hidden="true"></div>

    <div class="kf-container relative">
        <div class="grid items-center gap-12 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)] lg:gap-16">
            {{-- Mensaje --}}
            <div class="kf-reveal">
                <span class="kf-badge">{{ $hero['etiqueta'] }}</span>

                <h1 class="kf-h1 mt-6">
                    {{ $hero['titulo_antes'] }}
                    <span class="kf-accent">{{ $hero['titulo_marca'] }}</span>
                </h1>

                <p class="kf-lead kf-copy mt-5">{{ $hero['descripcion'] }}</p>

                <ul class="mt-8 grid gap-3">
                    @foreach ($hero['puntos'] as $punto)
                        <li class="flex items-center gap-3">
                            <span class="kf-icon-box h-9 w-9 rounded-lg">
                                @include('kirafact.components.icon', ['icono' => $punto['icono'], 'iconoClase' => 'h-4 w-4'])
                            </span>
                            <span class="text-sm font-medium">{{ $punto['texto'] }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <a href="#funcionalidades" class="kf-btn kf-btn-primary kf-btn-lg">
                        <span>{{ $hero['cta_principal'] }}</span>
                        @include('kirafact.components.icon', ['icono' => 'flecha'])
                    </a>
                    @include('kirafact.components.btn-ingresar', ['class' => 'kf-btn-secondary kf-btn-lg', 'texto' => $hero['cta_secundario']])
                </div>

                @unless (config('kirafact.login_url'))
                    {{-- Indicación discreta: el acceso todavía no está habilitado --}}
                    <p class="kf-slate mt-4 flex max-w-md items-start gap-2 text-xs leading-relaxed">
                        @include('kirafact.components.icon', ['icono' => 'aviso', 'iconoClase' => 'mt-0.5 h-4 w-4 shrink-0'])
                        <span>{{ config('kirafact.mensajes.acceso_pendiente') }}</span>
                    </p>
                @endunless
            </div>

            {{-- Composición del software --}}
            <div class="kf-reveal">
                @include('kirafact.components.mock', ['titulo' => 'KIRAFACT · Comprobantes electrónicos'])

                <p class="kf-mock-note">
                    @include('kirafact.components.icon', ['icono' => 'aviso', 'iconoClase' => 'mt-0.5 h-4 w-4 shrink-0'])
                    <span>{{ config('kirafact.software.nota') }}</span>
                </p>
            </div>
        </div>
    </div>
</section>
