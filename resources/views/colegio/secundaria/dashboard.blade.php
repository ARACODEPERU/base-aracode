@extends('colegio.layouts.secundaria')

@section('title', 'Mi aula · Secundaria · ' . $portal['school']['name'])
@section('description', 'Aula virtual de Secundaria de ' . $portal['school']['name'] . ': tus cursos, unidades y avance.')

@php
    $completed = $portal['progress']['completed'];
    $percent = $portal['progress']['percent'];
    $totalActivities = $portal['progress']['total'];

    $milestones = [
        [
            'title' => 'Primer paso',
            'hint' => 'Entrega tu primera actividad',
            'done' => $completed >= 1,
        ],
        [
            'title' => 'En ritmo',
            'hint' => 'Completa 5 actividades',
            'done' => $completed >= 5,
        ],
        [
            'title' => 'Explorador',
            'hint' => 'Llega al 50% de tu aula',
            'done' => $percent >= 50,
        ],
    ];
@endphp

@section('content')
    <div class="sec2">
        <div class="sec2-aurora" aria-hidden="true">
            <span class="sec2-aurora__orb sec2-aurora__orb--purple"></span>
            <span class="sec2-aurora__orb sec2-aurora__orb--cyan"></span>
            <span class="sec2-aurora__orb sec2-aurora__orb--coral"></span>
        </div>

        @include('colegio.secundaria.partials.topbar')

        <main class="sec-main">
            {{-- Bienvenida y progreso general --}}
            <section class="sec-hero" aria-labelledby="secHeroTitle">
                <div class="sec-hero__copy">
                    <span class="sec-eyebrow">
                        <span class="sec-eyebrow__dot"></span>
                        Aula virtual · Secundaria
                    </span>

                    <h1 id="secHeroTitle" class="sec-hero__title">
                        ¡Hola, {{ $portal['student']['name'] }}!
                        <span aria-hidden="true">👋</span>
                    </h1>

                    <p class="sec-hero__subtitle">
                        Tus cursos, tus retos y tu progreso en un solo lugar. Elige una materia y sigue avanzando.
                    </p>

                    <div class="sec-hero__chips">
                        <span class="sec-chip sec-chip--glass">{{ $portal['grade']['label'] }}</span>
                        @if ($portal['grade']['year'])
                            <span class="sec-chip sec-chip--glass">Año {{ $portal['grade']['year'] }}</span>
                        @endif
                        <span class="sec-chip sec-chip--glass">{{ $portal['grade']['level'] }} · {{ $portal['grade']['level_ages'] }}</span>
                    </div>

                    <div class="sec-hero__progress">
                        <div class="sec-hero__progress-head">
                            <span>Progreso general</span>
                            <strong>{{ $percent }}%</strong>
                        </div>
                        <div class="sec-hero__bar" role="img" aria-label="Progreso general del aula: {{ $percent }}%">
                            <span style="width: {{ $percent }}%"></span>
                        </div>
                        <p class="sec-hero__progress-note">
                            @if ($totalActivities > 0)
                                {{ $completed }} de {{ $totalActivities }} {{ $totalActivities === 1 ? 'actividad entregada' : 'actividades entregadas' }}.
                            @else
                                Tu docente está preparando las actividades de este periodo.
                            @endif
                        </p>
                    </div>

                    <div class="sec-hero__stats">
                        <div class="sec-hero__stat">
                            <strong>{{ $portal['stats']['courses'] }}</strong>
                            <span>{{ $portal['stats']['courses'] === 1 ? 'Curso' : 'Cursos' }}</span>
                        </div>
                        <div class="sec-hero__stat">
                            <strong>{{ $portal['stats']['units'] }}</strong>
                            <span>{{ $portal['stats']['units'] === 1 ? 'Unidad' : 'Unidades' }}</span>
                        </div>
                        <div class="sec-hero__stat">
                            <strong>{{ $portal['stats']['sessions'] }}</strong>
                            <span>{{ $portal['stats']['sessions'] === 1 ? 'Sesión' : 'Sesiones' }}</span>
                        </div>
                    </div>
                </div>

                <div class="sec-hero__art">
                    @include('colegio.secundaria.partials.hero-character')
                </div>
            </section>

            <div class="sec-layout">
                {{-- Cursos --}}
                <section id="cursos" aria-labelledby="secCoursesTitle">
                    <div class="sec-section-head">
                        <div>
                            <span class="sec-kicker">Tu ruta de hoy</span>
                            <h2 id="secCoursesTitle" class="sec-section-title">Mis cursos</h2>
                        </div>
                        <span class="sec-section-count">
                            {{ $portal['stats']['courses'] }} {{ $portal['stats']['courses'] === 1 ? 'materia' : 'materias' }}
                        </span>
                    </div>

                    @if ($courses->isEmpty())
                        <div class="sec-empty">
                            <span class="sec-empty__icon" aria-hidden="true">
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A1.5 1.5 0 0 1 5.5 4H10l2 2h6.5A1.5 1.5 0 0 1 20 7.5v11A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5z"/>
                                </svg>
                            </span>
                            <h3>Tu docente está preparando tus cursos</h3>
                            <p>Cuando los publique aparecerán aquí con sus unidades, sesiones y materiales.</p>
                        </div>
                    @endif

                    <div class="sec-courses">
                        @foreach ($courses as $course)
                            <a href="{{ route('colegio.secundaria.course', $course->id) }}"
                               class="sec-course sec-course--{{ $course->aula['sec_theme'] }}">
                                <div class="sec-course__top">
                                    @include('colegio.secundaria.partials.course-emblem', ['theme' => $course->aula['sec_theme']])
                                    <span class="sec-course__units">
                                        {{ $course->aula['units'] }} {{ $course->aula['units'] === 1 ? 'unidad' : 'unidades' }}
                                    </span>
                                </div>

                                <h3 class="sec-course__name">{{ $course->aula['name'] }}</h3>

                                <p class="sec-course__meta">
                                    @if ($course->aula['teacher'])
                                        Docente: {{ $course->aula['teacher'] }}
                                    @else
                                        {{ $course->aula['area'] }}
                                    @endif
                                </p>

                                <div class="sec-course__progress">
                                    <div class="sec-course__progress-head">
                                        <span>Tu avance</span>
                                        <strong>{{ $course->aula['percent'] }}%</strong>
                                    </div>
                                    <div class="sec-bar" role="img" aria-label="Avance en {{ $course->aula['name'] }}: {{ $course->aula['percent'] }}%">
                                        <span style="width: {{ $course->aula['percent'] }}%"></span>
                                    </div>
                                    <small>
                                        @if ($course->aula['activities_total'] > 0)
                                            {{ $course->aula['completed'] }} de {{ $course->aula['activities_total'] }} actividades
                                        @else
                                            Contenido listo para explorar
                                        @endif
                                    </small>
                                </div>

                                <div class="sec-course__footer">
                                    <span>{{ $course->aula['sessions'] }} {{ $course->aula['sessions'] === 1 ? 'sesión' : 'sesiones' }}</span>
                                    <span class="sec-course__cta">
                                        Abrir curso
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h12M12 6l6 6-6 6"/>
                                        </svg>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>

                {{-- Panel lateral --}}
                <aside class="sec-side">
                    <section class="sec-side__hero">
                        @include('colegio.secundaria.partials.explorer-character')
                        <div>
                            <span class="sec-kicker sec-kicker--light">Modo explorador</span>
                            <h2 class="sec-side__title">Cada curso es una misión</h2>
                            <p class="sec-side__text">
                                Entra, revisa el material y entrega tus actividades. Tu avance se actualiza solo.
                            </p>
                        </div>
                    </section>

                    <section class="sec-panel" aria-labelledby="secProgressTitle">
                        <div class="sec-panel__head">
                            <h2 id="secProgressTitle" class="sec-panel__title">Tu avance</h2>
                            <span class="sec-panel__percent">{{ $percent }}%</span>
                        </div>

                        <div class="sec-bar sec-bar--panel" role="img" aria-label="Progreso general: {{ $percent }}%">
                            <span style="width: {{ $percent }}%"></span>
                        </div>

                        <ul class="sec-milestones">
                            @foreach ($milestones as $milestone)
                                <li class="sec-milestone {{ $milestone['done'] ? 'is-done' : '' }}">
                                    <span class="sec-milestone__icon" aria-hidden="true">
                                        @if ($milestone['done'])
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 6.5"/>
                                            </svg>
                                        @else
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                                <path stroke-linecap="round" d="M12 17v-5M12 8.2v.1"/>
                                                <circle cx="12" cy="12" r="9"/>
                                            </svg>
                                        @endif
                                    </span>
                                    <span class="sec-milestone__copy">
                                        <strong>{{ $milestone['title'] }}</strong>
                                        <small>{{ $milestone['hint'] }}</small>
                                    </span>
                                    <span class="sec-milestone__state">{{ $milestone['done'] ? 'Logrado' : 'Pendiente' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>

                    <section class="sec-tip">
                        <span class="sec-tip__icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.2a6 6 0 0 0-3.4 10.9V17h6.8v-2.9A6 6 0 0 0 12 3.2z"/>
                                <path stroke-linecap="round" d="M9.6 20h4.8"/>
                            </svg>
                        </span>
                        <div>
                            <strong>Ritmo propio</strong>
                            <p>Estudia en bloques cortos, revisa una sesión por vez y anota tus dudas para preguntarlas en clase.</p>
                        </div>
                    </section>
                </aside>
            </div>
        </main>

        <footer class="sec-footer">
            <span>{{ $portal['school']['name'] }} · Aula Virtual</span>
            <span>{{ $portal['grade']['level'] }} · {{ $portal['grade']['year'] ?? now()->year }}</span>
        </footer>
    </div>
@endsection
