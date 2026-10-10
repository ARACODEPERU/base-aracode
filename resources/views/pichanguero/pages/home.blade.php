@extends('pichanguero.layouts.app')

@section('title', 'Pichanguero — Organiza tu torneo de fútbol sin Excel')
@section('description', 'Pichanguero organiza tu campeonato de fútbol: fixture automático, tabla de posiciones, estadísticas de jugadores, resultados en vivo y app para Android.')

@section('content')
    @include('pichanguero.components.navbar')

    {{-- ===================== HERO ===================== --}}
    <section class="pg-surface-dark relative overflow-hidden pb-20 pt-28 sm:pt-32 lg:pb-24 lg:pt-40">
        {{-- Fondo: fotografía real de fútbol con velo azul marino --}}
        <div class="absolute inset-0 -z-10">
            <img src="{{ asset('themes/pichanguero/images/hero-bg.jpg') }}" alt=""
                 class="pg-photo opacity-25" width="1920" height="950">
            <div class="absolute inset-0 bg-gradient-to-b from-pg-navy/90 via-pg-navy/95 to-pg-navy"></div>
        </div>

        {{-- Detalle diagonal discreto, inspirado en el movimiento del juego --}}
        <div class="pg-slash left-[-6%] top-24 h-[560px] w-2 rotate-[14deg] opacity-40" aria-hidden="true"></div>
        <div class="pg-slash left-[6%] top-24 h-[560px] w-24 rotate-[14deg] opacity-[0.08]" aria-hidden="true"></div>

        <div class="pg-container relative">
            <div class="grid grid-cols-1 items-center gap-14 lg:grid-cols-[1.05fr_0.95fr]">
                <div>
                    <span class="pg-badge pg-badge-accent pg-reveal">Gestión de torneos</span>

                    <h1 class="pg-reveal mt-6 text-3xl font-extrabold leading-[1.08] text-white sm:text-5xl lg:text-[3.4rem]">
                        Tu campeonato, <span class="pg-accent">ordenado y en vivo</span>
                    </h1>

                    <p class="pg-reveal pg-lead mt-6">
                        Fixture automático, tabla de posiciones, estadísticas de jugadores y resultados al instante.
                        Pichanguero reemplaza el Excel y los grupos de WhatsApp por una plataforma hecha para el fútbol.
                    </p>

                    <div class="pg-reveal mt-9 flex flex-col gap-4 sm:flex-row">
                        <a href="{{ route('pichanguero.descargas') }}" class="pg-btn pg-btn-primary pg-btn-lg">
                            Descargar la app
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                        </a>
                        <a href="{{ route('pichanguero.contacto') }}" class="pg-btn pg-btn-ghost pg-btn-lg">
                            Solicitar demo
                        </a>
                    </div>
                </div>

                {{-- Recurso real: ilustración que la propia app distribuye en su APK --}}
                <div class="pg-reveal hidden lg:block">
                    <img src="{{ asset('themes/pichanguero/images/app-player.webp') }}"
                         alt="Ilustración de la aplicación Pichanguero"
                         class="mx-auto w-full max-w-md" width="900" height="900"
                         loading="lazy" decoding="async">
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== CAPACIDADES ===================== --}}
    <section class="pg-surface-light border-b border-pg-line-light">
        <div class="pg-container py-10 lg:py-12">
            <div class="pg-rail">
                @php
                    $strip = [
                        [
                            'title' => 'Fixture automático',
                            'text' => 'Fechas y cruces listos',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                        ],
                        [
                            'title' => 'Tabla y rankings',
                            'text' => 'Puntos y goleadores',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
                        ],
                        [
                            'title' => 'Resultados en vivo',
                            'text' => 'Marcador al instante',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M13 3L4 14h6l-1 7 9-11h-6l1-7z"/>',
                        ],
                        [
                            'title' => 'App Android',
                            'text' => 'Torneo en el bolsillo',
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
                        ],
                    ];
                @endphp

                @foreach($strip as $item)
                    <div class="pg-rail-item">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-pg-green" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" aria-hidden="true">
                            {!! $item['icon'] !!}
                        </svg>
                        <div>
                            <p class="pg-display text-sm font-bold text-pg-ink">{{ $item['title'] }}</p>
                            <p class="mt-1 text-xs text-pg-slate">{{ $item['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== BENEFICIOS ===================== --}}
    <section id="beneficios" class="pg-surface-light scroll-mt-24 py-16 sm:py-20 lg:py-24">
        <div class="pg-container">
            <div class="mx-auto max-w-2xl text-center">
                <span class="pg-badge pg-badge-accent">Beneficios</span>
                <h2 class="pg-reveal mt-5 text-3xl text-pg-ink sm:text-4xl">
                    Todo lo que tu torneo necesita
                </h2>
                <p class="pg-reveal mt-4 text-pg-slate">
                    Menos trabajo administrativo y más fútbol: la información del campeonato se ordena sola.
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @php
                    $benefits = [
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>',
                            'title' => 'Fixture automático',
                            'text' => 'Genera el calendario por grupos, llaves o liga sin armarlo a mano ni corregir cruces.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
                            'title' => 'Tabla de posiciones',
                            'text' => 'Puntos, diferencia de goles y desempates calculados automáticamente tras cada fecha.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
                            'title' => 'Estadísticas de jugadores',
                            'text' => 'Goleadores, asistencias, vallas invictas y MVP con rankings siempre actualizados.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
                            'title' => 'Resultados en vivo',
                            'text' => 'Publica el marcador apenas termina el partido y compártelo con toda la liga.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
                            'title' => 'Inscripciones y pagos',
                            'text' => 'Registra equipos, controla cupos y lleva el estado de cada inscripción.',
                        ],
                        [
                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
                            'title' => 'App móvil',
                            'text' => 'Los equipos y jugadores siguen el torneo desde el celular con la app Pichanguero.',
                        ],
                    ];
                @endphp

                @foreach($benefits as $benefit)
                    <div class="pg-card pg-reveal">
                        <div class="pg-icon-box">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                {!! $benefit['icon'] !!}
                            </svg>
                        </div>
                        <h3 class="mt-5 text-lg text-pg-ink">{{ $benefit['title'] }}</h3>
                        <p class="mt-2 leading-relaxed text-pg-slate">{{ $benefit['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== CÓMO FUNCIONA ===================== --}}
    <section id="como-funciona" class="pg-surface-dark scroll-mt-24 py-16 sm:py-20 lg:py-24">
        <div class="pg-container">
            <div class="grid grid-cols-1 items-center gap-14 lg:grid-cols-2">
                <div>
                    <span class="pg-badge pg-badge-accent">Cómo funciona</span>
                    <h2 class="pg-reveal mt-5 text-3xl text-white sm:text-4xl">
                        De la idea al fixture en cuatro pasos
                    </h2>
                    <p class="pg-reveal mt-4 text-pg-muted">
                        Sin configuraciones complicadas: creas el torneo una vez y la plataforma se encarga del resto.
                    </p>

                    <ol class="mt-10 space-y-6">
                        @php
                            $steps = [
                                ['title' => 'Crea tu torneo', 'text' => 'Define nombre, formato y reglas del campeonato.'],
                                ['title' => 'Inscribe a los equipos', 'text' => 'Registra clubes, planteles y responsables.'],
                                ['title' => 'Genera el fixture', 'text' => 'La plataforma arma las fechas y los cruces.'],
                                ['title' => 'Publica los resultados', 'text' => 'Carga los marcadores y la tabla se actualiza sola.'],
                            ];
                        @endphp

                        @foreach($steps as $index => $step)
                            <li class="pg-reveal flex gap-4">
                                <span class="pg-step-number">{{ $index + 1 }}</span>
                                <div>
                                    <p class="pg-display font-bold text-white">{{ $step['title'] }}</p>
                                    <p class="mt-1 text-sm text-pg-muted">{{ $step['text'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                {{-- Recursos reales: fotografía de fútbol + ilustración de la app --}}
                <div class="pg-reveal relative">
                    <div class="overflow-hidden rounded-2xl border border-pg-line">
                        <img src="{{ asset('themes/pichanguero/images/bg-page.jpg') }}"
                             alt="Fotografía de un partido de fútbol"
                             class="pg-photo" width="1920" height="558" loading="lazy" decoding="async">
                    </div>

                    <div class="absolute -bottom-6 left-6 flex items-center gap-3 rounded-2xl border border-pg-line bg-pg-navy2 p-3 pr-5">
                        <img src="{{ asset('themes/pichanguero/images/app-keeper.webp') }}"
                             alt="Ilustración de la aplicación Pichanguero"
                             class="h-12 w-12" width="600" height="600" loading="lazy" decoding="async">
                        <div>
                            <p class="pg-display text-sm font-bold text-white">App Android</p>
                            <a href="{{ route('pichanguero.descargas') }}" class="pg-link text-xs">Ver descarga</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== PARA QUIÉN ===================== --}}
    <section class="pg-surface-light py-16 sm:py-20 lg:py-24">
        <div class="pg-container">
            <div class="mx-auto max-w-2xl text-center">
                <span class="pg-badge pg-badge-accent">Hecho para</span>
                <h2 class="pg-reveal mt-5 text-3xl text-pg-ink sm:text-4xl">
                    ¿Para quién es Pichanguero?
                </h2>
                <p class="pg-reveal mt-4 text-pg-slate">
                    Desde una liga barrial hasta academias que manejan varias categorías durante el año.
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
                @php
                    $audiences = [
                        ['title' => 'Ligas y campeonatos', 'text' => 'Organiza fechas, sanciones y posiciones de toda la temporada con reglas claras.'],
                        ['title' => 'Academias y clubes', 'text' => 'Lleva el control de tus categorías, planteles y estadísticas de cada jugador.'],
                        ['title' => 'Organizadores independientes', 'text' => 'Monta torneos relámpago o de fin de semana y comparte la información al instante.'],
                    ];
                @endphp

                @foreach($audiences as $audience)
                    <div class="pg-card pg-reveal">
                        <h3 class="text-lg text-pg-ink">{{ $audience['title'] }}</h3>
                        <p class="mt-2 leading-relaxed text-pg-slate">{{ $audience['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== DESCARGA ===================== --}}
    <section class="pg-surface-dark relative overflow-hidden py-16 lg:py-20">
        <div class="absolute inset-0 -z-10">
            <img src="{{ asset('themes/pichanguero/images/bg-page.jpg') }}" alt=""
                 class="pg-photo opacity-20" width="1920" height="558" loading="lazy" decoding="async">
            <div class="absolute inset-0 bg-gradient-to-r from-pg-navy via-pg-navy/90 to-pg-navy/70"></div>
        </div>

        <div class="pg-container">
            <div class="flex flex-col items-start justify-between gap-8 lg:flex-row lg:items-center">
                <div>
                    <span class="pg-badge pg-badge-accent">App Android</span>
                    <h2 class="pg-reveal mt-5 text-2xl font-extrabold text-white sm:text-3xl">
                        Lleva el torneo en el bolsillo
                    </h2>
                    <p class="pg-reveal mt-3 max-w-xl text-pg-muted">
                        Descarga la app y consulta el fixture, los resultados y las estadísticas desde tu celular.
                    </p>
                </div>
                <div class="flex shrink-0 flex-col gap-3 sm:flex-row">
                    <a href="{{ route('pichanguero.descargas') }}" class="pg-btn pg-btn-primary pg-btn-lg">
                        Descargar Pichanguero
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </a>
                    {{-- Descarga directa del APK: misma accion que ofrecia la version anterior --}}
                    <a href="{{ asset('downloads/pichanguero.apk') }}" class="pg-btn pg-btn-ghost pg-btn-lg" download>
                        Descargar APK
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== PREGUNTAS FRECUENTES ===================== --}}
    <section class="pg-surface-light py-16 sm:py-20 lg:py-24">
        <div class="pg-container">
            <div class="mx-auto max-w-3xl">
                <div class="text-center">
                    <span class="pg-badge pg-badge-accent">Preguntas frecuentes</span>
                    <h2 class="pg-reveal mt-5 text-3xl text-pg-ink sm:text-4xl">Dudas habituales</h2>
                </div>

                <div class="mt-10 space-y-4">
                    @php
                        $faqs = [
                            [
                                'q' => '¿Necesito instalar algo en la computadora?',
                                'a' => 'No. La gestión del torneo funciona desde el navegador y la app permite consultar la información desde el celular.',
                            ],
                            [
                                'q' => '¿Los jugadores también deben descargar la app?',
                                'a' => 'Es opcional. La app les da acceso al fixture, sus estadísticas y los resultados, pero la información pública del torneo también se puede compartir por enlace.',
                            ],
                            [
                                'q' => '¿Sirve para alquiler de canchas?',
                                'a' => 'Sí. Además de campeonatos, Pichanguero se usa para gestionar reservas y adelantos de alquiler de local.',
                            ],
                            [
                                'q' => '¿Cuánto cuesta?',
                                'a' => 'Depende del tamaño del torneo y de los módulos que necesites. Escríbenos y te pasamos los planes según tu caso.',
                            ],
                        ];
                    @endphp

                    @foreach($faqs as $faq)
                        <details class="pg-faq pg-reveal">
                            <summary>{{ $faq['q'] }}</summary>
                            <p class="mt-4 leading-relaxed">{{ $faq['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== CTA FINAL ===================== --}}
    <section class="pg-surface-dark pb-20 pt-4 lg:pb-24">
        <div class="pg-container">
            <div class="pg-diagonal-edge relative overflow-hidden rounded-3xl border border-pg-line bg-gradient-to-br from-pg-navy2 to-pg-navy3 p-10 text-center lg:p-14">
                <div class="pg-slash right-[-4%] top-[-30%] h-[420px] w-2 rotate-[14deg] opacity-40" aria-hidden="true"></div>

                <h2 class="relative text-3xl font-extrabold text-white sm:text-4xl">
                    ¿Listo para ordenar tu torneo?
                </h2>
                <p class="relative mx-auto mt-4 max-w-2xl text-pg-muted">
                    Cuéntanos cuántos equipos manejas y te mostramos Pichanguero funcionando con tu campeonato.
                </p>
                <div class="relative mt-8 flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ route('pichanguero.contacto') }}" class="pg-btn pg-btn-primary pg-btn-lg">
                        Solicitar demo
                    </a>
                    <a href="{{ route('pichanguero.descargas') }}" class="pg-btn pg-btn-ghost pg-btn-lg">
                        Descargar la app
                    </a>
                </div>
            </div>
        </div>
    </section>

    @include('pichanguero.components.footer')
@endsection
