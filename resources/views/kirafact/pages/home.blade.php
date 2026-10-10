@extends('kirafact.layouts.app')

@section('title', 'KiraFact — Facturación electrónica y gestión comercial')
@section('description', 'KiraFact emite facturas, boletas y notas electrónicas con integración directa a SUNAT, control de inventario y reportes de ventas.')

@section('content')
    @include('kirafact.components.navbar')

    {{-- ===================== HERO ===================== --}}
    <section class="relative overflow-hidden pt-32 pb-24">
        <div class="absolute inset-0 -z-10">
            <img src="{{ asset('themes/kirafact/images/bg-hero.webp') }}" alt=""
                 class="h-full w-full object-cover opacity-25">
            <div class="absolute inset-0 bg-gradient-to-b from-kf-dark/85 via-kf-dark/90 to-kf-dark"></div>
        </div>

        <div class="kf-container relative">
            <div class="max-w-3xl">
                <span class="kf-badge kf-badge-amber kf-reveal">Cumplimiento SUNAT</span>

                <h1 class="kf-reveal mt-6 text-4xl font-extrabold leading-tight text-white sm:text-5xl lg:text-6xl">
                    Emite, envía y <span class="kf-gradient-text">controla tus comprobantes</span>
                </h1>

                <p class="kf-reveal mt-6 text-lg leading-relaxed text-slate-300">
                    KiraFact es la plataforma de facturación electrónica y gestión comercial: facturas, boletas,
                    notas, inventario y reportes, todo conectado con SUNAT.
                </p>

                <div class="kf-reveal mt-9 flex flex-col gap-4 sm:flex-row">
                    <a href="{{ route('kirafact.contacto') }}" class="kf-btn kf-btn-primary kf-btn-lg">
                        Solicitar asesoría
                    </a>
                    <a href="{{ route('kirafact.planes') }}" class="kf-btn kf-btn-ghost kf-btn-lg">
                        Ver planes
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== TIRA ===================== --}}
    <section class="border-y border-kf-line bg-kf-panel/50">
        <div class="kf-container py-8">
            <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
                @php
                    $strip = [
                        ['title' => 'Comprobantes', 'text' => 'Facturas y boletas'],
                        ['title' => 'SUNAT', 'text' => 'Envío automático'],
                        ['title' => 'Inventario', 'text' => 'Stock y kardex'],
                        ['title' => 'Reportes', 'text' => 'Ventas al día'],
                    ];
                @endphp

                @foreach($strip as $item)
                    <div>
                        <p class="text-sm font-semibold text-white">{{ $item['title'] }}</p>
                        <p class="mt-1 text-xs text-kf-muted">{{ $item['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== CARACTERÍSTICAS ===================== --}}
    <section id="caracteristicas" class="scroll-mt-24 py-20 lg:py-28">
        <div class="kf-container">
            <div class="mx-auto max-w-3xl text-center">
                <span class="kf-badge kf-badge-teal">Características</span>
                <h2 class="mt-5 text-3xl font-extrabold text-white sm:text-4xl">
                    Todo lo que necesitas para facturar
                </h2>
                <p class="mt-4 text-kf-muted">
                    Un sistema completo, seguro y en cumplimiento con la normativa vigente.
                </p>
            </div>

            <div class="mt-14 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @php
                    $features = [
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>',
                            'title' => 'Comprobantes electrónicos',
                            'text' => 'Facturas, boletas, notas de crédito y débito emitidas en formato electrónico.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>',
                            'title' => 'Integración SUNAT',
                            'text' => 'Validación y envío automático de comprobantes, con control de estados y rechazos.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
                            'title' => 'Panel comercial',
                            'text' => 'Ventas, compras, inventario y kardex desde un mismo dashboard.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>',
                            'title' => 'Automatización',
                            'text' => 'Generación automática de XML y PDF, validaciones y alertas inteligentes.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
                            'title' => 'Seguridad',
                            'text' => 'Cifrado de datos, respaldos y control de accesos por usuario.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
                            'title' => 'Multiusuario',
                            'text' => 'Roles y permisos para tu equipo comercial y de administración.',
                        ],
                    ];
                @endphp

                @foreach($features as $feature)
                    <div class="kf-card kf-reveal">
                        <div class="kf-icon-box">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                {!! $feature['icon'] !!}
                            </svg>
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-white">{{ $feature['title'] }}</h3>
                        <p class="mt-2 leading-relaxed text-kf-muted">{{ $feature['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== CÓMO FUNCIONA ===================== --}}
    <section id="como-funciona" class="scroll-mt-24 border-y border-kf-line bg-kf-panel/30 py-20 lg:py-28">
        <div class="kf-container">
            <div class="grid grid-cols-1 items-center gap-14 lg:grid-cols-2">
                <div>
                    <span class="kf-badge kf-badge-amber">Cómo funciona</span>
                    <h2 class="mt-5 text-3xl font-extrabold text-white sm:text-4xl">
                        De la venta al comprobante aceptado
                    </h2>
                    <p class="mt-4 text-kf-muted">
                        Configura tu empresa una vez y emite comprobantes válidos desde el primer día.
                    </p>

                    <ol class="mt-10 space-y-6">
                        @php
                            $steps = [
                                ['title' => 'Configura tu empresa', 'text' => 'Datos del emisor, series y certificado digital.'],
                                ['title' => 'Registra la venta', 'text' => 'Productos, servicios, clientes y forma de pago.'],
                                ['title' => 'Envía a SUNAT', 'text' => 'El comprobante se valida y se envía automáticamente.'],
                                ['title' => 'Controla con reportes', 'text' => 'Consulta ventas, inventario y estados de comprobantes.'],
                            ];
                        @endphp

                        @foreach($steps as $index => $step)
                            <li class="flex gap-4">
                                <span class="kf-step-number">{{ $index + 1 }}</span>
                                <div>
                                    <p class="font-semibold text-white">{{ $step['title'] }}</p>
                                    <p class="mt-1 text-sm text-kf-muted">{{ $step['text'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="kf-reveal">
                    <img src="{{ asset('themes/kirafact/images/app-ui.png') }}" alt="Plataforma KiraFact"
                         class="w-full rounded-2xl border border-kf-line shadow-kf-glow" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== PARA QUIÉN ===================== --}}
    <section class="py-20 lg:py-28">
        <div class="kf-container">
            <div class="mx-auto max-w-3xl text-center">
                <span class="kf-badge kf-badge-teal">Hecho para</span>
                <h2 class="mt-5 text-3xl font-extrabold text-white sm:text-4xl">¿Para quién es KiraFact?</h2>
                <p class="mt-4 text-kf-muted">
                    Empresas que necesitan facturar en regla y controlar sus ventas sin complicarse.
                </p>
            </div>

            <div class="mt-14 grid grid-cols-1 gap-6 md:grid-cols-3">
                @php
                    $audiences = [
                        ['title' => 'Mypes y emprendimientos', 'text' => 'Empieza a facturar electrónicamente desde el primer mes de operación.'],
                        ['title' => 'Comercios y almacenes', 'text' => 'Punto de venta, inventario y comprobantes en un mismo flujo.'],
                        ['title' => 'Áreas administrativas', 'text' => 'Reportes de ventas y estados de comprobantes para contabilidad.'],
                    ];
                @endphp

                @foreach($audiences as $audience)
                    <div class="kf-card kf-reveal">
                        <h3 class="text-lg font-bold text-white">{{ $audience['title'] }}</h3>
                        <p class="mt-2 leading-relaxed text-kf-muted">{{ $audience['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== PLANES (RESUMEN) ===================== --}}
    <section class="relative overflow-hidden border-y border-kf-line py-16">
        <div class="absolute inset-0 -z-10">
            <img src="{{ asset('themes/kirafact/images/bg-page.jpg') }}" alt=""
                 class="h-full w-full object-cover opacity-25">
            <div class="absolute inset-0 bg-gradient-to-r from-kf-dark via-kf-dark/85 to-kf-dark/70"></div>
        </div>

        <div class="kf-container">
            <div class="flex flex-col items-start justify-between gap-8 lg:flex-row lg:items-center">
                <div>
                    <span class="kf-badge kf-badge-amber">Planes</span>
                    <h2 class="mt-5 text-2xl font-extrabold text-white sm:text-3xl">
                        Precios claros, sin sorpresas
                    </h2>
                    <p class="mt-3 max-w-xl text-kf-muted">
                        Desde S/ 35 al mes por empresa, con opciones para negocios que crecen y equipos de trabajo.
                    </p>
                </div>
                <a href="{{ route('kirafact.planes') }}" class="kf-btn kf-btn-amber kf-btn-lg shrink-0">
                    Ver planes y precios
                </a>
            </div>
        </div>
    </section>

    {{-- ===================== FAQ ===================== --}}
    <section class="py-20 lg:py-28">
        <div class="kf-container">
            <div class="mx-auto max-w-3xl">
                <div class="text-center">
                    <span class="kf-badge kf-badge-teal">Preguntas frecuentes</span>
                    <h2 class="mt-5 text-3xl font-extrabold text-white sm:text-4xl">Dudas habituales</h2>
                </div>

                <div class="mt-12 space-y-4">
                    @php
                        $faqs = [
                            [
                                'q' => '¿Necesito un certificado digital?',
                                'a' => 'Sí, para emitir comprobantes electrónicos se requiere un certificado digital. Te ayudamos a tramitarlo o puedes usar el que ya tengas.',
                            ],
                            [
                                'q' => '¿Funciona con punto de venta e inventario?',
                                'a' => 'Sí. KiraFact incluye ventas, cotizaciones, notas de venta, kardex y control de inventario según el plan.',
                            ],
                            [
                                'q' => '¿Puedo probarlo antes de contratar?',
                                'a' => 'Escríbenos y coordinamos una demostración con los datos de tu empresa para que veas el flujo completo.',
                            ],
                            [
                                'q' => '¿Qué pasa si un comprobante es rechazado?',
                                'a' => 'El sistema te muestra el estado y el motivo del rechazo, y te permite corregir y reenviar el comprobante.',
                            ],
                        ];
                    @endphp

                    @foreach($faqs as $faq)
                        <details class="kf-faq rounded-2xl border border-kf-line bg-kf-panel/60 p-6">
                            <summary>{{ $faq['q'] }}</summary>
                            <p class="mt-4 leading-relaxed text-kf-muted">{{ $faq['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== CTA FINAL ===================== --}}
    <section class="pb-24">
        <div class="kf-container">
            <div class="relative overflow-hidden rounded-3xl border border-kf-line bg-gradient-to-br from-kf-panel to-kf-panel2 p-10 text-center lg:p-14">
                <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-kf-teal/20 blur-3xl"></div>

                <h2 class="relative text-3xl font-extrabold text-white sm:text-4xl">
                    ¿Listo para facturar sin complicaciones?
                </h2>
                <p class="relative mx-auto mt-4 max-w-2xl text-kf-muted">
                    Cuéntanos cómo factura hoy tu empresa y te mostramos KiraFact con tu propio flujo de ventas.
                </p>
                <div class="relative mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ route('kirafact.contacto') }}" class="kf-btn kf-btn-primary kf-btn-lg">Solicitar asesoría</a>
                    <a href="{{ route('kirafact.planes') }}" class="kf-btn kf-btn-ghost kf-btn-lg">Ver planes</a>
                </div>
            </div>
        </div>
    </section>

    @include('kirafact.components.footer')
@endsection
