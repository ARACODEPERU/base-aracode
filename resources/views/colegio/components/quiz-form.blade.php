@php
    $activeAttempt = $activeAttempt ?? null;
    $remainingSeconds = $remainingSeconds ?? null;
    $totalSeconds = $totalSeconds ?? null;
    $questionCount = $activity->questions->count();

    if ($remainingSeconds !== null) {
        $hours = intdiv($remainingSeconds, 3600);
        $minutes = intdiv($remainingSeconds % 3600, 60);
        $seconds = $remainingSeconds % 60;
        $remainingLabel = $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $seconds)
            : sprintf('%02d:%02d', $minutes, $seconds);
    } else {
        $remainingLabel = '--:--';
    }
@endphp

{{-- Formulario de una evaluación en línea. Las respuestas correctas nunca --}}
{{-- llegan al navegador: el alumno solo ve las preguntas y sus alternativas. --}}
<form method="POST"
      action="{{ $action }}"
      class="quiz-form quiz-runner"
      id="quizForm"
      data-quiz-runner
      data-expires-at="{{ $activeAttempt?->expires_at?->toIso8601String() }}"
      data-total-seconds="{{ $totalSeconds }}"
      data-close-at="{{ $activity->close_at?->toIso8601String() }}"
      data-question-count="{{ $questionCount }}">
    @csrf
    <input type="hidden" name="auto_submitted" value="0" data-quiz-auto>

    @if ($errors->any())
        <div class="quiz-alert quiz-alert--error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="quiz-timer" data-quiz-timer>
        <span class="quiz-timer__icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="13" r="8"/>
                <path stroke-linecap="round" d="M12 9v4l2.5 2M9 2h6"/>
            </svg>
        </span>
        <div class="quiz-timer__copy">
            <span class="quiz-timer__label">Tiempo restante</span>
            <strong class="quiz-timer__value" data-quiz-time>{{ $remainingLabel }}</strong>
            <span class="quiz-timer__meta">
                @if ($activity->close_at)
                    El examen se cierra a las {{ $activity->close_at->format('H:i') }} del
                    {{ $activity->close_at->format('d/m/Y') }}, aunque todavía te quede tiempo.
                @else
                    Al llegar a cero, el examen se enviará automáticamente.
                @endif
            </span>
        </div>
        <span class="quiz-timer__badge">Intento {{ $activeAttempt?->attempt_number ?? $summary['next_number'] }}</span>
    </div>

    <div class="quiz-progress">
        <span class="quiz-progress__text" data-quiz-position>
            Pregunta 1 de {{ $questionCount }}
        </span>
        <span class="quiz-progress__track" aria-hidden="true">
            <span class="quiz-progress__bar" data-quiz-progress></span>
        </span>
    </div>

    <ol class="quiz-questions">
        @foreach ($activity->questions as $question)
            @php
                $isShort = $question->type === \Modules\Academic\Entities\AcaVirtualActivityQuestion::TYPE_SHORT;
                $isMultiple = $question->type === \Modules\Academic\Entities\AcaVirtualActivityQuestion::TYPE_MULTIPLE;
                $selected = collect(old('answers.' . $question->id . '.option_ids', []))->map(fn ($id) => (int) $id);
                $previousText = old('answers.' . $question->id . '.answer_text');
            @endphp

            <li class="quiz-question"
                id="pregunta-{{ $question->id }}"
                data-quiz-question
                data-question-id="{{ $question->id }}">
                <fieldset>
                    <legend class="quiz-question__legend">
                        <span class="quiz-question__number" data-quiz-number>{{ $loop->iteration }}</span>
                        <span class="quiz-question__text">{{ $question->question }}</span>
                        <span class="quiz-question__points">{{ rtrim(rtrim(number_format((float) $question->points, 2, '.', ''), '0'), '.') }} pts</span>
                    </legend>

                    @if ($isShort)
                        <label class="quiz-answer-text">
                            <span>Tu respuesta</span>
                            <textarea name="answers[{{ $question->id }}][answer_text]"
                                      rows="3"
                                      maxlength="2000"
                                      placeholder="Escribe tu respuesta aquí">{{ $previousText }}</textarea>
                        </label>
                    @else
                        <div class="quiz-options">
                            @foreach ($question->options as $option)
                                <label class="quiz-option">
                                    <input type="{{ $isMultiple ? 'checkbox' : 'radio' }}"
                                           name="answers[{{ $question->id }}][option_ids][]"
                                           value="{{ $option->id }}"
                                           @checked($selected->contains((int) $option->id))>
                                    <span>{{ $option->text }}</span>
                                </label>
                            @endforeach
                        </div>
                        @if ($isMultiple)
                            <p class="quiz-hint">Puedes marcar más de una alternativa.</p>
                        @endif
                    @endif
                </fieldset>
            </li>
        @endforeach
    </ol>

    <div class="quiz-navigation" data-quiz-navigation>
        <button type="button" class="quiz-nav__button" data-quiz-prev>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M11 6l-6 6 6 6"/>
            </svg>
            Anterior
        </button>

        <span class="quiz-nav__counter" data-quiz-counter>1 / {{ $questionCount }}</span>

        <button type="button" class="quiz-nav__button quiz-nav__button--next" data-quiz-next>
            Siguiente
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
        </button>
    </div>

    <div class="quiz-submit">
        <p class="quiz-submit__note">
            Al terminar verás tu nota, tus aciertos y tus errores. No se muestran las respuestas correctas.
            @if ($summary['used'] > 0)
                Este intento reemplazará tu nota anterior.
            @endif
        </p>
        <button type="submit" class="quiz-submit__button" data-quiz-finish>
            Terminar examen
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7"/>
            </svg>
        </button>
    </div>

    <div class="quiz-confirm" data-quiz-confirm hidden>
        <div class="quiz-confirm__card" role="alertdialog" aria-modal="true" aria-labelledby="quizConfirmTitle">
            <span class="quiz-confirm__icon" aria-hidden="true">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9"/>
                    <path stroke-linecap="round" d="M12 8v4m0 4h.01"/>
                </svg>
            </span>
            <h2 id="quizConfirmTitle">¿Terminar el examen?</h2>
            <p data-quiz-confirm-text>Aún tienes tiempo. Si terminas ahora, ya no podrás cambiar tus respuestas.</p>
            <div class="quiz-confirm__actions">
                <button type="button" class="quiz-confirm__button" data-quiz-cancel>Seguir respondiendo</button>
                <button type="button" class="quiz-confirm__button quiz-confirm__button--primary" data-quiz-accept>
                    Sí, terminar examen
                </button>
            </div>
        </div>
    </div>

    <noscript>
        <p class="quiz-hint">
            Este navegador no puede mostrar el temporizador ni las preguntas de una en una.
            El examen seguirá cerrándose en la fecha y hora indicadas.
        </p>
    </noscript>
</form>
