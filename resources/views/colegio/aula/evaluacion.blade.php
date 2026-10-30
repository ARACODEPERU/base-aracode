@extends('colegio.layouts.base')

@section('title', $activity->title . ' · Evaluación · ' . $portal['school']['name'])
@section('description', 'Responde la evaluación ' . $activity->title . ' del curso ' . $course->aula['name'] . '.')
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

            <section class="aula-exam">
                <header class="aula-exam__head">
                    <div>
                        <span class="aula-exam__kicker">{{ $course->aula['name'] }} · Evaluación</span>
                        <h1 class="aula-exam__title">{{ $activity->title }}</h1>
                        <p class="aula-exam__meta">
                            Intento {{ $activeAttempt?->attempt_number ?? $summary['next_number'] }} de {{ $summary['allowed'] }}
                            @if ($activity->close_at)
                                · Se cierra el {{ $activity->close_at->format('d/m/Y') }} a las {{ $activity->close_at->format('H:i') }}
                            @endif
                            · Tiempo: {{ (int) ($activity->duration_minutes ?: 30) }} min
                        </p>
                    </div>

                    <span class="aula-exam__badge" aria-hidden="true">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 3.5h9l4 4v13H6z"/>
                            <path stroke-linecap="round" d="M9 12h6M9 15.5h4"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 7 1.4 1.4L13 5.8"/>
                        </svg>
                    </span>
                </header>

                @if ($activity->instructions)
                    <div class="aula-exam__instructions">
                        <strong>Indicaciones</strong>
                        <p>{!! nl2br(e($activity->instructions)) !!}</p>
                    </div>
                @endif

                @if ($summary['used'] > 0)
                    <div class="aula-exam__notice">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01"/>
                            <circle cx="12" cy="12" r="9"/>
                        </svg>
                        <span>Ya tienes una nota de {{ $summary['score'] !== null ? rtrim(rtrim(number_format((float) $summary['score'], 2, '.', ''), '0'), '.') : '—' }}. Si respondes otra vez, la nueva nota reemplazará a la anterior.</span>
                    </div>
                @endif

                @if ($activeAttempt)
                    @include('colegio.components.quiz-form', [
                        'action' => route('colegio.aula.quiz.submit', $activity->id),
                        'activity' => $activity,
                        'summary' => $summary,
                        'activeAttempt' => $activeAttempt,
                        'remainingSeconds' => $remainingSeconds,
                        'totalSeconds' => $totalSeconds,
                    ])

                    <p class="aula-exam__foot">
                        El cronómetro corre desde que iniciaste este intento. Al llegar a cero, el examen se envía solo.
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

                            <span class="aula-exam__kicker">Evaluación bloqueada</span>
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

                            <form method="POST" action="{{ route('colegio.aula.quiz.start', $activity->id) }}">
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

        @include('colegio.aula.partials.dock')
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('themes/colegio/js/aula.js') }}" defer></script>
    <script src="{{ asset('themes/colegio/js/examen.js') }}" defer></script>
@endpush
