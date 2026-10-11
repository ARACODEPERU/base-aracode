{{--
    Llamada a la acción final.

    Fondo azul marino con acentos azules. Dos acciones, las dos reales: la
    principal lleva al contacto comercial (canales verificados) y la secundaria
    pide directamente la demo por WhatsApp.

    No se anuncia prueba gratuita, descuento, garantía ni oferta temporal.
--}}
@php
    $cta = config('kirafact.cta');
@endphp

<section class="kf-surface-dark relative overflow-hidden py-16 sm:py-20 lg:py-24" id="contacto-cta">
    <div class="kf-grid-lines absolute inset-0" aria-hidden="true"></div>
    {{-- El halo respira mientras se recorre el cierre (animación ligada al scroll) --}}
    <div class="kf-halo kf-halo-cta -bottom-40 left-1/2 h-[26rem] w-[26rem] -translate-x-1/2" aria-hidden="true"></div>

    <div class="kf-container relative">
        <div class="mx-auto max-w-3xl text-center kf-reveal">
            <h2 class="kf-h2">{{ $cta['titulo'] }}</h2>
            <p class="kf-lead kf-copy mx-auto mt-5">{{ $cta['texto'] }}</p>

            <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row sm:flex-wrap">
                <a href="{{ route('kirafact.contacto') }}" class="kf-btn kf-btn-primary kf-btn-lg">
                    <span>{{ $cta['boton'] }}</span>
                    @include('kirafact.components.icon', ['icono' => 'flecha'])
                </a>
                @include('kirafact.components.btn-demo', ['class' => 'kf-btn-secondary kf-btn-lg'])
            </div>
        </div>
    </div>
</section>
