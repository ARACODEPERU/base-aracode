<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaExamQuestion;
use Modules\Academic\Services\CourseAuthoringService;

/**
 * Examen final del curso: se cuelga del curso (sin módulo ni contenido), como
 * el que se configura desde el mantenedor. Queda inactivo hasta que una persona
 * active el curso.
 */
#[Name('academic_create_final_exam')]
#[Description('Crea o actualiza el examen final del curso (sin módulo). Envía course_id, title, date_start, date_end, duration_minutes y, si ya las tienes, las preguntas en questions. Queda inactivo hasta que una persona active el curso.')]
class AcademicCreateFinalExamTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_examen_final_crear';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer',
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
            'course_id' => 'id del curso',
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

        $curso = AcaCourse::find($datos['course_id']);

        if (! $curso) {
            throw ValidationException::withMessages([
                'course_id' => 'No existe un curso con ese id.',
            ]);
        }

        $this->cursoBorrador($curso->id);

        [$examen, $creado] = $this->authoring()->createFinalExam($curso, $datos);

        $preguntas = [];
        $omitidas = false;

        if (! empty($datos['questions'])) {
            if ($examen->questions()->exists()) {
                // Reintento del examen final: no se duplican sus preguntas.
                $omitidas = true;
            } else {
                $preguntas = $this->authoring()->addExamQuestions($examen, $datos['questions']);
            }
        }

        return $this->json([
            'exam' => [
                'id' => $examen->id,
                'title' => $examen->description,
                'course_id' => $examen->course_id,
                'type' => 'final',
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
            'next_step' => 'Una persona revisa y activa el curso y sus exámenes en el ERP; desde MCP no se activa.',
        ]);
    }
}
