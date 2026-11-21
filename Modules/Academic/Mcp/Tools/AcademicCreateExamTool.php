<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaContent;

/**
 * Examen regular de un contenido. En el ERP el examen cuelga del contenido y
 * hereda curso, módulo y clase; aquí se respeta esa estructura.
 */
#[Name('academic_create_exam')]
#[Description('Crea el examen de un contenido (id de academic_set_contents). Queda inactivo hasta que una persona lo revise. Luego agrega las preguntas con academic_add_exam_questions.')]
class AcademicCreateExamTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'content_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'attempts' => 'nullable|integer|min:1|max:10',
            'duration_minutes' => 'nullable|integer|min:5|max:600',
        ], [], [
            'content_id' => 'id del contenido',
            'title' => 'nombre del examen',
            'date_start' => 'fecha de inicio',
            'date_end' => 'fecha de fin',
            'attempts' => 'intentos',
            'duration_minutes' => 'duración en minutos',
        ]);

        $contenido = AcaContent::with('theme.module')->find($datos['content_id']);

        if (! $contenido || ! $contenido->theme || ! $contenido->theme->module) {
            throw ValidationException::withMessages([
                'content_id' => 'No existe un contenido con ese id, o no tiene clase y módulo.',
            ]);
        }

        $this->cursoBorrador((int) $contenido->theme->module->course_id);

        if ($contenido->exam()->exists()) {
            throw ValidationException::withMessages([
                'content_id' => 'Ese contenido ya tiene un examen. Usa academic_add_exam_questions con el examen existente.',
            ]);
        }

        $examen = $this->authoring()->createExam($contenido, $datos);

        return $this->json([
            'exam' => [
                'id' => $examen->id,
                'title' => $examen->description,
                'content_id' => $examen->content_id,
                'status' => 'inactivo',
                'attempts' => $examen->attempts,
                'duration_minutes' => $examen->duration_minutes,
            ],
            'next_step' => 'Agrega las preguntas con academic_add_exam_questions usando exam_id = ' . $examen->id . '.',
        ]);
    }
}
