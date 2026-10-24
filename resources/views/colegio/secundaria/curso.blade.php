@extends('colegio.layouts.secundaria')

@section('title', $course->aula['name'] . ' · Aula Secundaria · ' . $portal['school']['name'])
@section('description', 'Unidades, sesiones, materiales y actividades del curso ' . $course->aula['name'] . ' de Secundaria.')

@php
    $sessionNumber = 0;
@endphp

@section('content')
    <div class="sec2 sec2--course">
        <div class="sec2-aurora" aria-hidden="true">
            <span class="sec2-aurora__orb sec2-aurora__orb--purple"></span>
            <span class="sec2-aurora__orb sec2-aurora__orb--cyan"></span>
            <span class="sec2-aurora__orb sec2-aurora__orb--coral"></span>
        </div>

        @include('colegio.secundaria.partials.topbar')

        <main class="sec-main">
            <a href="{{ route('colegio.secundaria.dashboard') }}#cursos" class="sec-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
                Volver a mis cursos
            </a>

            {{-- Portada del curso --}}
            <section class="sec-course-hero sec-course--{{ $course->aula['sec_theme'] }}">
                <div class="sec-course-hero__copy">
                    <span class="sec-eyebrow sec-eyebrow--dark">
                        <span class="sec-eyebrow__dot"></span>
                        Curso · {{ $portal['grade']['label'] }}
                    </span>

                    <h1 class="sec-course-hero__title">{{ $course->aula['name'] }}</h1>

                    <p class="sec-course-hero__meta">
                        {{ $portal['grade']['level'] }} · {{ $portal['grade']['year'] ?? now()->year }}
                        @if ($course->aula['teacher'])
                            · Docente: {{ $course->aula['teacher'] }}
                        @endif
                    </p>

                    <div class="sec-course-hero__chips">
                        <span>{{ $course->aula['units'] }} {{ $course->aula['units'] === 1 ? 'unidad' : 'unidades' }}</span>
                        <span>{{ $course->aula['sessions'] }} {{ $course->aula['sessions'] === 1 ? 'sesión' : 'sesiones' }}</span>
                        @if ($course->aula['activities'] > 0)
                            <span>{{ $course->aula['activities'] }} {{ $course->aula['activities'] === 1 ? 'actividad' : 'actividades' }}</span>
                        @endif
                    </div>

                    <div class="sec-course-hero__progress">
                        <div class="sec-course-hero__progress-head">
                            <span>Tu avance en este curso</span>
                            <strong>{{ $course->aula['percent'] }}%</strong>
                        </div>
                        <div class="sec-bar sec-bar--light" role="img" aria-label="Avance en el curso: {{ $course->aula['percent'] }}%">
                            <span style="width: {{ $course->aula['percent'] }}%"></span>
                        </div>
                        <small>
                            @if ($course->aula['activities_total'] > 0)
                                {{ $course->aula['completed'] }} de {{ $course->aula['activities_total'] }} actividades entregadas
                            @else
                                Tu docente todavía no publicó actividades en este curso
                            @endif
                        </small>
                    </div>
                </div>

                <div class="sec-course-hero__art" aria-hidden="true">
                    @include('colegio.secundaria.partials.course-emblem', ['theme' => $course->aula['sec_theme']])
                </div>
            </section>

            <div class="sec-layout sec-layout--course">
                {{-- Contenido --}}
                <section aria-labelledby="secUnitsTitle">
                    <div class="sec-section-head">
                        <div>
                            <span class="sec-kicker">Contenido publicado</span>
                            <h2 id="secUnitsTitle" class="sec-section-title">Unidades y sesiones</h2>
                        </div>
                        <span class="sec-section-count">{{ $course->units->count() }} {{ $course->units->count() === 1 ? 'unidad' : 'unidades' }}</span>
                    </div>

                    @if ($course->units->isEmpty())
                        <div class="sec-empty">
                            <span class="sec-empty__icon" aria-hidden="true">
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A1.5 1.5 0 0 1 5.5 4H10l2 2h6.5A1.5 1.5 0 0 1 20 7.5v11A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5z"/>
                                </svg>
                            </span>
                            <h3>Tu docente está preparando este curso</h3>
                            <p>Cuando publique las unidades aparecerán aquí con sus sesiones y materiales.</p>
                        </div>
                    @endif

                    @foreach ($course->units as $unit)
                        <article class="sec-unit">
                            <header class="sec-unit__head">
                                <span class="sec-unit__number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <div class="sec-unit__copy">
                                    <h3 class="sec-unit__title">{{ $unit->title }}</h3>
                                    @if ($unit->purpose)
                                        <p class="sec-unit__purpose">{{ $unit->purpose }}</p>
                                    @endif
                                </div>
                                @if ($unit->duration_hours)
                                    <span class="sec-unit__hours">{{ $unit->duration_hours }} h</span>
                                @endif
                            </header>

                            @if ($unit->significant_situation)
                                <div class="sec-unit__situation">
                                    <span class="sec-unit__situation-label">Situación significativa</span>
                                    <p>{{ $unit->significant_situation }}</p>
                                </div>
                            @endif

                            <div class="sec-unit__meta">
                                <span>{{ $unit->lessons->count() }} {{ $unit->lessons->count() === 1 ? 'sesión' : 'sesiones' }}</span>
                                @if ($unit->start_date)
                                    <span>Desde el {{ $unit->start_date->format('d/m/Y') }}</span>
                                @endif
                                @if ($unit->end_date)
                                    <span>Hasta el {{ $unit->end_date->format('d/m/Y') }}</span>
                                @endif
                            </div>

                            @if ($unit->lessons->isNotEmpty())
                                <div class="sec-sessions">
                                    @foreach ($unit->lessons as $lesson)
                                        @php($sessionNumber++)
                                        <details class="sec-session">
                                            <summary class="sec-session__summary">
                                                <span class="sec-session__number">{{ $sessionNumber }}</span>
                                                <span class="sec-session__copy">
                                                    <strong>{{ $lesson->title }}</strong>
                                                    @if ($lesson->purpose)
                                                        <small>{{ $lesson->purpose }}</small>
                                                    @endif
                                                </span>
                                                @if ($lesson->duration_minutes)
                                                    <span class="sec-session__duration">{{ $lesson->duration_minutes }} min</span>
                                                @endif
                                                <svg class="sec-session__chevron" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                                                </svg>
                                            </summary>

                                            <div class="sec-session__body">
                                                <?php
                                                    $moments = [
                                                        'Propósito' => $lesson->purpose,
                                                        'Inicio' => $lesson->intro_activity,
                                                        'Desarrollo' => $lesson->development,
                                                        'Cierre' => $lesson->closure,
                                                        'Metacognición' => $lesson->metacognition,
                                                        'Evaluación' => $lesson->evaluation,
                                                    ];
                                                ?>

                                                @foreach ($moments as $label => $text)
                                                    @if ($text)
                                                        <div class="sec-session__block">
                                                            <span class="sec-session__label">{{ $label }}</span>
                                                            <p>{!! nl2br(e($text)) !!}</p>
                                                        </div>
                                                    @endif
                                                @endforeach

                                                @if ($lesson->resources->isNotEmpty())
                                                    <div class="sec-session__block">
                                                        <span class="sec-session__label">Materiales de la sesión</span>
                                                        <div class="sec-resource-list">
                                                            @foreach ($lesson->resources as $resource)
                                                                @php($material = collect($materials)->firstWhere('id', $resource->id))
                                                                <div class="sec-resource">
                                                                    <span class="sec-resource__title">{{ $resource->title }}</span>
                                                                    @if ($material)
                                                                        <button type="button" class="sec-link" data-material-open data-material-id="{{ $material['id'] }}">
                                                                            Ver material
                                                                        </button>
                                                                        @if (! empty($material['external_url']))
                                                                            <a href="{{ $material['external_url'] }}" target="_blank" rel="noopener noreferrer" class="sec-link sec-link--soft">Ir al enlace</a>
                                                                        @endif
                                                                    @elseif ($resource->url)
                                                                        <a href="{{ $resource->url }}" target="_blank" rel="noopener noreferrer" class="sec-link">Ir al enlace</a>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($lesson->activities->isNotEmpty())
                                                    <div class="sec-session__block">
                                                        <span class="sec-session__label">Actividades</span>
                                                        <div class="sec-activities">
                                                            @foreach ($lesson->activities as $activity)
                                                                @include('colegio.secundaria.partials.activity', ['activity' => $activity])
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    @endforeach
                                </div>
                            @else
                                <p class="sec-unit__empty">Tu docente todavía está preparando las sesiones de esta unidad.</p>
                            @endif

                            @if ($unit->activities->isNotEmpty())
                                <div class="sec-unit__activities">
                                    <span class="sec-session__label">Actividades de la unidad</span>
                                    <div class="sec-activities">
                                        @foreach ($unit->activities as $activity)
                                            @include('colegio.secundaria.partials.activity', ['activity' => $activity])
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </article>
                    @endforeach

                    @if ($course->activities->isNotEmpty())
                        <div class="sec-section-head sec-section-head--tight">
                            <div>
                                <span class="sec-kicker">Sin unidad específica</span>
                                <h2 class="sec-section-title">Actividades del curso</h2>
                            </div>
                        </div>

                        <div class="sec-activities">
                            @foreach ($course->activities as $activity)
                                @include('colegio.secundaria.partials.activity', ['activity' => $activity])
                            @endforeach
                        </div>
                    @endif
                </section>

                {{-- Panel lateral --}}
                <aside class="sec-side">
                    <section class="sec-panel sec-panel--teacher">
                        <span class="sec-panel__icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="8" r="3.6"/>
                                <path stroke-linecap="round" d="M4.8 20c.6-4 3.6-6 7.2-6s6.6 2 7.2 6"/>
                            </svg>
                        </span>
                        <div>
                            <span class="sec-kicker">Acompañamiento</span>
                            <h2 class="sec-panel__title">
                                @if ($course->aula['teacher'])
                                    {{ $course->aula['teacher'] }}
                                @else
                                    Tu docente
                                @endif
                            </h2>
                            <p class="sec-panel__text">
                                Revisa cada sesión y pregunta tus dudas en clase. Las actividades se entregan en el aula.
                            </p>
                        </div>
                    </section>

                    <section class="sec-panel" aria-labelledby="secMaterialsTitle">
                        <div class="sec-panel__head">
                            <h2 id="secMaterialsTitle" class="sec-panel__title">Materiales</h2>
                            <span class="sec-panel__percent">{{ count($materials) }}</span>
                        </div>

                        @if (empty($materials))
                            <p class="sec-panel__text">Tu docente todavía no publicó materiales en este curso.</p>
                        @else
                            <div class="sec-material-list">
                                @foreach ($materials as $material)
                                    @include('colegio.secundaria.partials.material-card', ['material' => $material])
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="sec-tip">
                        <span class="sec-tip__icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m12 3 2.6 5.8 6.4.7-4.8 4.3 1.4 6.2-5.6-3.3-5.6 3.3 1.4-6.2L3 9.5l6.4-.7z"/>
                            </svg>
                        </span>
                        <div>
                            <strong>Antes de entregar</strong>
                            <p>Lee las indicaciones completas, revisa la fecha de cierre y guarda tu trabajo con tu nombre.</p>
                        </div>
                    </section>
                </aside>
            </div>
        </main>

        <footer class="sec-footer">
            <span>{{ $portal['school']['name'] }} · Aula Virtual</span>
            <span>{{ $course->aula['name'] }} · {{ $portal['grade']['label'] }}</span>
        </footer>

        @include('colegio.components.aula.material-dialog', ['materials' => $materials])

        @if (session('task_status') || $errors->any())
            <div class="sec-flash-wrap" role="status">
                @if (session('task_status'))
                    <p class="sec-flash sec-flash--ok">{{ session('task_status') }}</p>
                @endif
                @foreach ($errors->all() as $error)
                    <p class="sec-flash sec-flash--error">{{ $error }}</p>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('themes/colegio/js/aula.js') }}"></script>
@endpush
