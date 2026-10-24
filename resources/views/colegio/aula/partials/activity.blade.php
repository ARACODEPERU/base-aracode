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

<div class="aula-activity aula-activity--{{ $isQuiz ? 'quiz' : ($isTask ? 'task' : 'other') }}"
     id="aula-actividad-{{ $activity->id }}">
    <div class="aula-activity__head">
        <span class="aula-activity__type">
            {{ $isQuiz ? 'Evaluación' : ($isTask ? 'Tarea' : 'Actividad') }}
        </span>
        @if ($activity->grading_scale === 'numeric')
            <span class="aula-activity__points">{{ $maxScore }} pts</span>
        @endif
    </div>

    <strong class="aula-activity__title">{{ $activity->title }}</strong>

    @if ($activity->instructions)
        <p class="aula-activity__text">{{ \Illuminate\Support\Str::limit(strip_tags($activity->instructions), 220) }}</p>
    @endif

    @if ($isQuiz)
        @if (($state['used'] ?? 0) > 0)
            <p class="aula-activity__grade">
                Mi nota: <strong>{{ $scoreLabel }}{{ $activity->grading_scale === 'numeric' && $scoreLabel !== null ? ' / ' . $maxScore : '' }}</strong>
                · {{ $state['used'] }} de {{ $state['allowed'] }} {{ $state['allowed'] === 1 ? 'intento' : 'intentos' }}
            </p>
        @endif

        @if ($state['can_take'] ?? false)
            <a href="{{ route('colegio.aula.quiz', $activity->id) }}" class="aula-activity__button">
                {{ ($state['in_progress'] ?? false)
                    ? 'Continuar evaluación'
                    : (($state['used'] ?? 0) > 0 ? 'Intentar otra vez' : 'Responder evaluación') }}
            </a>
        @elseif ($state['latest'] ?? null)
            <a href="{{ route('colegio.aula.quiz.result', $activity->id) }}" class="aula-activity__button aula-activity__button--soft">
                Ver mi nota
            </a>
        @endif

        @if ($state['can_download'] ?? false)
            <a href="{{ route('colegio.aula.quiz.download', $activity->id) }}" class="aula-activity__link">
                Descargar mi examen resuelto
            </a>
        @endif

        @if ($state['error'] ?? null)
            <p class="aula-activity__note">{{ $state['error'] }}</p>
        @endif
    @endif

    @if ($isTask)
        @if ($state['submission'] ?? null)
            <div class="aula-activity__submission">
                <span>{{ $state['submission']->status === 'graded' ? 'Calificada' : 'Entregada' }}</span>
                @if ($state['submission']->status === 'graded')
                    <strong>
                        Nota:
                        {{ $state['submission']->grade_text ?: rtrim(rtrim(number_format((float) $state['submission']->score, 2, '.', ''), '0'), '.') }}
                    </strong>
                    @if ($state['submission']->feedback)
                        <small>{{ $state['submission']->feedback }}</small>
                    @endif
                @endif
            </div>
        @endif

        @if ($state['can_submit'] ?? false)
            <form method="POST"
                  action="{{ route('colegio.aula.task.submit', $activity->id) }}"
                  enctype="multipart/form-data"
                  class="aula-activity__upload">
                @csrf
                <label>
                    <span>Sube tu tarea en PDF</span>
                    <input type="file" name="file" accept="application/pdf,.pdf" required>
                </label>
                <button type="submit" class="aula-activity__button">Subir mi tarea</button>
                <small>Solo PDF, máximo 10 MB.</small>
            </form>
        @elseif ($state['submission']?->status !== 'graded' && ($state['error'] ?? null))
            <p class="aula-activity__note">{{ $state['error'] }}</p>
        @endif
    @endif
</div>
