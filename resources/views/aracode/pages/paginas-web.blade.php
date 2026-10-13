@extends('aracode.layouts.webpage')

@section('meta_title', 'Páginas web para tu negocio desde S/ 89 al mes | ARACODE Smart Solutions')
@section('meta_description', 'Elige una plantilla de nuestro catálogo, la adaptamos con tu marca y la publicamos con tu dominio en pocos días. Un solo plan mensual o anual: dominio, hosting y cambios incluidos.')

@php
    /*
        Página del servicio de páginas web.

        Es la otra mitad del Banco de Plantillas: el catálogo (/plantillas)
        muestra lo que se puede publicar y esta página, cómo y por cuánto. Los
        planes llegan resueltos del controlador (App\Support\ServicioWeb, sobre
        config/servicio_web.php) y los formatos del catálogo, para enlazar cada
        plan con las plantillas de ese formato. Aquí no se lee ninguna config.

        Los precios se publican en un único lugar: la tarjeta, el JSON-LD y el
        bloque de planes del catálogo salen del mismo dato, así que no pueden
        contradecirse.
    */

    // Preguntas frecuentes: el mismo arreglo pinta el acordeón y el JSON-LD
    // FAQPage, así que lo que ve el visitante y lo que lee Google no pueden
    // decir cosas distintas (es requisito del marcado FAQ).
    $preguntas = [
        [
            'p' => '¿Qué diferencia hay entre una landing y un sitio web?',
            'r' => 'La landing es una sola página: todo el contenido en secciones y enlaces internos, pensada para que quien llega escriba o llame. El sitio web son varias páginas enlazadas con menú, con una página por servicio o especialidad. Si dudas cuál te toca, mira las plantillas del catálogo: cada una dice su formato.',
        ],
        [
            'p' => '¿Cuánto demora la publicación?',
            'r' => 'La landing demora de 3 a 5 días, el sitio web de 5 a 8 y la tienda en línea de 7 a 10. El plazo se cuenta desde que nos entregas tus textos, fotos y logo: mientras antes lleguen, antes queda publicado.',
        ],
        [
            'p' => '¿El dominio y el hosting están incluidos?',
            'r' => 'Sí. El plan cubre el dominio propio (.com o .pe) del primer año, el hosting y el certificado de seguridad. El dominio se registra a tu nombre, así que es tuyo aunque decidas no renovar el plan.',
        ],
        [
            'p' => '¿Puedo pedir cambios después de publicar?',
            'r' => 'Sí: cada plan incluye una cantidad de cambios de contenido al año (precios, fotos, textos, nuevos servicios). Solo escríbenos por WhatsApp con lo que quieres cambiar y lo publicamos, sin cobro adicional mientras el plan esté vigente.',
        ],
        [
            'p' => '¿Necesito saber de diseño o de programación?',
            'r' => 'No. Tú eliges la plantilla y nos entregas tu logo, tus fotos y la información de tu negocio; del diseño, la publicación y el alta en Google nos ocupamos nosotros.',
        ],
        [
            'p' => '¿Puedo pasar de mensual a anual, o cambiar de plan?',
            'r' => 'Sí. Puedes pagar el año completo en cualquier momento (equivale a diez mensualidades) y puedes subir de landing a sitio web o a tienda pagando la diferencia. Si subes de plan, descontamos lo que ya pagaste del mes en curso.',
        ],
        [
            'p' => '¿Cómo se paga?',
            'r' => $notaPago,
        ],
    ];
@endphp

