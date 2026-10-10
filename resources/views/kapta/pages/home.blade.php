@extends('kapta.layouts.app')

@section('title', 'KAPTA LMS — Enseña, evalúa y certifica en línea')
@section('description', 'KAPTA LMS gestiona tus cursos, aulas virtuales, evaluaciones, matrículas y certificados automáticos. Plataforma para instituciones, academias y empresas.')

@section('content')
    @include('kapta.components.navbar')

    {{-- ===================== HERO ===================== --}}
    <section class="relative overflow-hidden pt-32 pb-24">
        <div class="absolute inset-0 -z-10">
            <img src="{{ asset('themes/kapta/images/bg-hero.webp') }}" alt=""
                 class="h-full w-full object-cover opacity-25">
            <div class="absolute inset-0 bg-gradient-to-b from-ka-dark/85 via-ka-dark/90 to-ka-dark"></div>
        </div>

        <div class="ka-container relative">
            <div class="max-w-3xl">
                <span class="ka-badge ka-badge-amber ka-reveal">Plataforma e-learning</span>

                <h1 class="ka-reveal mt-6 text-4xl font-extrabold leading-tight text-white sm:text-5xl lg:text-6xl">
                    Enseña, evalúa y <span class="ka-gradient-text">certifica en línea</span>
                </h1>

                <p class="ka-reveal mt-6 text-lg leading-relaxed text-slate-300">
                    KAPTA LMS reúne cursos, aulas virtuales, evaluaciones, matrículas y certificados en una sola
                    plataforma. Diseñada para instituciones, academias y empresas que forman personas.
                </p>

                <div class="ka-reveal mt-9 flex flex-col gap-4 sm:flex-row">
                    <a href="{{ route('kapta.planes') }}" class="ka-btn ka-btn-primary ka-btn-lg">
                        Ver planes
                    </a>
                    <a href="{{ route('kapta.contacto') }}" class="ka-btn ka-btn-ghost ka-btn-lg">
                        Solicitar demo
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== TIRA ===================== --}}
    <section class="border-y border-ka-line bg-ka-panel/50">
        <div class="ka-container py-8">
            <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
                @php
                    $strip = [
                        ['title' => 'Cursos y aulas', 'text' => 'Contenido multimedia'],
                        ['title' => 'Evaluaciones', 'text' => 'Notas y avance'],
                        ['title' => 'Certificados', 'text' => 'Emisión automática'],
                        ['title' => 'Métricas con IA', 'text' => 'Seguimiento del alumno'],
                    ];
                @endphp

                @foreach($strip as $item)
                    <div>
                        <p class="text-sm font-semibold text-white">{{ $item['title'] }}</p>
                        <p class="mt-1 text-xs text-ka-muted">{{ $item['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== BENEFICIOS ===================== --}}
    <section id="beneficios" class="scroll-mt-24 py-20 lg:py-28">
        <div class="ka-container">
            <div class="mx-auto max-w-3xl text-center">
                <span class="ka-badge ka-badge-violet">Beneficios</span>
                <h2 class="mt-5 text-3xl font-extrabold text-white sm:text-4xl">
                    Todo el ciclo formativo en un solo lugar
                </h2>
                <p class="mt-4 text-ka-muted">
                    Desde la matrícula hasta el certificado, sin hojas de cálculo ni procesos manuales.
                </p>
            </div>

            <div class="mt-14 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @php
                    $benefits = [
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
                            'title' => 'Gestión completa',
                            'text' => 'Administra cursos, módulos, evaluaciones y certificaciones desde un solo panel.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>',
                            'title' => 'Aulas virtuales',
                            'text' => 'Videos, documentos, PDF y enlaces organizados por módulo y sesión.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>',
                            'title' => 'Certificación automática',
                            'text' => 'Genera certificados personalizados al completar el curso o la evaluación final.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
                            'title' => 'Control de matrículas',
                            'text' => 'Registra alumnos, controla cupos y sigue el avance de cada participante.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
                            'title' => 'Pasarelas de pago',
                            'text' => 'Vende cursos y certificaciones con cobros en línea y control de ingresos.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
                            'title' => 'Métricas con IA',
                            'text' => 'Paneles con progreso, notas y alertas para acompañar a cada estudiante.',
                        ],
                    ];
                @endphp

                @foreach($benefits as $benefit)
                    <div class="ka-card ka-reveal">
                        <div class="ka-icon-box">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                {!! $benefit['icon'] !!}
                            </svg>
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-white">{{ $benefit['title'] }}</h3>
                        <p class="mt-2 leading-relaxed text-ka-muted">{{ $benefit['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== CÓMO FUNCIONA ===================== --}}
    <section id="como-funciona" class="scroll-mt-24 border-y border-ka-line bg-ka-panel/30 py-20 lg:py-28">
        <div class="ka-container">
            <div class="grid grid-cols-1 items-center gap-14 lg:grid-cols-2">
                <div>
                    <span class="ka-badge ka-badge-amber">Cómo funciona</span>
                    <h2 class="mt-5 text-3xl font-extrabold text-white sm:text-4xl">
                        Tu programa formativo en cuatro pasos
                    </h2>
                    <p class="mt-4 text-ka-muted">
                        Publica el curso una vez y deja que la plataforma se encargue de matrículas, evaluaciones
                        y certificados.
                    </p>

                    <ol class="mt-10 space-y-6">
                        @php
                            $steps = [
                                ['title' => 'Crea tu curso', 'text' => 'Define módulos, sesiones y materiales.'],
                                ['title' => 'Abre la matrícula', 'text' => 'Inscribe alumnos por enlace, cupo o pago en línea.'],
                                ['title' => 'Evalúa', 'text' => 'Carga actividades, notas y seguimiento del avance.'],
                                ['title' => 'Certifica', 'text' => 'Emite el certificado automáticamente al completar el curso.'],
                            ];
                        @endphp

                        @foreach($steps as $index => $step)
                            <li class="flex gap-4">
                                <span class="ka-step-number">{{ $index + 1 }}</span>
                                <div>
                                    <p class="font-semibold text-white">{{ $step['title'] }}</p>
                                    <p class="mt-1 text-sm text-ka-muted">{{ $step['text'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="ka-reveal">
                    <img src="{{ asset('themes/kapta/images/app-ui.png') }}" alt="Plataforma KAPTA LMS"
                         class="w-full rounded-2xl border border-ka-line shadow-ka-glow" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== PARA QUIÉN ===================== --}}
    <section class="py-20 lg:py-28">
        <div class="ka-container">
            <div class="mx-auto max-w-3xl text-center">
                <span class="ka-badge ka-badge-violet">Hecho para</span>
                <h2 class="mt-5 text-3xl font-extrabold text-white sm:text-4xl">¿Para quién es KAPTA?</h2>
                <p class="mt-4 text-ka-muted">
                    Instituciones que necesitan ordenar su formación y demostrar resultados.
                </p>
            </div>

            <div class="mt-14 grid grid-cols-1 gap-6 md:grid-cols-3">
                @php
                    $audiences = [
                        ['title' => 'Institutos y academias', 'text' => 'Cursos regulares, ciclos y certificaciones con matrícula online.'],
                        ['title' => 'Áreas de capacitación', 'text' => 'Programas internos con seguimiento de avance por colaborador.'],
                        ['title' => 'Empresas y consultoras', 'text' => 'Vende formación a distancia y entrega certificados verificables.'],
                    ];
                @endphp

                @foreach($audiences as $audience)
                    <div class="ka-card ka-reveal">
                        <h3 class="text-lg font-bold text-white">{{ $audience['title'] }}</h3>
                        <p class="mt-2 leading-relaxed text-ka-muted">{{ $audience['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== PLANES (RESUMEN) ===================== --}}
    <section class="relative overflow-hidden border-y border-ka-line py-16">
        <div class="absolute inset-0 -z-10">
            <img src="{{ asset('themes/kapta/images/bg-page.webp') }}" alt=""
                 class="h-full w-full object-cover opacity-20">
            <div class="absolute inset-0 bg-gradient-to-r from-ka-dark via-ka-dark/85 to-ka-dark/70"></div>
        </div>

        <div class="ka-container">
            <div class="flex flex-col items-start justify-between gap-8 lg:flex-row lg:items-center">
                <div>
                    <span class="ka-badge ka-badge-amber">Planes</span>
                    <h2 class="mt-5 text-2xl font-extrabold text-white sm:text-3xl">
                        Planes que crecen con tu institución
                    </h2>
                    <p class="mt-3 max-w-xl text-ka-muted">
                        Desde emprendimientos educativos hasta instituciones con cientos de estudiantes.
                    </p>
                </div>
                <a href="{{ route('kapta.planes') }}" class="ka-btn ka-btn-amber ka-btn-lg shrink-0">
                    Ver planes y precios
                </a>
            </div>
        </div>
    </section>

    {{-- ===================== FAQ ===================== --}}
    <section class="py-20 lg:py-28">
        <div class="ka-container">
            <div class="mx-auto max-w-3xl">
                <div class="text-center">
                    <span class="ka-badge ka-badge-violet">Preguntas frecuentes</span>
                    <h2 class="mt-5 text-3xl font-extrabold text-white sm:text-4xl">Dudas habituales</h2>
                </div>

                <div class="mt-12 space-y-4">
                    @php
                        $faqs = [
                            [
                                'q' => '¿Puedo usar mi propio dominio y mi marca?',
                                'a' => 'Sí. La plataforma se puede personalizar con tu logo, colores y dominio para que tus alumnos la vean como tuya.',
                            ],
                            [
                                'q' => '¿Cómo se entregan los certificados?',
                                'a' => 'Se generan automáticamente al completar el curso o la evaluación final, listos para descargar y verificar.',
                            ],
                            [
                                'q' => '¿Migran los cursos que ya tengo?',
                                'a' => 'Sí. Te acompañamos en la carga inicial de cursos, materiales y alumnos según el plan contratado.',
                            ],
                            [
                                'q' => '¿Ofrecen capacitación al equipo?',
                                'a' => 'El plan Enterprise incluye capacitación; en los demás planes podemos coordinar una sesión de arranque.',
                            ],
                        ];
                    @endphp

                    @foreach($faqs as $faq)
                        <details class="ka-faq rounded-2xl border border-ka-line bg-ka-panel/60 p-6">
                            <summary>{{ $faq['q'] }}</summary>
                            <p class="mt-4 leading-relaxed text-ka-muted">{{ $faq['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== CTA FINAL ===================== --}}
    <section class="pb-24">
        <div class="ka-container">
            <div class="relative overflow-hidden rounded-3xl border border-ka-line bg-gradient-to-br from-ka-panel to-ka-panel2 p-10 text-center lg:p-14">
                <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-ka-violet/20 blur-3xl"></div>

                <h2 class="relative text-3xl font-extrabold text-white sm:text-4xl">
                    ¿Listo para digitalizar tu formación?
                </h2>
                <p class="relative mx-auto mt-4 max-w-2xl text-ka-muted">
                    Cuéntanos cuántos alumnos y cursos manejas y te mostramos KAPTA funcionando con tu programa.
                </p>
                <div class="relative mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ route('kapta.contacto') }}" class="ka-btn ka-btn-primary ka-btn-lg">Solicitar demo</a>
                    <a href="{{ route('kapta.planes') }}" class="ka-btn ka-btn-ghost ka-btn-lg">Ver planes</a>
                </div>
            </div>
        </div>
    </section>

    @include('kapta.components.footer')
@endsection
