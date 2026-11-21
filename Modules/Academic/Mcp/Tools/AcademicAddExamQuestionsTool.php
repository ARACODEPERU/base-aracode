<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\AcaExamQuestion;

/**
 * Preguntas del examen con sus opciones. Respeta los tipos reales del ERP:
 * Escribir, Alternativas, Varias respuestas y Subir Archivo.
 */
#[Name('academic_add_exam_questions')]
#[Description('Agrega preguntas en lote a un examen. type: "Alternativas" (exactamente una opción correcta), "Varias respuestas" (una o más), "Escribir" o "Subir Archivo" (sin opciones). La suma de puntajes correctos no puede superar el score de la pregunta.')]
class AcademicAddExamQuestionsTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'exam_id' => 'required|integer',
            'questions' => 'required|array|min:1|max:50',
            'questions.*.statement' => 'required|string|max:5200',
            'questions.*.score' => 'required|integer|min:1|max:100',
            'questions.*.type' => ['required', Rule::in(['Escribir', 'Alternativas', 'Varias respuestas', 'Subir Archivo'])],
            'questions.*.options' => 'nullable|array|max:10',
            'questions.*.options.*.text' => 'required|string|max:300',
            'questions.*.options.*.correct' => 'nullable|boolean',
            'questions.*.options.*.score' => 'nullable|integer|min:0|max:100',
        ], [
            'questions.*.type.in' => 'Tipo de pregunta no válido: usa Escribir, Alternativas, Varias respuestas o Subir Archivo.',
        ], [
            'exam_id' => 'id del examen',
            'questions' => 'preguntas',
            'questions.*.statement' => 'enunciado',
            'questions.*.score' => 'puntaje',
            'questions.*.type' => 'tipo',
            'questions.*.options' => 'opciones',
        ]);

        $examen = AcaExam::find($datos['exam_id']);

        if (! $examen) {
            throw ValidationException::withMessages(['exam_id' => 'No existe un examen con ese id.']);
        }

        // El examen hereda el curso desde su contenido.
        $cursoId = $examen->course_id ?: AcaContent::with('theme.module')->find($examen->content_id)?->theme?->module?->course_id;

        if (! $cursoId) {
            throw ValidationException::withMessages([
                'exam_id' => 'El examen no está asociado a un curso, así que no se pueden agregar preguntas desde MCP.',
            ]);
        }

        $this->cursoBorrador((int) $cursoId);

        $creadas = $this->authoring()->addExamQuestions($examen, $datos['questions']);

        return $this->json([
            'exam_id' => $examen->id,
            'questions' => array_map(fn (AcaExamQuestion $pregunta) => [
                'id' => $pregunta->id,
                'statement' => mb_substr((string) $pregunta->description, 0, 120),
                'score' => $pregunta->score,
                'type' => $pregunta->type_answers,
                'answers' => $pregunta->answers()->count(),
            ], $creadas),
            'next_step' => 'Cuando el curso esté completo llama a academic_validate_course y avisa a la persona responsable para que lo active en el ERP.',
        ]);
    }
}
