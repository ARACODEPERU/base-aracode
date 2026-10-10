@extends('kirafact.layouts.app')

@section('title', 'Planes y precios | KiraFact')
@section('description', 'Planes de KiraFact desde S/ 35 al mes: facturación electrónica, inventario y reportes para mypes, comercios y equipos de trabajo.')

@section('content')
    @include('kirafact.components.navbar')

    {{-- ===================== ENCABEZADO ===================== --}}
    <section class="relative overflow-hidden pt-32 pb-16">
        <div class="absolute inset-0 -z-10">
            <img src="{{ asset('themes/kirafact/images/bg-page.jpg') }}" alt=""
                 class="h-full w-full object-cover opacity-25">
            <div class="absolute inset-0 bg-gradient-to-b from-kf-dark/85 via-kf-dark/90 to-kf-dark"></div>
        </div>

        <div class="kf-container">
            <div class="mx-auto max-w-3xl text-center">
                <span class="kf-badge kf-badge-amber">Planes</span>
                <h1 class="mt-6 text-4xl font-extrabold text-white sm:text-5xl">
                    Planes flexibles para tu <span class="kf-gradient-text">empresa</span>
                </h1>
                <p class="mt-5 text-lg text-kf-muted">
                    Elige el plan según el volumen de ventas y el tamaño de tu equipo.
                </p>
            </div>
        </div>
    </section>

    {{-- ===================== PLANES ===================== --}}
    <section class="pb-20">
        <div class="kf-container">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                @php
                    $plans = [
                        [
                            'name' => 'Emprendedor',
                            'price' => 'S/ 35',
                            'period' => '/mes',
                            'annual' => 'S/ 350 /año',
                            'features' => [
                                'Módulo de ventas y compras',
                                'Control de inventario',
                                'Facturas y boletas electrónicas',
                                'Reportes básicos',
                                '2 usuarios',
                                'Soporte 24/7',
                            ],
                            'featured' => false,
                        ],
                        [
                            'name' => 'PYME',
                            'price' => 'S/ 50',
                            'period' => '/mes',
                            'annual' => 'S/ 500 /año',
                            'features' => [
                                'Todo lo del plan Emprendedor',
                                'Cotizaciones y notas de venta',
                                'Kardex y movimientos',
                                'Punto de venta',
                                '5 usuarios',
                                'Soporte prioritario',
                            ],
                            'featured' => true,
                        ],
                        [
                            'name' => 'PRO',
                            'price' => 'S/ 80',
                            'period' => '/mes',
                            'annual' => 'S/ 800 /año',
                            'features' => [
                                'Todo lo del plan PYME',
                                'Guías de remisión',
                                'Reportes avanzados',
                                '10 usuarios',
                                'Soporte dedicado',
                                'Capacitación incluida',
                            ],
                            'featured' => false,
                        ],
                    ];
                @endphp

                @foreach($plans as $plan)
                    <div class="kf-card relative kf-reveal {{ $plan['featured'] ? 'kf-card-featured' : '' }}">
                        @if($plan['featured'])
                            <span class="kf-badge kf-badge-amber absolute -top-3 left-1/2 -translate-x-1/2">
                                Más popular
                            </span>
                        @endif

                        <h2 class="text-xl font-bold text-white">{{ $plan['name'] }}</h2>

                        <div class="mt-4">
                            <span class="text-4xl font-extrabold text-kf-sky">{{ $plan['price'] }}</span>
                            <span class="text-kf-muted">{{ $plan['period'] }}</span>
                            @if($plan['annual'])
                                <p class="mt-1 text-sm text-kf-muted">{{ $plan['annual'] }}</p>
                            @endif
                        </div>

                        <ul class="mt-6 space-y-3">
                            @foreach($plan['features'] as $feature)
                                <li class="flex items-start gap-3 text-sm text-kf-muted">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-kf-teal" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <a href="{{ route('kirafact.contacto') }}"
                           class="kf-btn mt-8 w-full {{ $plan['featured'] ? 'kf-btn-primary' : 'kf-btn-ghost' }}">
                            Lo quiero
                        </a>
                    </div>
                @endforeach
            </div>

            <p class="mt-10 text-center text-sm text-kf-muted">
                Los precios no incluyen IGV. Podemos adaptar el plan si tu empresa maneja varios locales.
            </p>
        </div>
    </section>

    {{-- ===================== CTA ===================== --}}
    <section class="pb-24">
        <div class="kf-container">
            <div class="relative overflow-hidden rounded-3xl border border-kf-line bg-gradient-to-br from-kf-panel to-kf-panel2 p-10 text-center lg:p-14">
                <div class="pointer-events-none absolute -left-16 -bottom-16 h-48 w-48 rounded-full bg-kf-emerald/20 blur-3xl"></div>

                <h2 class="relative text-2xl font-extrabold text-white sm:text-3xl">
                    Te ayudamos a elegir el plan correcto
                </h2>
                <p class="relative mx-auto mt-4 max-w-2xl text-kf-muted">
                    Cuéntanos cuánto facturas al mes y te recomendamos el plan que mejor se ajusta.
                </p>
                <div class="relative mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ route('kirafact.contacto') }}" class="kf-btn kf-btn-primary kf-btn-lg">Solicitar asesoría</a>
                    <a href="{{ route('kirafact.home') }}#caracteristicas" class="kf-btn kf-btn-ghost kf-btn-lg">Ver características</a>
                </div>
            </div>
        </div>
    </section>

    @include('kirafact.components.footer')
@endsection