@push('json-ld')
{{-- `@@` escapa la directiva @context de Blade, igual que en el layout. --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Service",
    "name": "Páginas web a partir de plantillas",
    "serviceType": "Diseño, publicación y mantenimiento de páginas web",
    "description": "Publicación de una página web a partir de una plantilla del catálogo de ARACODE, adaptada con la marca del cliente y con su propio dominio.",
    "url": "{{ route('plantillas.paginas_web') }}",
    "inLanguage": "es-PE",
    "areaServed": {
        "@@type": "Country",
        "name": "Perú"
    },
    "provider": {
        "@@type": "Organization",
        "name": "ARACODE Smart Solutions",
        "url": "{{ url('/') }}"
    },
    "offers": [
        @foreach ($planes as $plan)
        {
            "@@type": "Offer",
            "name": "{{ $plan['nombre'] }} — pago mensual",
            "description": "{{ $plan['resumen'] }}",
            "price": {{ $plan['mensual'] }},
            "priceCurrency": "PEN",
            "url": "{{ route('plantillas.paginas_web') }}"
        },
        {
            "@@type": "Offer",
            "name": "{{ $plan['nombre'] }} — año completo",
            "description": "El año completo de {{ $plan['nombre'] }}: {{ $plan['meses_gratis'] }} meses sin cobro.",
            "price": {{ $plan['anual'] }},
            "priceCurrency": "PEN",
            "url": "{{ route('plantillas.paginas_web') }}"
        }@if (! $loop->last),@endif
        @endforeach
    ]
}
</script>

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        @foreach ($preguntas as $pregunta)
        {
            "@@type": "Question",
            "name": "{{ $pregunta['p'] }}",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ $pregunta['r'] }}"
            }
        }@if (! $loop->last),@endif
        @endforeach
    ]
}
</script>
@endpush

