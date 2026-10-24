<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolStudent;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Entities\AcaVirtualActivity;
use Modules\Academic\Entities\AcaVirtualQuizAttempt;
use Modules\Academic\Services\VirtualQuizGrader;

/**
 * Flujo del alumno para rendir una evaluación en línea.
 *
 * GET/POST .../activities/{activity}/attempt  → inicia (o retoma) el intento y
 *   devuelve las preguntas sin las respuestas correctas.
 * POST .../attempts/{attempt}/submit          → guarda las respuestas y el
 *   sistema califica automáticamente según la configuración del docente.
 *
 * Solo puede rendir un alumno matriculado en la sección del curso y en el año
 * del mismo; el intento queda ligado a su identidad de estudiante, nunca a un
 * id enviado por el navegador.
 */
class AcaVirtualQuizAttemptController extends Controller
{
    public function __construct(private VirtualQuizGrader $grader) {}

    public function start(Request $request, int $activityId)
    {
        $activity = $this->resolveActivity($activityId);
        $student = $this->resolveStudent($activity);

        // Si el alumno dejó un intento sin terminar, se retoma. Si no, se crea
        // el siguiente siempre que le queden intentos habilitados por el docente.
        $attempt = AcaVirtualQuizAttempt::where('activity_id', $activity->id)
            ->where('student_id', $student->id)
            ->where('status', AcaVirtualQuizAttempt::STATUS_IN_PROGRESS)
            ->latest('attempt_number')
            ->first();

        if (! $attempt) {
            $used = AcaVirtualQuizAttempt::where('activity_id', $activity->id)
                ->where('student_id', $student->id)
                ->count();
            $allowed = max(1, (int) ($activity->attempts_allowed ?? 1));

            if ($used >= $allowed) {
                return response()->json([
                    'message' => 'Ya usaste todos los intentos de esta evaluación.',
                ], 409);
            }

            $attempt = AcaVirtualQuizAttempt::create([
                'activity_id' => $activity->id,
                'student_id' => $student->id,
                'attempt_number' => $used + 1,
                'status' => AcaVirtualQuizAttempt::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);
        }

        return response()->json([
            'attempt' => $this->attemptPayload($attempt),
            'activity' => [
                'id' => $activity->id,
                'title' => $activity->title,
                'instructions' => $activity->instructions,
                'max_score' => (float) $activity->max_score,
                'close_at' => $activity->close_at?->toIso8601String(),
            ],
            'questions' => $activity->questions->map(fn ($question) => [
                'id' => $question->id,
                'type' => $question->type,
                'question' => $question->question,
                'points' => (float) $question->points,
                'options' => $question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'text' => $option->text,
                ])->values(),
            ])->values(),
        ]);
    }

    public function submit(Request $request, int $attemptId)
    {
        $attempt = AcaVirtualQuizAttempt::with('activity.course')->findOrFail($attemptId);
        $activity = $this->resolveActivity($attempt->activity_id);
        $student = $this->resolveStudent($activity);

        if ((int) $attempt->student_id !== (int) $student->id) {
            abort(403);
        }

        if ($attempt->status === AcaVirtualQuizAttempt::STATUS_FINISHED) {
            return response()->json(['message' => 'Esta evaluación ya fue calificada.'], 409);
        }

        $validated = $request->validate([
            'answers' => 'nullable|array',
            'answers.*.question_id' => 'required|integer',
            'answers.*.option_ids' => 'nullable|array',
            'answers.*.option_ids.*' => 'integer',
            'answers.*.answer_text' => 'nullable|string|max:2000',
        ]);

        $graded = $this->grader->grade($attempt, $validated['answers'] ?? []);

        return response()->json([
            'message' => 'Evaluación calificada automáticamente.',
            'attempt' => $this->attemptPayload($graded),
            'attempts_allowed' => max(1, (int) ($activity->attempts_allowed ?? 1)),
            'grade_text' => $graded->score !== null && $activity->grading_scale === 'qualitative'
                ? $this->grader->qualitativeGrade(
                    (float) $graded->points_total > 0
                        ? (float) $graded->points_earned / (float) $graded->points_total * 100
                        : 0
                )
                : null,
        ]);
    }

    private function attemptPayload(AcaVirtualQuizAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'attempt_number' => (int) ($attempt->attempt_number ?? 1),
            'status' => $attempt->status,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'finished_at' => $attempt->finished_at?->toIso8601String(),
            'points_earned' => (float) $attempt->points_earned,
            'points_total' => (float) $attempt->points_total,
            'score' => $attempt->score !== null ? (float) $attempt->score : null,
        ];
    }

    /** Carga la evaluación y valida que esté disponible para rendirse. */
    private function resolveActivity(int $activityId): AcaVirtualActivity
    {
        $activity = AcaVirtualActivity::with(['course', 'questions.options'])->findOrFail($activityId);
        $course = $activity->course;

        abort_unless($activity->type === 'quiz' && $activity->status, 404);

        $activeYear = AcaSchoolYear::where('school_id', $course->school_id)
            ->where('status', AcaSchoolYear::STATUS_ACTIVE)
            ->first();

        abort_unless($activeYear && (int) $course->year_id === (int) $activeYear->id, 403, 'Esta evaluación no está disponible.');

        if ($activity->questions->isEmpty()) {
            throw ValidationException::withMessages([
                'activity' => 'La evaluación todavía no tiene preguntas configuradas.',
            ]);
        }

        $now = now();

        if ($activity->open_at && $now->lt($activity->open_at)) {
            throw ValidationException::withMessages([
                'activity' => 'La evaluación aún no está habilitada.',
            ]);
        }

        if ($activity->close_at && $now->gt($activity->close_at) && ! $activity->allow_late) {
            throw ValidationException::withMessages([
                'activity' => 'El plazo para rendir la evaluación terminó.',
            ]);
        }

        return $activity;
    }

    /** Identifica al alumno por su usuario autenticado y comprueba su matrícula. */
    private function resolveStudent(AcaVirtualActivity $activity): AcaSchoolStudent
    {
        $personId = Auth::user()?->person_id;

        abort_unless($personId, 403, 'Solo los estudiantes pueden rendir evaluaciones.');

        $student = AcaSchoolStudent::where('school_id', $activity->course->school_id)
            ->where('person_id', $personId)
            ->first();

        abort_unless($student, 403, 'Solo los estudiantes pueden rendir evaluaciones.');

        $enrolled = AcaSchoolEnrollment::where('student_id', $student->id)
            ->where('section_id', $activity->course->section_id)
            ->where('year_id', $activity->course->year_id)
            ->where('status', AcaSchoolEnrollment::STATUS_ACTIVO)
            ->exists();

        abort_unless($enrolled, 403, 'No estás matriculado en la sección de esta evaluación.');

        return $student;
    }
}
