<?php

namespace Modules\Academic\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Entities\AcaSchoolYear;
use Modules\Academic\Entities\AcaVirtualActivity;
use Modules\Academic\Entities\AcaVirtualActivityOption;
use Modules\Academic\Entities\AcaVirtualActivityQuestion;
use Modules\Academic\Services\SchoolContextService;

/**
 * Configuración de preguntas y respuestas de una evaluación en línea.
 * Cada pregunta indica cuánto vale y cuál (o cuáles) alternativa es correcta,
 * datos con los que el sistema califica automáticamente al alumno.
 */
class AcaVirtualQuizQuestionController extends Controller
{
    public function __construct(private SchoolContextService $context) {}

    public function store(Request $request, int $activityId)
    {
        $activity = AcaVirtualActivity::with('course')->findOrFail($activityId);
        $this->ensureEditable($activity);

        [$validated, $options] = $this->validatePayload($request, $activity);
        $maxOrder = AcaVirtualActivityQuestion::where('activity_id', $activity->id)->max('sort_order');

        DB::transaction(function () use ($activity, $validated, $options, $maxOrder) {
            $question = AcaVirtualActivityQuestion::create([
                'activity_id' => $activity->id,
                'type' => $validated['type'],
                'question' => $validated['question'],
                'points' => $validated['points'],
                'answer_text' => $validated['type'] === AcaVirtualActivityQuestion::TYPE_SHORT
                    ? $validated['answer_text']
                    : null,
                'sort_order' => ($maxOrder ?? -1) + 1,
            ]);

            $this->syncOptions($question, $options);
        });

        return back()->with('message', 'Pregunta agregada correctamente.');
    }

    public function update(Request $request, int $questionId)
    {
        $question = AcaVirtualActivityQuestion::with('activity.course')->findOrFail($questionId);
        $this->ensureEditable($question->activity);

        [$validated, $options] = $this->validatePayload($request, $question->activity, $question);

        DB::transaction(function () use ($question, $validated, $options) {
            $question->update([
                'type' => $validated['type'],
                'question' => $validated['question'],
                'points' => $validated['points'],
                'answer_text' => $validated['type'] === AcaVirtualActivityQuestion::TYPE_SHORT
                    ? $validated['answer_text']
                    : null,
            ]);

            $question->options()->delete();
            $this->syncOptions($question, $options);
        });

        return back()->with('message', 'Pregunta actualizada correctamente.');
    }

