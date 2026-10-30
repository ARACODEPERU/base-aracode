@extends('colegio.layouts.secundaria')

@section('title', 'Mi nota · ' . $activity->title . ' · ' . $portal['school']['name'])
@section('description', 'Nota obtenida en la evaluación ' . $activity->title . ' de Secundaria.')

@section('content')
    <div class="sec2 sec2--course sec2--result">
        <div class="sec2-aurora" aria-hidden="true">
            <span class="sec2-aurora__orb sec2-aurora__orb--purple"></span>
            <span class="sec2-aurora__orb sec2-aurora__orb--cyan"></span>
            <span class="sec2-aurora__orb sec2-aurora__orb--coral"></span>
        </div>

        @include('colegio.secundaria.partials.topbar')

        <main class="sec-main sec-main--narrow">
            <a href="{{ route('colegio.secundaria.course', $course->id) }}" class="sec-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
                Volver al curso
            </a>

            <section class="sec-result">
                <span class="sec-result__spark" aria-hidden="true">
                    <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m12 2.6 2.9 6.4 6.9.8-5.2 4.7 1.5 6.8L12 17.8l-6.1 3.5 1.5-6.8L2.2 9.8l6.9-.8z"/>
                    </svg>
                </span>

                <span class="sec-kicker">Evaluación completada</span>
                <h1 class="sec-result__title">{{ $activity->title }}</h1>
                <p class="sec-result__text">Esta es la nota de tu último intento. No mostramos las preguntas ni las respuestas correctas.</p>

                <div class="sec-result__score">
                    <strong>{{ $gradeLabel }}</strong>
                    @if ($summary['score'] !== null && $activity->grading_scale === 'numeric')
                        <span>puntos</span>
                    @endif
                </div>

                <div class="sec-result__stats" aria-label="Resumen de respuestas">
                    <div class="sec-result__stat sec-result__stat--ok">
                        <span>Aciertos</span>
                        <strong data-result-correct>{{ $summary['correct_count'] }}</strong>
                    </div>
                    <div class="sec-result__stat sec-result__stat--bad">
                        <span>Errores</span>
                        <strong data-result-incorrect>{{ $summary['incorrect_count'] }}</strong>
                    </div>
                </div>

                <p class="sec-result__attempts">
                    Usaste <strong>{{ $summary['used'] }}</strong> de <strong>{{ $summary['allowed'] }}</strong>
                    {{ $summary['allowed'] === 1 ? 'intento' : 'intentos' }}.
                </p>

                @if ($summary['remaining'] > 0)
                    <div class="sec-result__retry">
                        <span class="sec-result__retry-icon" aria-hidden="true">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 11a8 8 0 1 0-2.4 5.7M20 5v6h-6"/>
                            </svg>
                        </span>
                        <div>
                            <h2>¿Quieres intentarlo otra vez?</h2>
                            <p>
                                Te {{ $summary['remaining'] === 1 ? 'queda' : 'quedan' }}
                                {{ $summary['remaining'] }} {{ $summary['remaining'] === 1 ? 'intento' : 'intentos' }}.
                                La nueva nota reemplazará a la actual.
                            </p>
                        </div>
                        <a href="{{ route('colegio.secundaria.quiz', $activity->id) }}" class="sec-result__cta">
                            Intentar otra vez
                        </a>
                    </div>
                @else
                    <p class="sec-result__closed">Ya usaste todos los intentos de esta evaluación.</p>
                @endif

                <div class="sec-result__actions">
                    <a href="{{ route('colegio.secundaria.course', $course->id) }}" class="sec-result__link">
                        Volver al curso
                    </a>

                    @if ($summary['can_download'])
                        <a href="{{ route('colegio.secundaria.quiz.download', $activity->id) }}" class="sec-result__link sec-result__link--strong">
                            Descargar mi examen resuelto
                        </a>
                    @endif
                </div>
            </section>
        </main>
    </div>
@endsection
