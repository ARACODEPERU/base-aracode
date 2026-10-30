@extends('colegio.layouts.base')

@section('title', $course->aula['name'] . ' · Aula ' . $portal['grade']['level'] . ' · ' . $portal['school']['name'])
@section('description', 'Unidades, sesiones y materiales del curso ' . $course->aula['name'] . ' del nivel ' . $portal['grade']['level'] . '.')
@section('body_class', 'font-body')

<?php
    $sessionNumber = 0;
?>

@section('content')
    <div class="aula">
        @include('colegio.components.aula.scene')
        @include('colegio.aula.partials.topbar')

        <main class="aula-main">
            <a href="{{ route('colegio.aula.dashboard') }}#cursos" class="aula-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
                Volver a mis cursos
            </a>

            {{-- Portada del curso --}}
            <section class="course-hero course-card--{{ $course->aula['tone'] }}" data-listen>
                <div>
                    <span class="course-hero__kicker">Curso de {{ $portal['grade']['level'] }}</span>
                    <h1 class="course-hero__title">{{ $course->aula['name'] }}</h1>
                    <p class="course-hero__meta">
                        {{ $portal['grade']['label'] }}
                        @if ($course->aula['teacher'])
                            · {{ $course->aula['teacher'] }}
                        @endif
                    </p>

                    <div class="course-hero__stats">
                        <span class="course-hero__stat">
                            {{ $course->aula['units'] }} {{ $course->aula['units'] === 1 ? 'unidad' : 'unidades' }}
                        </span>
                        <span class="course-hero__stat">
                            {{ $course->aula['sessions'] }} {{ $course->aula['sessions'] === 1 ? 'sesión' : 'sesiones' }}
                        </span>
                        @if ($course->aula['activities'] > 0)
                            <span class="course-hero__stat">
                                {{ $course->aula['activities'] }} {{ $course->aula['activities'] === 1 ? 'actividad' : 'actividades' }}
                            </span>
                        @endif
                    </div>

                    @if (! empty($materials))
                        <button type="button" class="cole-btn cole-btn-ghost mt-4 h-11 px-4 text-xs" data-material-open>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A1.5 1.5 0 0 1 5.5 4H10l2 2h6.5A1.5 1.5 0 0 1 20 7.5v11A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5z"/>
                            </svg>
                            Ver materiales de apoyo
                        </button>
                    @endif
                </div>

                <div class="course-hero__art" aria-hidden="true">
                    @include('colegio.components.aula.course-art', ['art' => $course->aula['theme']])
                </div>
            </section>

            <div class="aula-layout">
                <section aria-labelledby="unidadesTitle">
                    <h2 id="unidadesTitle" class="aula-section-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A1.5 1.5 0 0 1 5.5 4H10l2 2h6.5A1.5 1.5 0 0 1 20 7.5v11A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5z"/>
                        </svg>
                        Mis unidades y sesiones
                    </h2>

                    @if ($course->units->isEmpty())
                        <div class="aula-empty">
                            <p class="font-display text-lg text-cole-forest">Todavía no hay unidades publicadas</p>
                            <p class="mx-auto mt-1 max-w-sm text-xs font-semibold leading-relaxed text-cole-ink/60">
                                Tu profesora está preparando el contenido de este curso. Vuelve a revisarlo pronto.
                            </p>
                        </div>
                    @endif

                    @foreach ($course->units as $unit)
                        <article class="unit-card mt-4">
                            <div class="unit-card__head">
                                <span class="unit-card__number">{{ $loop->iteration }}</span>
                                <div>
                                    <h3 class="unit-card__title">{{ $unit->title }}</h3>
                                    @if ($unit->purpose)
                                        <p class="unit-card__purpose">{{ $unit->purpose }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="unit-card__meta">
                                <span class="unit-tag">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                        <circle cx="12" cy="12" r="8.5"/>
                                        <path stroke-linecap="round" d="M12 7.5V12l3 2"/>
                                    </svg>
                                    {{ $unit->lessons->count() }} {{ $unit->lessons->count() === 1 ? 'sesión' : 'sesiones' }}
                                </span>

                                @if ($unit->duration_hours)
                                    <span class="unit-tag">{{ $unit->duration_hours }} horas</span>
                                @endif

                                @if ($unit->start_date)
                                    <span class="unit-tag">
                                        Desde el {{ $unit->start_date->format('d/m/Y') }}
                                    </span>
                                @endif
                            </div>

                            @if ($unit->lessons->isNotEmpty())
                                <div class="lesson-list">
                                    @foreach ($unit->lessons as $lesson)
                                        <?php $sessionNumber++; ?>
                                        <details class="lesson">
                                            <summary class="lesson__summary">
                                                <span class="lesson__dot">{{ $sessionNumber }}</span>
                                                <span>{{ $lesson->title }}</span>
                                                <svg class="lesson__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                                                </svg>
                                            </summary>

                                            <div class="lesson__body">
                                                @if ($lesson->purpose)
                                                    <div class="lesson__block">
                                                        <span class="lesson__block-label">¿Qué aprenderé?</span>
                                                        <span class="lesson__block-text">{{ $lesson->purpose }}</span>
                                                    </div>
                                                @endif

                                                <?php
                                                    $moments = [
                                                        'Inicio' => $lesson->intro_activity,
                                                        'Desarrollo' => $lesson->development,
                                                        'Cierre' => $lesson->closure,
                                                        'Metacognición' => $lesson->metacognition,
                                                        'Evaluación' => $lesson->evaluation,
                                                    ];
                                                ?>

                                                @foreach ($moments as $label => $text)
                                                    @if ($text)
                                                        <div class="lesson__block">
                                                            <span class="lesson__block-label">{{ $label }}</span>
                                                            <span class="lesson__block-text">{!! nl2br(e($text)) !!}</span>
                                                        </div>
                                                    @endif
                                                @endforeach

                                                @if ($lesson->duration_minutes)
                                                    <div class="lesson__block">
                                                        <span class="lesson__block-label">Duración</span>
                                                        <span class="lesson__block-text">{{ $lesson->duration_minutes }} minutos</span>
                                                    </div>
                                                @endif

                                                @if ($lesson->resources->isNotEmpty())
                                                    <div class="lesson__block">
                                                        <span class="lesson__block-label">Materiales</span>
                                                        <div class="material-list">
                                                            @foreach ($lesson->resources as $resource)
                                                                <button type="button" class="material material--button"
                                                                        data-material-open data-material-id="{{ $resource->id }}">
                                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.5h7l5 5v12H6z"/>
                                                                        <path stroke-linecap="round" d="M13 3.5v5h5"/>
                                                                        <path stroke-linecap="round" d="M9 14h6M9 17h4"/>
                                                                    </svg>
                                                                    {{ $resource->title }}
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($lesson->activities->isNotEmpty())
                                                    <div class="lesson__block">
                                                        <span class="lesson__block-label">Actividades</span>
                                                        <div class="aula-activity-list">
                                                            @foreach ($lesson->activities as $activity)
                                                                @if ($portal['grade']['level_code'] === \Modules\Academic\Entities\AcaSchoolLevel::PRIMARIA)
                                                                    @include('colegio.aula.partials.activity', ['activity' => $activity])
                                                                @else
                                                                    <span class="material">
                                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5.5h14v11H9l-4 3z"/>
                                                                        </svg>
                                                                        {{ $activity->title }}
                                                                    </span>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    @endforeach
                                </div>
                            @else
                                <p class="px-4 pb-4 text-xs font-semibold text-cole-ink/55">
                                    Tu profesora todavía está preparando las sesiones de esta unidad.
                                </p>
                            @endif

                            @if ($portal['grade']['level_code'] === \Modules\Academic\Entities\AcaSchoolLevel::PRIMARIA && $unit->activities->isNotEmpty())
                                <div class="px-4 pb-4">
                                    <span class="lesson__block-label">Actividades de la unidad</span>
                                    <div class="aula-activity-list mt-2">
                                        @foreach ($unit->activities as $activity)
                                            @include('colegio.aula.partials.activity', ['activity' => $activity])
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </article>
                    @endforeach

                    @if ($portal['grade']['level_code'] === \Modules\Academic\Entities\AcaSchoolLevel::PRIMARIA && $course->activities->isNotEmpty())
                        <article class="unit-card mt-4">
                            <div class="unit-card__head">
                                <span class="unit-card__number">+</span>
                                <div>
                                    <h3 class="unit-card__title">Actividades del curso</h3>
                                    <p class="unit-card__purpose">Tareas y evaluaciones de todo el curso.</p>
                                </div>
                            </div>
                            <div class="aula-activity-list px-4 pb-4">
                                @foreach ($course->activities as $activity)
                                    @include('colegio.aula.partials.activity', ['activity' => $activity])
                                @endforeach
                            </div>
                        </article>
                    @endif
                </section>

                <aside class="space-y-4">
                    @include('colegio.aula.partials.mascot')

                    @if (! empty($materials))
                        <div class="aula-info-card">
                            <h2 class="font-display text-base font-semibold text-cole-forest">Materiales de apoyo</h2>
                            <p class="mt-1 text-[11px] font-bold leading-snug text-cole-ink/55">
                                Imágenes y PDF que puedes ver aquí mismo.
                            </p>

                            @if ($course->resources->isNotEmpty())
                                <div class="material-list mt-2">
                                    @foreach ($course->resources as $resource)
                                        <button type="button" class="material material--button"
                                                data-material-open data-material-id="{{ $resource->id }}">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.5h7l5 5v12H6z"/>
                                                <path stroke-linecap="round" d="M13 3.5v5h5"/>
                                                <path stroke-linecap="round" d="M9 14h6M9 17h4"/>
                                            </svg>
                                            {{ $resource->title }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            <button type="button" class="cole-btn cole-btn-primary mt-3 h-11 w-full text-xs" data-material-open>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A1.5 1.5 0 0 1 5.5 4H10l2 2h6.5A1.5 1.5 0 0 1 20 7.5v11A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5z"/>
                                </svg>
                                Ver todos ({{ count($materials) }})
                            </button>
                        </div>
                    @endif

                    <div class="aula-hint">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.2a6 6 0 0 0-3.4 10.9V17h6.8v-2.9A6 6 0 0 0 12 3.2z"/>
                            <path stroke-linecap="round" d="M9.6 20h4.8"/>
                        </svg>
                        <span>Abre cada sesión con calma. Si algo no se entiende, pídele ayuda a tu profesora.</span>
                    </div>
                </aside>
            </div>
        </main>

        @include('colegio.aula.partials.dock')
        @include('colegio.aula.partials.help-dialog')
        @include('colegio.components.aula.material-dialog', ['materials' => $materials])

        @if (session('task_status') || $errors->any())
            <div class="aula-flash-wrap" role="status">
                @if (session('task_status'))
                    <p class="aula-flash aula-flash--ok">{{ session('task_status') }}</p>
                @endif
                @foreach ($errors->all() as $error)
                    <p class="aula-flash aula-flash--error">{{ $error }}</p>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('themes/colegio/js/vendor/anime.umd.min.js') }}"></script>
    <script src="{{ asset('themes/colegio/js/aula.js') }}"></script>
@endpush