    public function destroy(Request $request, int $questionId)
    {
        $question = AcaVirtualActivityQuestion::with('activity.course')->findOrFail($questionId);
        $this->ensureEditable($question->activity);
        $question->delete();

        return back()->with('message', 'Pregunta eliminada correctamente.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{text: mixed, is_correct?: mixed}>  $options
     */
    private function syncOptions(AcaVirtualActivityQuestion $question, $options): void
    {
        if (! $question->isChoice()) {
            return;
        }

        foreach ($options->values() as $index => $option) {
            AcaVirtualActivityOption::create([
                'question_id' => $question->id,
                'text' => trim((string) $option['text']),
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Valida la pregunta y que sus puntos no sobrepasen la nota de la evaluación.
     *
     * @return array{0: array<string, mixed>, 1: \Illuminate\Support\Collection<int, array<string, mixed>>}
     */
    private function validatePayload(
        Request $request,
        AcaVirtualActivity $activity,
        ?AcaVirtualActivityQuestion $ignore = null
    ): array {
        $this->ensurePointsFit($activity, (float) $request->input('points', 0), $ignore);

        $validated = $request->validate([
            'type' => ['required', Rule::in([
                AcaVirtualActivityQuestion::TYPE_SINGLE,
                AcaVirtualActivityQuestion::TYPE_MULTIPLE,
                AcaVirtualActivityQuestion::TYPE_TRUE_FALSE,
                AcaVirtualActivityQuestion::TYPE_SHORT,
            ])],
            'question' => 'required|string|max:2000',
            'points' => 'required|numeric|min:0.01|max:100',
            'answer_text' => 'nullable|string|max:2000',
            'options' => 'nullable|array|max:10',
            'options.*.text' => 'nullable|string|max:500',
            'options.*.is_correct' => 'nullable|boolean',
        ], [
            'question.required' => 'Escribe el enunciado de la pregunta.',
            'points.required' => 'Indica cuántos puntos vale la pregunta.',
        ]);

        $options = collect($validated['options'] ?? [])
            ->filter(fn ($option) => filled($option['text'] ?? null))
            ->values();

        if ($validated['type'] === AcaVirtualActivityQuestion::TYPE_SHORT) {
            if (blank($validated['answer_text'] ?? null)) {
                throw ValidationException::withMessages([
                    'answer_text' => 'Escribe al menos una respuesta aceptada para calificar automáticamente.',
                ]);
            }

            return [$validated, collect()];
        }

        if ($options->count() < 2) {
            throw ValidationException::withMessages([
                'options' => 'Agrega al menos dos alternativas de respuesta.',
            ]);
        }

        $correct = $options->filter(fn ($option) => ! empty($option['is_correct']))->count();

        if ($validated['type'] === AcaVirtualActivityQuestion::TYPE_MULTIPLE && $correct < 1) {
            throw ValidationException::withMessages([
                'options' => 'Marca al menos una alternativa correcta.',
            ]);
        }

        if (in_array($validated['type'], [AcaVirtualActivityQuestion::TYPE_SINGLE, AcaVirtualActivityQuestion::TYPE_TRUE_FALSE], true) && $correct !== 1) {
            throw ValidationException::withMessages([
                'options' => 'Marca una sola alternativa correcta.',
            ]);
        }

        return [$validated, $options];
    }

    /**
     * La suma de puntos de las preguntas no puede superar la nota de la
     * evaluación: al llegar al máximo ya no se admiten más preguntas y una
     * pregunta nueva tampoco puede pasarse del límite.
     */
    private function ensurePointsFit(AcaVirtualActivity $activity, float $points, ?AcaVirtualActivityQuestion $ignore = null): void
    {
        $limit = round((float) $activity->max_score, 2);

        if ($limit <= 0) {
            return;
        }

        $assigned = round((float) AcaVirtualActivityQuestion::where('activity_id', $activity->id)
            ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore->id))
            ->sum('points'), 2);

        $available = round($limit - $assigned, 2);

        if ($available <= 0) {
            throw ValidationException::withMessages([
                'points' => sprintf(
                    'La evaluación ya tiene asignados sus %s puntos. Para agregar otra pregunta, ajusta los puntos de las preguntas existentes o cambia la nota máxima de la actividad.',
                    $this->formatPoints($limit)
                ),
            ]);
        }

        if (round($points, 2) > $available) {
            throw ValidationException::withMessages([
                'points' => sprintf(
                    'La evaluación admite %s puntos como máximo y las demás preguntas ya suman %s. Esta pregunta puede valer hasta %s puntos.',
                    $this->formatPoints($limit),
                    $this->formatPoints($assigned),
                    $this->formatPoints($available)
                ),
            ]);
        }
    }

    private function formatPoints(float $points): string
    {
        return rtrim(rtrim(number_format($points, 2, '.', ''), '0'), '.');
    }

    /** Solo el año vigente y el docente del curso (o quien configura el aula) editan preguntas. */
    private function ensureEditable(AcaVirtualActivity $activity): void
    {
        $course = $activity->course;
        $school = $this->context->currentSchool();
        $user = Auth::user();

        if (! $school || ! $course || (int) $course->school_id !== (int) $school->id) {
            abort(403);
        }

        $activeYear = AcaSchoolYear::where('school_id', $school->id)
            ->where('status', AcaSchoolYear::STATUS_ACTIVE)
            ->first();

        if (! $activeYear || (int) $course->year_id !== (int) $activeYear->id) {
            abort(403, 'Solo el año vigente admite cambios.');
        }

        if (! $user?->can('aca_virtual_actividad_gestionar')) {
            abort(403);
        }

        if (! $user->can('aca_virtual_configurar') && (int) $course->teacher_person_id !== (int) $user->person_id) {
            abort(403);
        }
    }
}
