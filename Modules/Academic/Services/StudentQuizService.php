<?php

namespace Modules\Academic\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaVirtualActivity;
use Modules\Academic\Entities\AcaVirtualActivityQuestion;
use Modules\Academic\Entities\AcaVirtualQuizAttempt;
use Modules\Academic\Entities\AcaVirtualSubmission;

/**
 * Estado de una evaluación para el portal del alumno y registro de sus
 * intentos. El docente decide cuántos intentos permite y cuánto tiempo tiene
 * el alumno para resolver cada uno; la nota vigente es siempre la del último
 * intento terminado.
 */
class StudentQuizService
{
    /** Margen para que llegue el envío automático del navegador. */
    public const SUBMIT_GRACE_SECONDS = 30;

    public function __construct(private VirtualQuizGrader $grader)
    {
    }

    /**
     * Motivo por el que la evaluación no se puede rendir ahora, o null si está
     * disponible. La visibilidad (status) se valida antes, en el controlador.
     */
    public function availabilityError(AcaVirtualActivity $activity): ?string
    {
        if ($activity->type !== 'quiz') {
            return 'Esta actividad no es una evaluación.';
        }

        if ($activity->questions->isEmpty()) {
            return 'La evaluación todavía no tiene preguntas configuradas.';
        }

        $now = now();

        if ($activity->open_at && $now->lt($activity->open_at)) {
            return 'La evaluación estará disponible el ' . $activity->open_at->format('d/m/Y H:i') . '.';
        }

        // El examen se cierra al terminar la disponibilidad, aunque al alumno
        // todavía le quede tiempo de su intento.
        if ($activity->close_at && $now->gt($activity->close_at)) {
            return 'El plazo para rendir esta evaluación terminó.';
        }

        return null;
    }

    /**
     * Resumen que usan la portada del curso, la pantalla del examen y la de
     * resultados. No expone respuestas correctas.
     *
     * @return array<string, mixed>
     */
    public function summary(AcaVirtualActivity $activity, AcaSchoolStudent $student): array
    {
        $attempts = $this->attempts($activity, $student);
        $finished = $attempts
            ->where('status', AcaVirtualQuizAttempt::STATUS_FINISHED)
            ->sortBy('attempt_number')
            ->values();
        $active = $attempts
            ->where('status', AcaVirtualQuizAttempt::STATUS_IN_PROGRESS)
            ->sortByDesc('attempt_number')
            ->first();
        $allowed = max(1, (int) ($activity->attempts_allowed ?? 1));
        $latest = $finished->last();
        $submission = AcaVirtualSubmission::where('activity_id', $activity->id)
            ->where('student_id', $student->id)
            ->first();
        $answers = $latest ? $latest->answers()->get(['is_correct']) : collect();
        $correct = $answers->where('is_correct', true)->count();

        return [
            'allowed' => $allowed,
            'used' => $finished->count(),
            'remaining' => max(0, $allowed - $attempts->count()),
            'next_number' => (int) ($attempts->max('attempt_number') ?? 0) + 1,
            'active' => $active,
            'in_progress' => $active !== null,
            'latest' => $latest,
            'submission' => $submission,
            'score' => $latest?->score !== null ? (float) $latest->score : null,
            'grade_text' => $submission?->grade_text,
            'feedback' => $submission?->feedback,
            'finished_at' => $latest?->finished_at,
            'correct_count' => $correct,
            'incorrect_count' => max(0, $answers->count() - $correct),
            'total_questions' => $answers->count(),
            'can_download' => (bool) $activity->allow_result_download && $latest !== null,
            'error' => $this->availabilityError($activity),
        ];
    }

    /** Intento que sigue abierto para el alumno, si todavía tiene tiempo. */
    public function activeAttempt(AcaVirtualActivity $activity, AcaSchoolStudent $student): ?AcaVirtualQuizAttempt
    {
        return AcaVirtualQuizAttempt::where('activity_id', $activity->id)
            ->where('student_id', $student->id)
            ->where('status', AcaVirtualQuizAttempt::STATUS_IN_PROGRESS)
            ->latest('attempt_number')
            ->first();
    }

