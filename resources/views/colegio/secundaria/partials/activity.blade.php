@php
    $state = $activityStates[$activity->id] ?? [];
    $isQuiz = $activity->type === 'quiz';
    $isTask = $activity->type === 'task';
    $score = $state['score'] ?? null;
    $gradeText = $state['grade_text'] ?? null;
    $scoreLabel = $gradeText ?: ($score !== null
        ? rtrim(rtrim(number_format((float) $score, 2, '.', ''), '0'), '.')
        : null);
    $maxScore = rtrim(rtrim(number_format((float) $activity->max_score, 2, '.', ''), '0'), '.');
@endphp

<article class="sec-activity sec-activity--{{ $isQuiz ? 'quiz' : ($isTask ? 'task' : 'forum') }}"
         id="sec-actividad-{{ $activity->id }}">
    <div class="sec-activity__head">
        <span class="sec-activity__type">
            {{ $isQuiz ? 'Evaluación' : ($isTask ? 'Tarea' : 'Foro') }}
        </span>
        @if ($activity->grading_scale === 'numeric')
            <span class="sec-activity__score">{{ $maxScore }} pts</span>
        @endif
    </div>

    <h4>{{ $activity->title }}</h4>

    @if ($activity->instructions)
        <p>{{ \Illuminate\Support\Str::limit(strip_tags($activity->instructions), 260) }}</p>
    @endif

    @if ($activity->close_at)
        <span class="sec-activity__date">Hasta el {{ $activity->close_at->format('d/m/Y H:i') }}</span>
    @endif

    @if ($isQuiz)
        @if (($state['used'] ?? 0) > 0)
            <div class="sec-activity__result">
                <span class="sec-activity__result-label">Tu nota vigente</span>
                <strong>{{ $scoreLabel }}{{ $activity->grading_scale === 'numeric' && $scoreLabel !== null ? ' / ' . $maxScore : '' }}</strong>
                <span class="sec-activity__result-meta">
                    {{ $state['used'] }} de {{ $state['allowed'] }} {{ $state['allowed'] === 1 ? 'intento' : 'intentos' }}
                </span>
            </div>
        @endif

        <div class="sec-activity__actions">
            @if ($state['can_take'] ?? false)
                <a href="{{ route('colegio.secundaria.quiz', $activity->id) }}" class="sec-activity__button">
                    {{ ($state['in_progress'] ?? false)
                        ? 'Continuar evaluación'
                        : (($state['used'] ?? 0) > 0 ? 'Intentar de nuevo' : 'Rendir evaluación') }}
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </a>
            @elseif ($state['latest'] ?? null)
                <a href="{{ route('colegio.secundaria.quiz.result', $activity->id) }}" class="sec-activity__button sec-activity__button--soft">
                    Ver mi nota
                </a>
            @endif

            @if ($state['can_download'] ?? false)
                <a href="{{ route('colegio.secundaria.quiz.download', $activity->id) }}" class="sec-activity__link">
                    Descargar examen resuelto
                </a>
            @endif
        </div>

        @if ($state['error'] ?? null)
            <p class="sec-activity__note">{{ $state['error'] }}</p>
        @endif
    @endif

    @if ($isTask)
        @if ($state['submission'] ?? null)
            <div class="sec-task-submission">
                <span class="sec-task-submission__status">
                    {{ $state['submission']->status === 'graded' ? 'Calificada' : 'Entregada' }}
                </span>
                <span class="sec-task-submission__date">
                    {{ $state['submission']->submitted_at?->format('d/m/Y H:i') }}
                </span>

                @if ($state['submission']->status === 'graded')
                    <div class="sec-task-submission__grade">
                        <span>Nota</span>
                        <strong>{{ $state['submission']->grade_text ?: rtrim(rtrim(number_format((float) $state['submission']->score, 2, '.', ''), '0'), '.') }}</strong>
                        @if ($state['submission']->grade_text && $state['submission']->score !== null)
                            <small>({{ rtrim(rtrim(number_format((float) $state['submission']->score, 2, '.', ''), '0'), '.') }} / {{ $maxScore }})</small>
                        @endif
                    </div>
                    @if ($state['submission']->feedback)
                        <p class="sec-task-submission__feedback">{{ $state['submission']->feedback }}</p>
                    @endif
                @endif
            </div>
        @endif

        @if ($state['can_submit'] ?? false)
            <form method="POST"
                  action="{{ route('colegio.secundaria.task.submit', $activity->id) }}"
                  enctype="multipart/form-data"
                  class="sec-task-form">
                @csrf
                <label class="sec-task-form__field">
                    <span>Sube tu tarea en PDF</span>
                    <input type="file" name="file" accept="application/pdf,.pdf" required>
                </label>
                <button type="submit" class="sec-activity__button">
                    Subir mi tarea
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4 4 4"/>
                        <path stroke-linecap="round" d="M5 16v3.5h14V16"/>
                    </svg>
                </button>
                <small>Solo PDF, máximo 10 MB. Puedes reemplazarlo hasta que tu docente lo califique.</small>
            </form>
        @elseif ($state['submission']?->status !== 'graded' && ($state['error'] ?? null))
            <p class="sec-activity__note">{{ $state['error'] }}</p>
        @endif
    @endif
</article>
