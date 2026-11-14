{{--
    Propuesta de valor: las cuatro ventajas en pestañas con panel visual
    ---------------------------------------------------------------------------
    Una sola ventaja abierta —la activa— y un panel a la derecha que cambia con
    ella (imagen + chip + contador). Antes eran cuatro párrafos seguidos y una
    foto fija: no había jerarquía y la imagen no significaba nada.

    Patrón de pestañas (ARIA): un `role="tablist"` vertical, cuatro `role="tab"`
    con una sola `aria-selected="true"`, y un único `role="tabpanel"` que todas
    controlan por `aria-controls`. El panel se etiqueta con la pestaña activa
    (`aria-labelledby`, lo actualiza aracode-evolucion.js). Las flechas, Inicio
    y Fin mueven la selección; sin JavaScript queda abierta la primera ventaja y
    las cuatro descripciones siguen en el documento.

    Los textos son los mismos de la sección anterior. Las imágenes, las del
    ecosistema: cada ventaja se asocia a algo concreto en vez de a una foto
    genérica.
--}}
@php
    $ventajas = [
        [
            'titulo' => 'IA integrada en cada solución',
            'descripcion' => 'Integramos inteligencia artificial en cada una de nuestras soluciones para automatizar procesos y optimizar decisiones.',
            'chip' => 'IA aplicada',
            'imagen' => asset('themes/webpage/images/productos/inteligencia-artificial.webp'),
            'alt' => 'Inteligencia artificial aplicada a datos de negocio',
            'icono' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
        ],
        [
            'titulo' => 'Ecosistema de productos + servicios',
            'descripcion' => 'Ofrecemos productos listos como LMS, CMS y Facturación Electrónica, junto con servicios a medida.',
            'chip' => 'Ecosistema',
            'imagen' => asset('themes/webpage/images/productos/paginas-web.webp'),
            'alt' => 'Ecosistema de productos ARACODE con plataforma web y catálogo',
            'icono' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>',
        ],
        [
            'titulo' => 'Escalabilidad y adaptabilidad',
            'descripcion' => 'Software que se adapta a tu empresa, con arquitectura modular que permite añadir funciones conforme evolucionen tus necesidades.',
            'chip' => 'Escalable',
            'imagen' => asset('themes/webpage/images/productos/desarrollo-medida.webp'),
            'alt' => 'Desarrollo de software a medida con arquitectura modular',
            'icono' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>',
        ],
        [
            'titulo' => 'Acompañamiento y soporte constante',
            'descripcion' => 'Brindamos capacitación y soporte post-implementación, con una relación cercana y profesional con cada cliente.',
            'chip' => 'Soporte cercano',
            'imagen' => asset('themes/webpage/images/misc/s6.webp'),
            'alt' => 'Acompañamiento y soporte del equipo ARACODE',
            'icono' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>',
        ],
    ];
@endphp

<section class="ara-valor" id="valor">
    {{-- Mismo container que el resto del home: max-w-7xl + px-4 sm:px-6
         lg:px-8. Así el borde de esta sección coincide con el de servicios,
         productos y el proceso. --}}
    <div class="ara-valor__envoltura max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div>
            <span class="ara-valor__ceja">Propuesta de valor</span>

            <h2 class="ara-valor__titulo">¿Qué nos hace diferentes?</h2>

            <p class="ara-valor__bajada">
                En ARACODE, ofrecemos soluciones digitales potenciadas con IA para tu empresa o institución.
            </p>

            <div class="ara-valor__lista" role="tablist" aria-orientation="vertical" aria-label="Ventajas de ARACODE">
                @foreach ($ventajas as $indice => $ventaja)
                    <button type="button"
                            class="ara-valor__tab"
                            role="tab"
                            id="valor-tab-{{ $indice + 1 }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            aria-controls="valor-panel"
                            tabindex="{{ $loop->first ? '0' : '-1' }}"
                            data-chip="{{ $ventaja['chip'] }}">
                        <span class="ara-valor__num">0{{ $indice + 1 }}</span>

                        <span class="ara-valor__icono" aria-hidden="true">{!! $ventaja['icono'] !!}</span>

                        <span class="ara-valor__tab-texto">
                            <span class="ara-valor__tab-titulo">{{ $ventaja['titulo'] }}</span>
                            <span class="ara-valor__desc">{{ $ventaja['descripcion'] }}</span>
                        </span>

                        <svg class="ara-valor__flecha" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="ara-valor__panel" id="valor-panel" role="tabpanel" aria-labelledby="valor-tab-1">
            @foreach ($ventajas as $indice => $ventaja)
                <img src="{{ $ventaja['imagen'] }}"
                     alt="{{ $ventaja['alt'] }}"
                     data-valor="{{ $indice + 1 }}"
                     class="{{ $loop->first ? 'is-activa' : '' }}"
                     @if (! $loop->first) aria-hidden="true" @endif
                     loading="lazy" decoding="async">
            @endforeach

            <div class="ara-valor__pie">
                <span class="ara-valor__chip">
                    <b id="valor-chip-num">01</b>
                    <span id="valor-chip-texto">{{ $ventajas[0]['chip'] }}</span>
                </span>
                <span class="ara-valor__contador"><span id="valor-actual">01</span> / {{ sprintf('%02d', count($ventajas)) }}</span>
            </div>
        </div>
    </div>
</section>
