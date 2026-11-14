{{--
    Cómo trabajamos: los cuatro pasos del proceso (solo portada)
    ---------------------------------------------------------------------------
    Reemplaza la franja de cifras. Los números (100+, 500+, 5+, 99%) ya están
    en el hero de la misma página, así que repetirlos aquí no agregaba nada:
    en su lugar se explica qué pasa cuando alguien escribe, que es la duda que
    el visitante todavía tiene al llegar a este punto.

    Los pasos son una lista ordenada (`<ol>`): el orden es parte del mensaje.
    Cada paso lleva una etiqueta con lo que se entrega, no un plazo: los
    tiempos dependen del proyecto y no se prometen desde el home.

    La acción es un solo botón con `data-asesoria`, igual que el resto del
    sitio: el modal de asesoría lo intercepta y abre el formulario completo.
    Sin JavaScript sigue siendo un enlace a /contacto, así que nunca queda
    un botón muerto.
--}}
@php
    $pasos = [
        [
            'titulo' => 'Diagnóstico',
            'texto' => 'Revisamos tus procesos y detectamos dónde se pierde tiempo o dinero.',
            'entrega' => 'Procesos mapeados',
        ],
        [
            'titulo' => 'Propuesta',
            'texto' => 'Alcance, entregables y precio por escrito. Sin sorpresas ni extras ocultos.',
            'entrega' => 'Propuesta cerrada',
        ],
        [
            'titulo' => 'Implementación',
            'texto' => 'Entregamos por etapas y probamos con tu equipo antes de cada cierre.',
            'entrega' => 'Entregas por etapas',
        ],
        [
            'titulo' => 'Acompañamiento',
            'texto' => 'Capacitación, soporte y mejoras continuas después de la puesta en marcha.',
            'entrega' => 'Soporte continuo',
        ],
    ];
@endphp

<section class="ara-proceso" id="proceso">
    {{-- Mismo container que el resto del home: max-w-7xl + px-4 sm:px-6
         lg:px-8. Los pasos quedan alineados con las tarjetas de servicios y
         de productos. --}}
    <div class="ara-proceso__envoltura max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <span class="ara-proceso__ceja">Cómo trabajamos</span>

        <h2 class="ara-proceso__titulo">De la conversación al sistema en marcha</h2>

        <p class="ara-proceso__bajada">
            Cuatro pasos, siempre los mismos. Sabes qué recibes en cada uno antes de firmar nada.
        </p>

        <ol class="ara-proceso__pasos">
            @foreach ($pasos as $indice => $paso)
                <li class="ara-proceso__paso">
                    <span class="ara-proceso__num" aria-hidden="true">0{{ $indice + 1 }}</span>

                    <h3 class="ara-proceso__paso-titulo">{{ $paso['titulo'] }}</h3>

                    <p class="ara-proceso__paso-texto">{{ $paso['texto'] }}</p>

                    <span class="ara-proceso__chip">{{ $paso['entrega'] }}</span>
                </li>
            @endforeach
        </ol>

        <div class="ara-proceso__cierre">
            <p class="ara-proceso__cierre-texto">
                <strong>¿Empezamos por el diagnóstico?</strong> Te decimos qué automatizar primero.
            </p>

            <a href="{{ route('contacto') }}" data-asesoria class="ara-btn ara-btn-primary ara-proceso__btn">
                Solicitar asesoría
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>
