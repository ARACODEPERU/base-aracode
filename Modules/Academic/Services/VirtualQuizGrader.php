<?php

namespace Modules\Academic\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Entities\AcaVirtualActivity;
use Modules\Academic\Entities\AcaVirtualActivityQuestion;
use Modules\Academic\Entities\AcaVirtualQuizAnswer;
use Modules\Academic\Entities\AcaVirtualQuizAttempt;
use Modules\Academic\Entities\AcaVirtualSubmission;

/**
 * Califica una evaluación en línea comparando las respuestas del alumno con
 * las respuestas correctas que configuró el docente.
 *
 * Reglas:
 * - Alternativa única y verdadero/falso: se debe elegir exactamente la opción correcta.
 * - Alternativa múltiple: se deben marcar todas las correctas y ninguna incorrecta.
 * - Respuesta abierta: se acepta cualquiera de las respuestas configuradas,
 *   sin distinguir mayúsculas, tildes ni espacios repetidos.
 */
class VirtualQuizGrader
{
    /** Normaliza un texto libre para poder compararlo. */
    public function normalize(?string $text): string
    {
        $text = Str::ascii(mb_strtolower(trim((string) $text)));
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    /**
     * Respuestas aceptadas de una pregunta abierta (separadas por "|" o ";").
     *
     * @return array<int, string>
     */
    public function acceptedAnswers(AcaVirtualActivityQuestion $question): array
    {
        return collect(preg_split('/[|;]/', (string) $question->answer_text) ?: [])
            ->map(fn ($answer) => $this->normalize($answer))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Indica si la respuesta del alumno es correcta.
     *
     * @param  array<int, int|string>  $optionIds
     */
    public function isCorrect(AcaVirtualActivityQuestion $question, array $optionIds, ?string $answerText): bool
    {
        if ($question->type === AcaVirtualActivityQuestion::TYPE_SHORT) {
            $answer = $this->normalize($answerText);

            return $answer !== '' && in_array($answer, $this->acceptedAnswers($question), true);
        }

        $selected = collect($optionIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        if ($selected->isEmpty()) {
            return false;
        }

        $correct = $question->options
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        if ($question->type === AcaVirtualActivityQuestion::TYPE_MULTIPLE) {
            return $correct->isNotEmpty() && $selected->all() === $correct->all();
        }

        // Alternativa única y verdadero/falso.
        return $selected->count() === 1
            && $correct->count() === 1
            && $selected->first() === $correct->first();
    }

    /** Nota literal del CNEB a partir del porcentaje de logro. */
    public function qualitativeGrade(float $percentage): string
    {
        return match (true) {
            $percentage >= 85 => 'AD',
            $percentage >= 70 => 'A',
            $percentage >= 50 => 'B',
            default => 'C',
        };
    }

    /**
     * Califica el intento, guarda las respuestas y registra la nota del alumno.
     *
     * @param  array<int, array{question_id?: int|string, option_ids?: array<int, int|string>, answer_text?: string|null}>  $answers
     */
    public function grade(AcaVirtualQuizAttempt $attempt, array $answers): AcaVirtualQuizAttempt
    {
        $activity = $attempt->activity()->firstOrFail();
        $questions = $activity->questions()->with('options')->get();
        $answered = collect($answers)->keyBy(fn ($answer) => (int) ($answer['question_id'] ?? 0));

        $rows = $questions->map(function (AcaVirtualActivityQuestion $question) use ($answered) {
            $answer = $answered->get($question->id);
            $optionIds = collect($answer['option_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $question->options->contains('id', $id))
                ->values()
                ->all();
            $answerText = $answer['answer_text'] ?? null;

            $isCorrect = $answer !== null && $this->isCorrect($question, $optionIds, $answerText);

            return [
                'question_id' => $question->id,
                'option_ids' => $question->isChoice() ? $optionIds : null,
                'answer_text' => $question->type === AcaVirtualActivityQuestion::TYPE_SHORT ? $answerText : null,
                'points_earned' => $isCorrect ? (float) $question->points : 0.0,
                'is_correct' => $isCorrect,
            ];
        });

        DB::transaction(function () use ($attempt, $rows, $activity, $questions) {
            $attempt->answers()->delete();

            foreach ($rows as $row) {
                AcaVirtualQuizAnswer::create(['attempt_id' => $attempt->id] + $row);
            }

            $this->syncAttempt($attempt, $activity, $questions, $attempt->answers()->get(), null, false);
        });

        return $attempt->fresh();
    }

    /**
     * Revisión manual del docente sobre las respuestas abiertas: ajusta los
     * puntos de cada una y recalcula la nota del intento y la entrega.
     *
     * @param  array<int, array{question_id?: int|string, points_earned?: numeric|string|null}>  $reviews
     */
    public function reviewOpenAnswers(AcaVirtualQuizAttempt $attempt, ?int $personId, array $reviews): AcaVirtualQuizAttempt
    {
        $activity = $attempt->activity()->firstOrFail();
        $questions = $activity->questions()->with('options')->get();
        $openQuestions = $questions
            ->where('type', AcaVirtualActivityQuestion::TYPE_SHORT)
            ->keyBy('id');
        $reviewed = collect($reviews)->keyBy(fn ($row) => (int) ($row['question_id'] ?? 0));

        if ($openQuestions->isEmpty()) {
            throw ValidationException::withMessages([
                'answers' => 'Esta evaluación no tiene respuestas abiertas para revisar.',
            ]);
        }

        $answers = $attempt->answers()->get();

        if (! $answers->contains(fn ($answer) => $openQuestions->has($answer->question_id))) {
            throw ValidationException::withMessages([
                'answers' => 'El estudiante no tiene respuestas abiertas en esta evaluación.',
            ]);
        }

        DB::transaction(function () use ($attempt, $activity, $questions, $openQuestions, $reviewed, $personId) {
            foreach ($attempt->answers()->get() as $answer) {
                $question = $openQuestions->get($answer->question_id);

                if (! $question || ! $reviewed->has($answer->question_id)) {
                    continue;
                }

                $points = round((float) ($reviewed->get($answer->question_id)['points_earned'] ?? 0), 2);
                $points = max(0, min($points, (float) $question->points));

                $answer->update([
                    'points_earned' => $points,
                    'is_correct' => $points >= (float) $question->points,
                    'manually_graded' => true,
                    'graded_by_person_id' => $personId,
                ]);
            }

            $this->syncAttempt($attempt, $activity, $questions, $attempt->answers()->get(), $personId, true);
        });

        return $attempt->fresh();
    }

    /**
     * Recalcula puntos, nota y entrega a partir de las respuestas guardadas.
     * La revisión manual conserva la fecha original de entrega del alumno.
     *
     * @param  \Illuminate\Support\Collection<int, AcaVirtualQuizAnswer>  $answers
     */
    private function syncAttempt(
        AcaVirtualQuizAttempt $attempt,
        AcaVirtualActivity $activity,
        $questions,
        $answers,
        ?int $reviewerPersonId,
        bool $manual
    ): void {
        $pointsTotal = round((float) $questions->sum('points'), 2);
        $pointsEarned = round((float) $answers->sum(fn ($answer) => (float) $answer->points_earned), 2);
        $correctCount = $answers->filter(fn ($answer) => (bool) $answer->is_correct)->count();
        $score = $pointsTotal > 0
            ? round($pointsEarned / $pointsTotal * (float) $activity->max_score, 2)
            : 0.0;
        $percentage = $pointsTotal > 0 ? $pointsEarned / $pointsTotal * 100 : 0.0;
        $finishedAt = $attempt->finished_at ?? now();

        $feedback = sprintf(
            '%s: %d de %d respuestas correctas (%s de %s puntos).',
            $manual ? 'Revisión manual del docente' : 'Calificación automática',
            $correctCount,
            $questions->count(),
            rtrim(rtrim(number_format($pointsEarned, 2, '.', ''), '0'), '.'),
            rtrim(rtrim(number_format($pointsTotal, 2, '.', ''), '0'), '.')
        );

        $attempt->update([
            'status' => AcaVirtualQuizAttempt::STATUS_FINISHED,
            'finished_at' => $finishedAt,
            'points_earned' => $pointsEarned,
            'points_total' => $pointsTotal,
            'score' => $score,
        ]);

        AcaVirtualSubmission::updateOrCreate(
            [
                'activity_id' => $activity->id,
                'student_id' => $attempt->student_id,
            ],
            [
                'content' => null,
                'status' => 'graded',
                'score' => $score,
                'grade_text' => $activity->grading_scale === 'qualitative'
                    ? $this->qualitativeGrade($percentage)
                    : null,
                'feedback' => $feedback,
                'submitted_at' => $finishedAt,
                'graded_at' => now(),
                'graded_by_person_id' => $reviewerPersonId,
            ]
        );
    }
}
