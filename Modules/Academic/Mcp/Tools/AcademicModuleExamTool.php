<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\AcaExamQuestion;
use Modules\Academic\Entities\AcaModule;

/**
 * Base de los exámenes por módulo. El ERP distingue dos exámenes por módulo:
 * el regular (cuenta para la nota) y el simulacro (is_mock, no afecta el
 * promedio). Ambos se crean con la misma estructura y aquí solo cambia el tipo.
 */
abstract class AcademicModuleExamTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    /**
     * Indica si la herramienta crea un simulacro en lugar del examen regular.
     */
    abstract protected function esSimulacro(): bool;

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'module_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'duration_minutes' => 'required|integer|min:5|max:600',
            'attempts' => 'nullable|integer|min:1|max:10',
            'questions' => 'nullable|array|min:1|max:50',
            'questions.*.statement' => 'required|string|max:5200',
            'questions.*.score' => 'required|integer|min:1|max:100',
            'questions.*.type' => 'required|in:Escribir,Alternativas,Varias respuestas,Subir Archivo',
            'questions.*.options' => 'nullable|array|max:10',
            'questions.*.options.*.text' => 'required|string|max:300',
            'questions.*.options.*.correct' => 'nullable|boolean',
            'questions.*.options.*.score' => 'nullable|integer|min:0|max:100',
        ], [
            'questions.*.type.in' => 'Tipo de pregunta no válido: usa Escribir, Alternativas, Varias respuestas o Subir Archivo.',
        ], [
            'module_id' => 'id del módulo',
            'title' => 'nombre del examen',
            'date_start' => 'fecha de inicio',
            'date_end' => 'fecha de fin',
            'duration_minutes' => 'duración en minutos',
            'attempts' => 'intentos',
            'questions' => 'preguntas',
            'questions.*.statement' => 'enunciado',
            'questions.*.score' => 'puntaje',
            'questions.*.type' => 'tipo',
            'questions.*.options' => 'opciones',
        ]);

        $modulo = AcaModule::with('course')->find($datos['module_id']);

        if (! $modulo || ! $modulo->course) {
            throw ValidationException::withMessages([
                'module_id' => 'No existe un módulo con ese id, o no tiene curso.',
            ]);
        }

        $this->cursoBorrador((int) $modulo->course_id);

        [$examen, $creado] = $this->authoring()->createModuleExam($modulo, $datos, $this->esSimulacro());

        $preguntas = [];
        $omitidas = false;

        if (! empty($datos['questions'])) {
            if ($examen->questions()->exists()) {
                // Reintento del mismo examen: se actualizan sus datos, pero no
                // se vuelven a insertar las preguntas. Para agregar más se usa
                // academic_add_exam_questions.
                $omitidas = true;
            } else {
                $preguntas = $this->authoring()->addExamQuestions($examen, $datos['questions']);
            }
        }

        return $this->json([
            'exam' => [
                'id' => $examen->id,
                'title' => $examen->description,
                'module_id' => $examen->module_id,
                'type' => $this->esSimulacro() ? 'simulacro' : 'regular',
                'status' => 'inactivo',
                'date_start' => $examen->date_start ? (string) $examen->date_start : null,
                'date_end' => $examen->date_end ? (string) $examen->date_end : null,
                'duration_minutes' => (int) $examen->duration_minutes,
                'attempts' => (int) $examen->attempts,
                'questions' => $examen->questions()->count(),
            ],
            'created' => $creado,
            'questions_skipped' => $omitidas
                ? 'El examen ya tenía preguntas: no se duplicaron. Usa academic_add_exam_questions para agregar más.'
                : null,
            'questions_added' => array_map(fn (AcaExamQuestion $pregunta) => [
                'id' => $pregunta->id,
                'statement' => mb_substr((string) $pregunta->description, 0, 120),
                'score' => $pregunta->score,
                'type' => $pregunta->type_answers,
                'answers' => $pregunta->answers()->count(),
            ], $preguntas),
            'next_step' => 'Revisa las preguntas con academic_get_course. Una persona activa el curso y sus exámenes en el ERP.',
        ]);
    }
}
