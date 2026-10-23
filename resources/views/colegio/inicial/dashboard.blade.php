@extends('colegio.layouts.base')

@section('title', 'Mi aula de Inicial · ' . $portal['school']['name'])
@section('description', 'Aula virtual del nivel Inicial de ' . $portal['school']['name'] . ': cursos, unidades y sesiones preparados por tu profesora.')
@section('body_class', 'font-body')

@section('content')
    <div class="aula">
        @include('colegio.components.aula.scene')
        @include('colegio.inicial.partials.topbar')

        <main class="aula-main">
            {{-- Saludo y resumen del aula --}}
            <section class="aula-hero aula-pop" data-listen>
                <div>
                    <p class="aula-eyebrow">Mi aula de Inicial</p>
                    <h1 class="aula-title">
                        ¡Hola, {{ $portal['student']['name'] }}!
                        <span aria-hidden="true">👋</span>
                    </h1>
                    <p class="aula-subtitle">
                        ¿Qué quieres aprender hoy? Elige una tarjeta y descubre lo que tu profesora preparó para ti.
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="aula-chip aula-chip--mint">Inicial · 3 a 5 años</span>
                        <span class="aula-chip aula-chip--sky">{{ $portal['grade']['label'] }}</span>
                        @if ($portal['grade']['year'])
                            <span class="aula-chip aula-chip--sun">Año {{ $portal['grade']['year'] }}</span>
                        @endif
                    </div>
                </div>

                <div class="aula-hero__stats">
                    <div class="aula-stat">
                        <span class="aula-stat__value">{{ $portal['stats']['courses'] }}</span>
                        <span class="aula-stat__label">{{ $portal['stats']['courses'] === 1 ? 'Curso' : 'Cursos' }}</span>
                    </div>
                    <div class="aula-stat">
                        <span class="aula-stat__value">{{ $portal['stats']['units'] }}</span>
                        <span class="aula-stat__label">{{ $portal['stats']['units'] === 1 ? 'Unidad' : 'Unidades' }}</span>
                    </div>
                    <div class="aula-stat">
                        <span class="aula-stat__value">{{ $portal['stats']['sessions'] }}</span>
                        <span class="aula-stat__label">{{ $portal['stats']['sessions'] === 1 ? 'Sesión' : 'Sesiones' }}</span>
                    </div>
                </div>
            </section>

            <div class="aula-layout">
                {{-- Cursos --}}
                <section id="cursos" aria-labelledby="cursosTitle">
                    <h2 id="cursosTitle" class="aula-section-title">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.5a3.1 3.1 0 0 1 3.1 3.1v1.2a3.1 3.1 0 0 1-1 5.9v2.2a3.1 3.1 0 1 1-4.2 0v-2.2a3.1 3.1 0 0 1-1-5.9V6.6A3.1 3.1 0 0 1 12 3.5z"/>
                        </svg>
                        ¿Qué quieres aprender hoy?
                    </h2>
                    <p class="mt-1 text-xs font-bold text-cole-forest/55">
                        Toca una tarjeta para entrar a sus unidades y sesiones.
                    </p>

                    @if ($courses->isEmpty())
                        <div class="aula-empty">
                            <p class="font-display text-lg text-cole-forest">Tu profesora está preparando tus cursos</p>
                            <p class="mx-auto mt-1 max-w-sm text-xs font-semibold leading-relaxed text-cole-ink/60">
                                Cuando los publique aparecerán aquí, con sus unidades y sesiones.
                                Mientras tanto puedes visitar la zona de juegos.
                            </p>
                        </div>
                    @endif

                    <div class="course-grid">
                        @foreach ($courses as $course)
                            <a href="{{ route('colegio.inicial.course', $course->id) }}"
                               class="course-card course-card--{{ $course->aula['tone'] }}">
                                <span class="course-card__badge">
                                    {{ $course->aula['units'] }} {{ $course->aula['units'] === 1 ? 'unidad' : 'unidades' }}
                                </span>

                                <span class="course-card__art" aria-hidden="true">
                                    @include('colegio.components.aula.course-art', ['art' => $course->aula['theme']])
                                </span>

                                <span class="course-card__name">{{ $course->aula['name'] }}</span>
                                <span class="course-card__meta">
                                    {{ $course->aula['sessions'] }} {{ $course->aula['sessions'] === 1 ? 'sesión' : 'sesiones' }}
                                    @if ($course->aula['activities'] > 0)
                                        · {{ $course->aula['activities'] }} {{ $course->aula['activities'] === 1 ? 'actividad' : 'actividades' }}
                                    @endif
                                </span>

                                <span class="course-card__cta">
                                    Entrar
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h12M12 6l6 6-6 6"/>
                                    </svg>
                                </span>
                            </a>
                        @endforeach

                        {{-- Zona de juegos: siempre visible --}}
                        <a href="{{ route('colegio.inicial.games') }}" class="course-card course-card--green">
                            <span class="course-card__badge">Zona de juegos</span>

                            <span class="course-card__art" aria-hidden="true">
                                <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M46 22h20a10 10 0 0 1 10 10v8h8a10 10 0 0 1 10 10v20a10 10 0 0 1-10 10h-8v8a10 10 0 0 1-10 10H46a10 10 0 0 1-10-10v-8h-8a10 10 0 0 1-10-10V50a10 10 0 0 1 10-10h8v-8a10 10 0 0 1 10-10z" fill="#FFE08A" transform="rotate(-8 60 62)"/>
                                    <circle cx="52" cy="60" r="3.4" fill="#4E6B27"/>
                                    <circle cx="72" cy="60" r="3.4" fill="#4E6B27"/>
                                    <path d="M55 68c3 3.6 9 3.6 12 0" stroke="#4E6B27" stroke-width="2.6" stroke-linecap="round"/>
                                    <ellipse cx="43" cy="68" rx="4.6" ry="2.8" fill="#F79AA2" opacity=".75"/>
                                    <ellipse cx="80" cy="68" rx="4.6" ry="2.8" fill="#F79AA2" opacity=".75"/>
                                </svg>
                            </span>

                            <span class="course-card__name">Juegos</span>
                            <span class="course-card__meta">Aprende jugando</span>
                            <span class="course-card__cta">
                                Jugar
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h12M12 6l6 6-6 6"/>
                                </svg>
                            </span>
                        </a>
                    </div>
                </section>

                {{-- Mascota y datos del aula --}}
                <aside class="space-y-4">
                    @include('colegio.inicial.partials.mascot')

                    <div class="aula-info-card">
                        <h2 class="font-display text-base font-semibold text-cole-forest">Mi clase</h2>

                        <div class="mt-2">
                            <div class="aula-info-card__row">
                                <span class="aula-info-card__label">Colegio</span>
                                <span class="aula-info-card__value">{{ $portal['school']['name'] }}</span>
                            </div>
                            <div class="aula-info-card__row">
                                <span class="aula-info-card__label">Nivel</span>
                                <span class="aula-info-card__value">{{ $portal['grade']['level'] }}</span>
                            </div>
                            <div class="aula-info-card__row">
                                <span class="aula-info-card__label">Grado y aula</span>
                                <span class="aula-info-card__value">{{ $portal['grade']['label'] }}</span>
                            </div>
                            <div class="aula-info-card__row">
                                <span class="aula-info-card__label">Matrícula</span>
                                <span class="aula-info-card__value">{{ $portal['student']['code'] }}</span>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </main>

        @include('colegio.inicial.partials.dock')
        @include('colegio.inicial.partials.help-dialog')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('themes/colegio/js/vendor/anime.umd.min.js') }}"></script>
    <script src="{{ asset('themes/colegio/js/aula.js') }}"></script>
@endpush
