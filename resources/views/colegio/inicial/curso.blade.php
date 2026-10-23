@extends('colegio.layouts.base')

@section('title', $course->aula['name'] . ' · Aula Inicial · ' . $portal['school']['name'])
@section('description', 'Unidades, sesiones y materiales del curso ' . $course->aula['name'] . ' del nivel Inicial.')
@section('body_class', 'font-body')

<?php
    $storage = \Illuminate\Support\Facades\Storage::disk('public');
    $sessionNumber = 0;
?>

@section('content')
    <div class="aula">
        @include('colegio.components.aula.scene')
        @include('colegio.inicial.partials.topbar')

        <main class="aula-main">
            <a href="{{ route('colegio.inicial.dashboard') }}#cursos" class="aula-back">
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
                                                                <?php
                                                                    $link = $resource->url ?: ($resource->path ? $storage->url($resource->path) : null);
                                                                ?>

                                                                @if ($link)
                                                                    <a class="material" href="{{ $link }}" target="_blank" rel="noopener noreferrer">
                                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 13.5a4 4 0 0 1 0-5.7l2-2a4 4 0 0 1 5.7 5.7l-1.3 1.3M13.5 10.5a4 4 0 0 1 0 5.7l-2 2a4 4 0 0 1-5.7-5.7l1.3-1.3"/>
                                                                        </svg>
                                                                        {{ $resource->title }}
                                                                    </a>
                                                                @else
                                                                    <span class="material">
                                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.5h7l5 5v12H6z"/>
                                                                            <path stroke-linecap="round" d="M13 3.5v5h5"/>
                                                                        </svg>
                                                                        {{ $resource->title }}
                                                                    </span>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($lesson->activities->isNotEmpty())
                                                    <div class="lesson__block">
                                                        <span class="lesson__block-label">Actividades</span>
                                                        <div class="material-list">
                                                            @foreach ($lesson->activities as $activity)
                                                                <span class="material">
                                                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5.5h14v11H9l-4 3z"/>
                                                                    </svg>
                                                                    {{ $activity->title }}
                                                                </span>
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
                        </article>
                    @endforeach
                </section>

                <aside class="space-y-4">
                    @include('colegio.inicial.partials.mascot')

                    @if ($course->resources->isNotEmpty())
                        <div class="aula-info-card">
                            <h2 class="font-display text-base font-semibold text-cole-forest">Materiales del curso</h2>
                            <div class="material-list mt-2">
                                @foreach ($course->resources as $resource)
                                    <?php
                                        $link = $resource->url ?: ($resource->path ? $storage->url($resource->path) : null);
                                    ?>

                                    @if ($link)
                                        <a class="material" href="{{ $link }}" target="_blank" rel="noopener noreferrer">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 13.5a4 4 0 0 1 0-5.7l2-2a4 4 0 0 1 5.7 5.7l-1.3 1.3M13.5 10.5a4 4 0 0 1 0 5.7l-2 2a4 4 0 0 1-5.7-5.7l1.3-1.3"/>
                                            </svg>
                                            {{ $resource->title }}
                                        </a>
                                    @else
                                        <span class="material">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.5h7l5 5v12H6z"/>
                                                <path stroke-linecap="round" d="M13 3.5v5h5"/>
                                            </svg>
                                            {{ $resource->title }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
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

        @include('colegio.inicial.partials.dock')
        @include('colegio.inicial.partials.help-dialog')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('themes/colegio/js/vendor/anime.umd.min.js') }}"></script>
    <script src="{{ asset('themes/colegio/js/aula.js') }}"></script>
@endpush
