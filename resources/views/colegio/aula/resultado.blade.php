@extends('colegio.layouts.base')

@section('title', 'Mi nota · ' . $activity->title . ' · ' . $portal['school']['name'])
@section('description', 'Nota obtenida en la evaluación ' . $activity->title . '.')
@section('body_class', 'font-body')

@section('content')
    <div class="aula">
        @include('colegio.components.aula.scene')
        @include('colegio.aula.partials.topbar')

        <main class="aula-main">
            <a href="{{ route('colegio.aula.course', $course->id) }}" class="aula-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
                Volver al curso
            </a>

            <section class="aula-result">
                <div class="aula-result__art" aria-hidden="true">
                    <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                        <circle cx="60" cy="60" r="52" fill="#FFF4D6"/>
                        <path d="M60 24l10.6 21.5 23.7 3.5-17.2 16.7 4.1 23.6L60 78.2 38.8 89.3l4.1-23.6-17.2-16.7 23.7-3.5z" fill="#F7C45C" stroke="#E0A63A" stroke-width="4" stroke-linejoin="round"/>
                    </svg>
                </div>

                <span class="aula-result__kicker">{{ $activity->title }}</span>
                <h1 class="aula-result__title">¡Terminaste tu evaluación!</h1>
                <p class="aula-result__text">Esta es la nota de tu último intento.</p>

                <div class="aula-result__score">
                    <strong>{{ $gradeLabel }}</strong>
                    @if ($summary['score'] !== null && $activity->grading_scale === 'numeric')
                        <span>puntos</span>
                    @endif
                </div>

                <div class="aula-result__stats" aria-label="Resumen de respuestas">
                    <div class="aula-result__stat aula-result__stat--ok">
                        <span>Aciertos</span>
                        <strong data-result-correct>{{ $summary['correct_count'] }}</strong>
                    </div>
                    <div class="aula-result__stat aula-result__stat--bad">
                        <span>Errores</span>
                        <strong data-result-incorrect>{{ $summary['incorrect_count'] }}</strong>
                    </div>
                </div>

                <p class="aula-result__attempts">
                    Usaste {{ $summary['used'] }} de {{ $summary['allowed'] }}
                    {{ $summary['allowed'] === 1 ? 'intento' : 'intentos' }}.
                </p>

                @if ($summary['remaining'] > 0)
                    <div class="aula-result__retry">
                        <h2>¿Quieres intentarlo otra vez?</h2>
                        <p>
                            Te {{ $summary['remaining'] === 1 ? 'queda' : 'quedan' }}
                            {{ $summary['remaining'] }} {{ $summary['remaining'] === 1 ? 'intento' : 'intentos' }}.
                            Si lo haces, la nueva nota reemplazará a la actual.
                        </p>
                        <a href="{{ route('colegio.aula.quiz', $activity->id) }}" class="aula-result__button aula-result__button--primary">
                            Intentar otra vez
                        </a>
                    </div>
                @else
                    <p class="aula-result__closed">Ya usaste todos los intentos de esta evaluación.</p>
                @endif

                <div class="aula-result__actions">
                    <a href="{{ route('colegio.aula.course', $course->id) }}" class="aula-result__button">
                        Volver al curso
                    </a>

                    @if ($summary['can_download'])
                        <a href="{{ route('colegio.aula.quiz.download', $activity->id) }}" class="aula-result__button">
                            Descargar mi examen resuelto
                        </a>
                    @endif
                </div>
            </section>
        </main>

        @include('colegio.aula.partials.dock')
    </div>
@endsection
