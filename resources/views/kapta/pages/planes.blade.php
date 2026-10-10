@extends('kapta.layouts.app')

@section('title', 'Planes y precios | KAPTA LMS')
@section('description', 'Planes de KAPTA LMS para emprendimientos educativos, instituciones y empresas. Precios en soles con facturación mensual o anual.')

@section('content')
    @include('kapta.components.navbar')

    {{-- ===================== ENCABEZADO ===================== --}}
    <section class="relative overflow-hidden pt-32 pb-16">
        <div class="absolute inset-0 -z-10">
            <img src="{{ asset('themes/kapta/images/bg-page.webp') }}" alt=""
                 class="h-full w-full object-cover opacity-20">
            <div class="absolute inset-0 bg-gradient-to-b from-ka-dark/85 via-ka-dark/90 to-ka-dark"></div>
        </div>

        <div class="ka-container">
            <div class="mx-auto max-w-3xl text-center">
                <span class="ka-badge ka-badge-amber">Planes</span>
                <h1 class="mt-6 text-4xl font-extrabold text-white sm:text-5xl">
                    Elige el plan <span class="ka-gradient-text">ideal</span>
                </h1>
                <p class="mt-5 text-lg text-ka-muted">
                    Precios en soles con facturación mensual o anual. Si tu institución es más grande,
                    armamos un plan a medida.
                </p>
            </div>
        </div>
    </section>

    {{-- ===================== PLANES ===================== --}}
    <section class="pb-20">
        <div class="ka-container">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                @php
                    $plans = [
                        [
                            'name' => 'Emprendedor',
                            'price' => 'S/ 149',
                            'period' => '/mes',
                            'annual' => 'S/ 1490 /año',
                            'features' => [
                                'Hasta 50 estudiantes',
                                '10 cursos',
                                'Certificaciones básicas',
                                'Soporte por email',
                            ],
                            'featured' => false,
                        ],
                        [
                            'name' => 'Profesional',
                            'price' => 'S/ 299',
                            'period' => '/mes',
                            'annual' => 'S/ 2990 /año',
                            'features' => [
                                'Hasta 200 estudiantes',
                                'Cursos ilimitados',
                                'Certificaciones personalizadas',
                                'Soporte prioritario',
                                'Métricas con IA',
                                'Pasarela de pago',
                            ],
                            'featured' => true,
                        ],
                        [
                            'name' => 'Enterprise',
                            'price' => 'Personalizado',
                            'period' => '',
                            'annual' => '',
                            'features' => [
                                'Estudiantes ilimitados',
                                'Todo lo del plan Profesional',
                                'Integraciones por API',
                                'Soporte dedicado',
                                'SLA garantizado',
                                'Capacitación incluida',
                            ],
                            'featured' => false,
                        ],
                    ];
                @endphp

                @foreach($plans as $plan)
                    <div class="ka-card relative ka-reveal {{ $plan['featured'] ? 'ka-card-featured' : '' }}">
                        @if($plan['featured'])
                            <span class="ka-badge ka-badge-amber absolute -top-3 left-1/2 -translate-x-1/2">
                                Más popular
                            </span>
                        @endif

                        <h2 class="text-xl font-bold text-white">{{ $plan['name'] }}</h2>

                        <div class="mt-4">
                            <span class="text-4xl font-extrabold text-ka-cyan">{{ $plan['price'] }}</span>
                            <span class="text-ka-muted">{{ $plan['period'] }}</span>
                            @if($plan['annual'])
                                <p class="mt-1 text-sm text-ka-muted">{{ $plan['annual'] }}</p>
                            @endif
                        </div>

                        <ul class="mt-6 space-y-3">
                            @foreach($plan['features'] as $feature)
                                <li class="flex items-start gap-3 text-sm text-ka-muted">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-ka-cyan" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>

                        <a href="{{ route('kapta.contacto') }}"
                           class="ka-btn mt-8 w-full {{ $plan['featured'] ? 'ka-btn-primary' : 'ka-btn-ghost' }}">
                            {{ $plan['price'] === 'Personalizado' ? 'Contactar' : 'Comenzar' }}
                        </a>
                    </div>
                @endforeach
            </div>

            <p class="mt-10 text-center text-sm text-ka-muted">
                ¿Necesitas algo distinto? Escríbenos y armamos un plan a la medida de tu institución.
            </p>
        </div>
    </section>

    {{-- ===================== CTA ===================== --}}
    <section class="pb-24">
        <div class="ka-container">
            <div class="relative overflow-hidden rounded-3xl border border-ka-line bg-gradient-to-br from-ka-panel to-ka-panel2 p-10 text-center lg:p-14">
                <div class="pointer-events-none absolute -left-16 -bottom-16 h-48 w-48 rounded-full bg-ka-indigo/25 blur-3xl"></div>

                <h2 class="relative text-2xl font-extrabold text-white sm:text-3xl">
                    Te mostramos KAPTA con tus propios cursos
                </h2>
                <p class="relative mx-auto mt-4 max-w-2xl text-ka-muted">
                    Agenda una demo y revisa cómo quedarían tus cursos, matrículas y certificados.
                </p>
                <div class="relative mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ route('kapta.contacto') }}" class="ka-btn ka-btn-primary ka-btn-lg">Solicitar demo</a>
                    <a href="{{ route('kapta.home') }}#beneficios" class="ka-btn ka-btn-ghost ka-btn-lg">Ver beneficios</a>
                </div>
            </div>
        </div>
    </section>

    @include('kapta.components.footer')
@endsection
