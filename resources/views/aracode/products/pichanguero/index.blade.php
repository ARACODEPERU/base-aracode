@extends('aracode.layouts.webpage')

@section('meta_title', 'Pichanguero | Gestión de Torneos y Campeonatos | ARACODE')
@section('meta_description', 'Pichanguero — Plataforma para organizar torneos de fútbol: fixture automático, tabla de posiciones, estadísticas de jugadores, resultados en vivo y app móvil para equipos.')

@section('content')
    @include('aracode.components.v2.navbar')

    {{-- Hero --}}
    <section class="pt-32 pb-20 bg-ara-navy relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-ara-green/10 rounded-full filter blur-3xl pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="ara-badge ara-badge-green mb-6 inline-block reveal">Gestión deportiva</span>
                    <h1 class="text-4xl sm:text-5xl font-bold text-white mb-6 reveal reveal-delay-1">
                        Pichan<span class="text-gradient">guero</span>
                    </h1>
                    <p class="text-lg text-white/70 mb-8 reveal reveal-delay-2">
                        La plataforma para organizar campeonatos de fútbol sin papeles ni Excel: fixture automático, tabla de posiciones, estadísticas y resultados en tiempo real.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 reveal reveal-delay-3">
                        <a href="{{ route('contacto') }}" class="ara-btn ara-btn-primary ara-btn-lg">
                            Solicitar Demo
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                        <a href="{{ asset('downloads/pichanguero.apk') }}" class="ara-btn ara-btn-secondary ara-btn-lg">
                            Descargar App
                        </a>
                    </div>
                </div>
                <div class="hidden lg:block reveal reveal-delay-4">
                    <img src="{{ asset('themes/webpage/images/misc/s4.webp') }}" alt="Pichanguero" class="rounded-2xl shadow-2xl w-full" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    {{-- Beneficios --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Beneficios"
                title="Todo para tu campeonato en un solo lugar"
                subtitle="Organiza, difunde y sigue cada torneo en vivo. Menos trabajo administrativo y más fútbol."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @php
                    $benefits = [
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>',
                            'title' => 'Fixture automático',
                            'description' => 'Genera el calendario de partidos por grupos, llaves o liga, sin armarlo a mano.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>',
                            'title' => 'Tabla de posiciones',
                            'description' => 'Puntos, diferencia de goles y desempates calculados automáticamente tras cada fecha.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                            'title' => 'Estadísticas de jugadores',
                            'description' => 'Goleadores, asistencias, vallas invictas y MVP en un ranking siempre actualizado.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
                            'title' => 'Resultados en vivo',
                            'description' => 'Publica el marcador al instante y compártelo con la landing pública del torneo.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>',
                            'title' => 'Inscripciones y pagos',
                            'description' => 'Registra equipos, controla cupos y lleva el estado de inscripción y adelantos.',
                        ],
                        [
                            'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
                            'title' => 'App móvil',
                            'description' => 'Tus equipos y jugadores siguen el torneo desde el celular con la app Pichanguero.',
                        ],
                    ];
                @endphp

                @foreach($benefits as $index => $benefit)
                    <div class="ara-card reveal reveal-delay-{{ $index + 1 }}">
                        <div class="ara-icon-box ara-icon-box-green mb-4">
                            {!! $benefit['icon'] !!}
                        </div>
                        <h3 class="text-xl font-bold text-ara-slate-700 mb-3">{{ $benefit['title'] }}</h3>
                        <p class="text-ara-slate-400 leading-relaxed">{{ $benefit['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Para quién --}}
    <section class="py-20 lg:py-28 bg-ara-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-v2.section-heading
                badge="Hecho para"
                title="¿Para quién es Pichanguero?"
                subtitle="Desde una liga barrial hasta academias y organizadores que manejan varios campeonatos al año."
                :light="false"
            />

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @php
                    $audiences = [
                        [
                            'title' => 'Ligas y campeonatos',
                            'description' => 'Organiza fechas, sanciones y posiciones de toda la temporada con reglas claras.',
                        ],
                        [
                            'title' => 'Academias y clubes',
                            'description' => 'Lleva el control de tus categorías, planteles y estadísticas de cada jugador.',
                        ],
                        [
                            'title' => 'Organizadores independientes',
                            'description' => 'Monta torneos relámpago o de fin de semana y comparte la información al instante.',
                        ],
                    ];
                @endphp

                @foreach($audiences as $index => $audience)
                    <div class="ara-card reveal reveal-delay-{{ $index + 1 }}">
                        <h3 class="text-xl font-bold text-ara-slate-700 mb-3">{{ $audience['title'] }}</h3>
                        <p class="text-ara-slate-400 leading-relaxed">{{ $audience['description'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Descarga de la app --}}
            <div class="mt-12 ara-card bg-ara-navy text-center reveal">
                <h3 class="text-2xl font-bold text-white mb-3">Lleva el torneo en el bolsillo</h3>
                <p class="text-white/70 mb-6">Descarga la app y consulta el fixture, resultados y estadísticas desde tu celular.</p>
                <a href="{{ asset('downloads/pichanguero.apk') }}" class="ara-btn ara-btn-primary ara-btn-lg">
                    Descargar Pichanguero
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <x-v2.cta-section
        title="¿Listo para ordenar tu torneo?"
        subtitle="Cuéntanos cuántos equipos manejas y te mostramos Pichanguero funcionando con tu campeonato."
        buttonText="Solicitar Demo"
    />

    @include('aracode.components.v2.footer')
@endsection
