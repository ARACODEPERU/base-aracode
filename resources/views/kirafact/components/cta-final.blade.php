{{--
    Llamada a la acción final.

    Fondo azul marino con acentos azules. La acción principal es real (contacto
    comercial con canales verificados); el acceso al sistema se ofrece en segundo
    lugar y reutiliza la URL configurada.

    No se anuncia prueba gratuita, descuento, garantía ni oferta temporal.
--}}
@php
    $cta = config('kirafact.cta');
@endphp

<section class="kf-surface-dark relative overflow-hidden py-16 sm:py-20 lg:py-24" id="contacto-cta">
    <div class="kf-grid-lines absolute inset-0" aria-hidden="true"></div>
    <div class="kf-halo -bottom-40 left-1/2 h-[26rem] w-[26rem] -translate-x-1/2" aria-hidden="true"></div>

    <div class="kf-container relative">
        <div class="mx-auto max-w-3xl text-center kf-reveal">
            <h2 class="kf-h2">{{ $cta['titulo'] }}</h2>
            <p class="kf-lead kf-copy mx-auto mt-5">{{ $cta['texto'] }}</p>

            <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row sm:flex-wrap">
                <a href="{{ route('kirafact.contacto') }}" class="kf-btn kf-btn-primary kf-btn-lg">
                    <span>{{ $cta['boton'] }}</span>
                    @include('kirafact.components.icon', ['icono' => 'flecha'])
                </a>
                @include('kirafact.components.btn-ingresar', ['class' => 'kf-btn-secondary kf-btn-lg', 'texto' => $cta['boton_secundario']])
            </div>

            @unless (config('kirafact.login_url'))
                <p class="kf-slate mx-auto mt-4 max-w-md text-xs leading-relaxed">
                    {{ config('kirafact.mensajes.acceso_pendiente') }}
                </p>
            @endunless
        </div>
    </div>
</section>
