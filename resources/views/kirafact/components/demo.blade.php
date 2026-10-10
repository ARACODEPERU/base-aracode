{{--
    Solicitud de demo.

    Ocupa el lugar que antes tenía el acceso de clientes: esta web no tiene
    portal, así que en vez de ofrecer un ingreso que no existe se ofrece lo que
    sí se puede hacer hoy, que es ver el sistema funcionando.

    No promete prueba gratuita, descuento ni plazo de respuesta: describe la
    demo y deja el correo real como alternativa al botón de WhatsApp.
--}}
@php
    $demo = config('kirafact.demo_seccion');
    $contacto = config('kirafact.contacto');
@endphp

<section id="demo" class="kf-surface-light">
    <div class="kf-container kf-section-tight">
        <div class="kf-card kf-reveal items-center gap-8 text-center lg:flex-row lg:justify-between lg:text-left">
            <div>
                <h2 class="kf-h2">{{ $demo['titulo'] }}</h2>
                <p class="kf-lead kf-copy mt-3">{{ $demo['texto'] }}</p>
            </div>

            <div class="shrink-0">
                @include('kirafact.components.btn-demo', ['class' => 'kf-btn-primary kf-btn-lg'])

                <p class="kf-slate mt-4 max-w-sm text-xs leading-relaxed">
                    {{ $demo['nota'] }}
                    <a href="mailto:{{ $contacto['email'] }}" class="kf-link">{{ $contacto['email'] }}</a>
                </p>
            </div>
        </div>
    </div>
</section>
