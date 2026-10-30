@extends('colegio.layouts.secundaria')

@section('title', $activity->title . ' · Evaluación · ' . $portal['school']['name'])
@section('description', 'Responde la evaluación ' . $activity->title . ' del curso ' . $course->aula['name'] . ' de Secundaria.')

@section('content')
    <div class="sec2 sec2--course sec2--exam">
        <div class="sec2-aurora" aria-hidden="true">
            <span class="sec2-aurora__orb sec2-aurora__orb--purple"></span>
            <span class="sec2-aurora__orb sec2-aurora__orb--cyan"></span>
            <span class="sec2-aurora__orb sec2-aurora__orb--coral"></span>
        </div>

        @include('colegio.secundaria.partials.topbar')

        <main class="sec-main">
            <a href="{{ route('colegio.secundaria.course', $course->id) }}" class="sec-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 6l-6 6 6 6"/>
                </svg>
                Volver al curso
            </a>

            <section class="sec-exam sec-exam--{{ $course->aula['sec_theme'] }}">
                <header class="sec-exam__head">
                    <div class="sec-exam__copy">
                        <span class="sec-eyebrow">
                            <span class="sec-eyebrow__dot"></span>
                            {{ $course->aula['name'] }} · Evaluación
                        </span>
                        <h1 class="sec-exam__title">{{ $activity->title }}</h1>
                        <div class="sec-exam__chips">
                            <span>Intento {{ $activeAttempt?->attempt_number ?? $summary['next_number'] }} de {{ $summary['allowed'] }}</span>
                            @if ($activity->close_at)
                                <span>Se cierra el {{ $activity->close_at->format('d/m/Y') }} a las {{ $activity->close_at->format('H:i') }}</span>
                            @endif
                            <span>Tiempo: {{ (int) ($activity->duration_minutes ?: 30) }} min</span>
                            @if ($activity->grading_scale === 'numeric')
                                <span>{{ rtrim(rtrim(number_format((float) $activity->max_score, 2, '.', ''), '0'), '.') }} pts</span>
                            @endif
                        </div>
                    </div>

                    <span class="sec-exam__art" aria-hidden="true">
                        @include('colegio.secundaria.partials.course-emblem', ['theme' => $course->aula['sec_theme']])
                    </span>
                </header>

                @if ($activity->instructions)
                    <div class="sec-exam__instructions">
                        <span class="sec-kicker">Indicaciones</span>
                        <p>{!! nl2br(e($activity->instructions)) !!}</p>
                    </div>
                @endif

                @if ($summary['used'] > 0)
                    <div class="sec-exam__notice" role="status">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01"/>
                            <circle cx="12" cy="12" r="9"/>
                        </svg>
                        <span>
                            Tu nota actual es
                            <strong>{{ rtrim(rtrim(number_format((float) $summary['score'], 2, '.', ''), '0'), '.') }}</strong>.
                            Este intento la reemplazará.
                        </span>
                    </div>
                @endif

                @if ($activeAttempt)
                    @include('colegio.components.quiz-form', [
                        'action' => route('colegio.secundaria.quiz.submit', $activity->id),
                        'activity' => $activity,
                        'summary' => $summary,
                        'activeAttempt' => $activeAttempt,
                        'remainingSeconds' => $remainingSeconds,
                        'totalSeconds' => $totalSeconds,
                    ])

                    <p class="sec-exam__foot">
                        El cronómetro corre desde que inició este intento. Al llegar a cero, el examen se envía solo.
                    </p>
                @else
                    {{-- Aviso que no se puede cerrar: el examen permanece bloqueado
                         hasta que el alumno confirma el inicio. --}}
                    <div class="quiz-lock" role="dialog" aria-modal="true" aria-labelledby="quizLockTitle">
                        <div class="quiz-lock__card">
                            <span class="quiz-lock__icon" aria-hidden="true">
                                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="4.5" y="10" width="15" height="10.5" rx="3"/>
                                    <path stroke-linecap="round" d="M8 10V7.5a4 4 0 0 1 8 0V10M12 14v3"/>
                                </svg>
                            </span>

                            <span class="sec-kicker">Evaluación bloqueada</span>
                            <h2 id="quizLockTitle">{{ $activity->title }}</h2>
                            <p>
                                Este examen todavía no ha comenzado. Al pulsar <strong>Iniciar examen</strong>
                                el tiempo empezará a correr y no podrás pausarlo.
                            </p>

                            <div class="quiz-lock__meta">
                                <div>
                                    <span>Se cierra</span>
                                    <strong>
                                        @if ($activity->close_at)
                                            {{ $activity->close_at->format('d/m/Y') }} · {{ $activity->close_at->format('H:i') }}
                                        @else
                                            Sin fecha de cierre
                                        @endif
                                    </strong>
                                </div>
                                <div>
                                    <span>Tiempo por intento</span>
                                    <strong>{{ (int) ($activity->duration_minutes ?: 30) }} minutos</strong>
                                </div>
                                <div>
                                    <span>Intentos</span>
                                    <strong>{{ $summary['allowed'] }}</strong>
                                </div>
                            </div>

                            @if ($errors->any())
                                <div class="quiz-alert quiz-alert--error" role="alert">
                                    @foreach ($errors->all() as $error)
                                        <p>{{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif

                            <form method="POST" action="{{ route('colegio.secundaria.quiz.start', $activity->id) }}">
                                @csrf
                                <button type="submit" class="quiz-lock__button" autofocus>
                                    Iniciar examen
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/>
                                    </svg>
                                </button>
                            </form>

                            <p class="quiz-lock__note">
                                Si la hora de cierre llega antes de que termines, el examen se cerrará y enviará automáticamente.
                            </p>
                        </div>
                    </div>
                @endif
            </section>
        </main>

        <footer class="sec-footer">
            <span>{{ $portal['school']['name'] }} · Aula Virtual</span>
            <span>{{ $course->aula['name'] }} · Evaluación</span>
        </footer>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('themes/colegio/js/examen.js') }}" defer></script>
@endpush