    /**
     * Crea el siguiente intento con su orden aleatorio y su hora de cierre.
     * Si ya existe un intento abierto, se reutiliza en lugar de gastar otro.
     */
    public function startAttempt(AcaVirtualActivity $activity, AcaSchoolStudent $student): AcaVirtualQuizAttempt
    {
        $active = $this->activeAttempt($activity, $student);

        if ($active) {
            if (! $this->expireIfNeeded($active)) {
                return $active;
            }
        }

        $summary = $this->summary($activity, $student);

        if ($summary['error']) {
            throw ValidationException::withMessages(['evaluacion' => $summary['error']]);
        }

        if ($summary['remaining'] <= 0) {
            throw ValidationException::withMessages([
                'evaluacion' => 'Ya usaste los ' . $summary['allowed'] . ' intentos de esta evaluación.',
            ]);
        }

        $questions = $activity->questions()->with('options')->get();

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'evaluacion' => 'La evaluación todavía no tiene preguntas configuradas.',
            ]);
        }

        try {
            return DB::transaction(function () use ($activity, $student, $questions) {
                $now = now();
                $duration = max(1, (int) ($activity->duration_minutes ?: 30));
                $expiresAt = $now->copy()->addMinutes($duration);

                // Nunca se puede resolver después de la fecha de cierre del examen.
                if ($activity->close_at && $activity->close_at->lt($expiresAt)) {
                    $expiresAt = $activity->close_at->copy();
                }

                if ($expiresAt->lte($now)) {
                    throw ValidationException::withMessages([
                        'evaluacion' => 'El plazo para rendir esta evaluación terminó.',
                    ]);
                }

                $nextNumber = (int) AcaVirtualQuizAttempt::where('activity_id', $activity->id)
                    ->where('student_id', $student->id)
                    ->max('attempt_number') + 1;

                return AcaVirtualQuizAttempt::create([
                    'activity_id' => $activity->id,
                    'student_id' => $student->id,
                    'attempt_number' => $nextNumber,
                    'status' => AcaVirtualQuizAttempt::STATUS_IN_PROGRESS,
                    'started_at' => $now,
                    'expires_at' => $expiresAt,
                    'question_order' => $questions->pluck('id')->shuffle()->values()->all(),
                    'option_order' => $questions->mapWithKeys(fn ($question) => [
                        $question->id => $question->options->pluck('id')->shuffle()->values()->all(),
                    ])->all(),
                ]);
            });
        } catch (QueryException $exception) {
            // Si dos clics iniciaron el intento al mismo tiempo, se devuelve el
            // que quedó abierto en lugar de mostrar un error al alumno.
            $active = $this->activeAttempt($activity, $student);

            if ($active) {
                return $active;
            }

            throw $exception;
        }
    }

    /**
     * Si el intento ya pasó su hora, lo califica con las respuestas guardadas
     * (o vacías) para que quede cerrado aunque el alumno no haya enviado nada.
     */
    public function expireIfNeeded(AcaVirtualQuizAttempt $attempt): bool
    {
        if (! $attempt->isExpired()) {
            return false;
        }

        $this->grader->grade($attempt, []);

        return true;
    }

    /**
     * Califica un intento abierto. La nota reemplaza a la anterior.
     *
     * @param  array<int, array{question_id?: int|string, option_ids?: array<int, int|string>, answer_text?: string|null}>  $answers
     */
    public function gradeAttempt(AcaVirtualQuizAttempt $attempt, array $answers): AcaVirtualQuizAttempt
    {
        if ($attempt->status !== AcaVirtualQuizAttempt::STATUS_IN_PROGRESS) {
            throw ValidationException::withMessages([
                'evaluacion' => 'Esta evaluación ya fue calificada.',
            ]);
        }

        return $this->grader->grade($attempt, $answers);
    }

    /**
     * Preguntas en el orden que se fijó al iniciar el intento. Si el docente
     * agregó preguntas después, se añaden al final sin romper el orden previo.
     *
     * @return \Illuminate\Support\Collection<int, AcaVirtualActivityQuestion>
     */
    public function orderedQuestions(AcaVirtualActivity $activity, ?AcaVirtualQuizAttempt $attempt)
    {
        $questions = $activity->questions;
        $byId = $questions->keyBy('id');
        $order = collect($attempt?->question_order ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $byId->has($id))
            ->values();

        $ordered = $order
            ->map(fn (int $id) => $byId->get($id))
            ->filter();

        return $ordered
            ->concat(
                $questions
                    ->reject(fn (AcaVirtualActivityQuestion $question) => $order->contains($question->id))
                    ->sortBy('sort_order')
                    ->values()
            )
            ->values();
    }

    /**
     * Alternativas de una pregunta en el orden aleatorio de este intento.
     *
     * @return \Illuminate\Support\Collection<int, \Modules\Academic\Entities\AcaVirtualActivityOption>
     */
    public function orderedOptions(AcaVirtualActivityQuestion $question, ?AcaVirtualQuizAttempt $attempt)
    {
        $options = $question->options;
        $order = collect((array) data_get($attempt?->option_order, (string) $question->id, []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $options->contains('id', $id))
            ->values();

        return $order
            ->map(fn (int $id) => $options->firstWhere('id', $id))
            ->filter()
            ->concat(
                $options
                    ->reject(fn ($option) => $order->contains($option->id))
                    ->sortBy('sort_order')
                    ->values()
            )
            ->values();
    }

    /** Segundos que le quedan al intento, o null si no tiene límite. */
    public function remainingSeconds(?AcaVirtualQuizAttempt $attempt): ?int
    {
        if (! $attempt?->expires_at) {
            return null;
        }

        return max(0, $attempt->expires_at->getTimestamp() - now()->getTimestamp());
    }

    /** Duración total del intento, para la barra de progreso. */
    public function totalSeconds(?AcaVirtualQuizAttempt $attempt): ?int
    {
        if (! $attempt?->expires_at || ! $attempt?->started_at) {
            return null;
        }

        return max(1, $attempt->expires_at->getTimestamp() - $attempt->started_at->getTimestamp());
    }

    /**
     * Nota que se muestra al alumno y en el PDF, según la escala configurada.
     *
     * @param  array<string, mixed>  $summary
     */
    public function gradeLabel(AcaVirtualActivity $activity, array $summary): ?string
    {
        $attempt = $summary['latest'] ?? null;

        if (! $attempt) {
            return null;
        }

        if ($activity->grading_scale === 'qualitative') {
            if ($summary['grade_text'] ?? null) {
                return $summary['grade_text'];
            }

            $total = (float) $attempt->points_total;
            $percentage = $total > 0 ? (float) $attempt->points_earned / $total * 100 : 0.0;

            return $this->grader->qualitativeGrade($percentage);
        }

        $score = number_format((float) ($summary['score'] ?? 0), 2, '.', '');
        $score = rtrim(rtrim($score, '0'), '.');
        $max = rtrim(rtrim(number_format((float) $activity->max_score, 2, '.', ''), '0'), '.');

        return $score . ' / ' . $max;
    }

    private function attempts(AcaVirtualActivity $activity, AcaSchoolStudent $student)
    {
        return AcaVirtualQuizAttempt::where('activity_id', $activity->id)
            ->where('student_id', $student->id)
            ->orderBy('attempt_number')
            ->get();
    }
}