@section('content')
    @include('aracode.components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-blue/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="ara-badge ara-badge-blue mb-6 inline-block reveal">Servicio de ARACODE</span>

                    <h1 class="text-4xl sm:text-5xl font-bold text-white mb-6 reveal reveal-delay-1">
                        Tu página web <span class="text-gradient">publicada en días</span>
                    </h1>

                    <p class="text-lg text-white/70 mb-8 reveal reveal-delay-2">
                        Elige una plantilla de nuestro catálogo, la adaptamos con tu logo, tus textos y tus fotos,
                        y la dejamos online con tu propio dominio. Planes desde
                        {{ $precioDesde }} al mes o el año completo: sin costo de implementación
                        y sin sorpresas.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 reveal reveal-delay-3">
                        <a href="{{ route('plantillas.catalogo') }}" class="ara-btn ara-btn-primary ara-btn-lg">
                            Ver las plantillas
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                        <a href="{{ route('contacto') }}" class="ara-btn ara-btn-secondary ara-btn-lg">
                            Solicitar asesoría
                        </a>
                    </div>

                    <ul class="mt-8 space-y-2 reveal reveal-delay-4">
                        <li class="flex items-start gap-2 text-sm text-white/70">
                            <svg class="w-5 h-5 text-ara-green flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Sin costo de implementación: se paga el plan, no el proyecto
                        </li>
                        <li class="flex items-start gap-2 text-sm text-white/70">
                            <svg class="w-5 h-5 text-ara-green flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Dominio, hosting y certificado de seguridad incluidos
                        </li>
                        <li class="flex items-start gap-2 text-sm text-white/70">
                            <svg class="w-5 h-5 text-ara-green flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Publicada y con cambios de contenido incluidos
                        </li>
                    </ul>
                </div>

                <div class="hidden lg:block reveal reveal-delay-4">
                    <img src="{{ asset('themes/webpage/images/misc/s3.jpg') }}" alt="Publicación de una página web de ARACODE" class="rounded-2xl shadow-2xl w-full" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    {{-- Cómo funciona --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Cómo funciona"
                title="De la plantilla a tu dominio, en cuatro pasos"
                subtitle="Tú eliges y nos entregas tu información; del diseño, la publicación y el alta en Google nos ocupamos nosotros."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                @php
                    $pasos = [
                        [
                            'titulo' => 'Elige tu plantilla',
                            'texto' => 'Mira las demostraciones del catálogo y quédate con la que se parece a tu negocio. Cada una dice su formato: landing, sitio web o tienda.',
                        ],
                        [
                            'titulo' => 'Cuéntanos de tu negocio',
                            'texto' => 'Nos entregas logo, fotos, servicios, precios y tu número de WhatsApp. Te enviamos una lista corta de lo que necesitamos, sin formularios largos.',
                        ],
                        [
                            'titulo' => 'Adaptamos y revisas',
                            'texto' => 'Montamos la plantilla con tu marca y tu contenido y te pasamos un enlace para que la revises. Incluye una ronda de ajustes antes de publicar.',
                        ],
                        [
                            'titulo' => 'Publicamos con tu dominio',
                            'texto' => 'Queda online en tu dominio, con correo de contacto, alta en Google y soporte para lo que necesites después.',
                        ],
                    ];
                @endphp

                @foreach ($pasos as $indice => $paso)
                    <div class="ara-card reveal reveal-delay-{{ $indice + 1 }}">
                        <div class="w-12 h-12 rounded-xl bg-ara-blue/10 text-ara-blue flex items-center justify-center font-bold text-lg mb-4">
                            {{ $indice + 1 }}
                        </div>
                        <h3 class="text-lg font-bold text-ara-slate-700 mb-3">{{ $paso['titulo'] }}</h3>
                        <p class="text-ara-slate-400 leading-relaxed text-sm">{{ $paso['texto'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Planes --}}
    <section class="py-20 lg:py-28 bg-ara-slate-50" id="planes">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Planes"
                title="Una sola tarifa, todo incluido"
                subtitle="Elige el plan según el formato de la plantilla que te gustó. Puedes pagar mes a mes o el año completo."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach ($planes as $plan)
                    @php $formato = $tipos[$plan['tipo']] ?? null; @endphp

                    {{-- La tarjeta es una columna: así el botón queda al pie de
                         todas y se pueden comparar los planes de un vistazo,
                         aunque una lista de ventajas sea más larga que otra. --}}
                    <div class="ara-card p-8 h-full flex flex-col border-2 {{ $plan['destacado'] ? 'border-ara-blue relative' : 'border-transparent hover:border-ara-blue/30 transition-all' }} reveal reveal-delay-{{ $loop->index + 1 }}">
                        @if ($plan['destacado'])
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-ara-blue text-white text-xs font-semibold">
                                Más elegido
                            </div>
                        @endif

                        <div class="text-center mb-6">
                            <h3 class="text-xl font-bold text-ara-slate-700 mb-2">{{ $plan['nombre'] }}</h3>
                            @if ($formato !== null)
                                <p class="text-ara-slate-500 text-sm">
                                    {{ $formato['hashtag'] }} · {{ $formato['total'] }} {{ $formato['total'] === 1 ? 'plantilla disponible' : 'plantillas disponibles' }}
                                </p>
                            @endif
                        </div>

                        <div class="text-center mb-2">
                            <span class="text-4xl font-bold text-ara-blue">{{ $plan['precio_mensual'] }}</span>
                            <span class="text-ara-slate-500">/mes</span>
                        </div>

                        <p class="text-center text-sm text-ara-slate-500 mb-6">
                            o {{ $plan['precio_anual'] }} el año completo
                            @if ($plan['meses_gratis'] > 0)
                                <span class="text-ara-green font-semibold">({{ $plan['meses_gratis'] }} meses gratis)</span>
                            @endif
                        </p>

                        <p class="text-ara-slate-400 text-sm leading-relaxed mb-6">{{ $plan['resumen'] }}</p>

                        <ul class="space-y-3 mb-8">
                            @foreach ($plan['incluye'] as $linea)
                                <li class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    <span class="text-ara-slate-500 text-sm">{{ $linea }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <p class="text-xs text-ara-slate-400 mb-6">
                            <strong class="text-ara-slate-500">Entrega:</strong> {{ $plan['entrega'] }} ·
                            <strong class="text-ara-slate-500">Para:</strong> {{ $plan['ideal_para'] }}
                        </p>

                        <a href="{{ route('contacto') }}" class="mt-auto block text-center py-3 px-6 rounded-xl font-semibold transition-all {{ $plan['destacado'] ? 'bg-ara-blue text-white hover:bg-blue-600' : 'border-2 border-ara-blue text-ara-blue hover:bg-ara-blue hover:text-white' }}">
                            Quiero el plan {{ $plan['nombre'] }}
                        </a>
                    </div>
                @endforeach
            </div>

            {{-- Lo común a todos los planes, en un solo bloque: repetirlo en cada
                 tarjeta alargaría la comparación y escondería lo que sí cambia. --}}
            <div class="ara-card mt-12 reveal">
                <h3 class="text-xl font-bold text-ara-slate-700 mb-6">Todos los planes incluyen</h3>

                <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($incluyeSiempre as $linea)
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-ara-green mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="text-ara-slate-500 text-sm">{{ $linea }}</span>
                        </li>
                    @endforeach
                </ul>

                @if ($notaPago !== '')
                    <p class="text-sm text-ara-slate-400 mt-6 pt-6 border-t border-ara-slate-100">{{ $notaPago }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Plantillas por formato --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="El catálogo"
                title="Elige el formato y mira las plantillas"
                subtitle="Cada plan se llama igual que un formato del catálogo, así que lo que ves en la tarjeta es lo que recibes."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach ($planes as $plan)
                    @php $formato = $tipos[$plan['tipo']] ?? null; @endphp

                    <div class="ara-card reveal reveal-delay-{{ $loop->index + 1 }}">
                        <span class="ara-badge ara-badge-blue mb-4 inline-block">{{ $plan['nombre'] }}</span>

                        <h3 class="text-lg font-bold text-ara-slate-700 mb-3">
                            {{ $formato['hashtag'] ?? $plan['nombre'] }}
                        </h3>

                        <p class="text-ara-slate-400 text-sm leading-relaxed mb-6">
                            {{ $formato['descripcion'] ?? $plan['resumen'] }}
                        </p>

                        <p class="text-ara-slate-500 text-sm mb-6">
                            {{ $formato['total'] ?? 0 }}
                            {{ ($formato['total'] ?? 0) === 1 ? 'plantilla publicada' : 'plantillas publicadas' }}
                            desde <strong class="text-ara-slate-700">{{ $plan['precio_mensual'] }}</strong>/mes
                        </p>

                        <a href="{{ route('plantillas.catalogo', ['q' => $formato['hashtag'] ?? $plan['nombre']]) }}"
                           class="inline-flex items-center gap-2 text-sm font-semibold text-ara-blue hover:underline">
                            Ver las plantillas de este formato
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-12 reveal">
                <a href="{{ route('plantillas.catalogo') }}" class="ara-btn ara-btn-secondary">
                    Ver el catálogo completo
                </a>
            </div>
        </div>
    </section>

    {{-- Preguntas frecuentes --}}
    <section class="py-20 lg:py-28 bg-ara-slate-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-ara-slate-700 mb-12 text-center reveal">Preguntas sobre el servicio</h2>

            <div class="space-y-4">
                @foreach ($preguntas as $pregunta)
                    <div class="faq-item border border-ara-slate-100 rounded-xl overflow-hidden reveal reveal-delay-{{ min($loop->index, 3) }}">
                        <button class="faq-toggle w-full px-6 py-5 text-left flex items-center justify-between hover:bg-ara-slate-50 transition-colors">
                            <span class="font-semibold text-ara-slate-700">{{ $pregunta['p'] }}</span>
                            <svg class="w-5 h-5 text-ara-slate-400 transition-transform faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div class="faq-content hidden px-6 pb-5">
                            <p class="text-ara-slate-500 leading-relaxed">{{ $pregunta['r'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Cierre --}}
    <section class="ara-cta py-20 lg:py-28">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6 reveal">
                ¿Empezamos por tu página?
            </h2>

            <p class="text-lg text-white/70 mb-10 max-w-2xl mx-auto reveal reveal-delay-1">
                Cuéntanos qué hace tu negocio y te decimos qué plantilla y qué plan te tocan. Si ninguna
                encaja, también lo desarrollamos a medida.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 reveal reveal-delay-2">
                <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary ara-btn-lg">
                    Solicitar asesoría
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
                <a href="{{ route('plantillas.catalogo') }}" class="ara-btn ara-btn-secondary ara-btn-lg">
                    Ver las plantillas
                </a>
            </div>
        </div>
    </section>

    @include('aracode.components.v2.footer')

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.faq-toggle').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const content = this.nextElementSibling;
                const icon = this.querySelector('.faq-icon');
                const isOpen = !content.classList.contains('hidden');

                document.querySelectorAll('.faq-content').forEach(function(c) { c.classList.add('hidden'); });
                document.querySelectorAll('.faq-icon').forEach(function(i) { i.style.transform = 'rotate(0deg)'; });

                if (!isOpen) {
                    content.classList.remove('hidden');
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        });
    });
    </script>
@endsection
