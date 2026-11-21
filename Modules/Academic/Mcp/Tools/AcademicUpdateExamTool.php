<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaExam;

/**
 * Corrección de un examen existente mientras el curso siga en borrador:
 * título, fechas, duración o intentos. No toca las preguntas.
 */
#[Name('academic_update_exam')]
#[Description('Corrige título, fechas, duración o intentos de un examen de contenido, de módulo o final de un curso en borrador. Envía solo lo que cambia. Para las preguntas usa academic_add_exam_questions.')]
class AcademicUpdateExamTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'exam_id' => 'required|integer',
            'title' => 'sometimes|required|string|max:255',
            'date_start' => 'sometimes|nullable|date',
            'date_end' => 'sometimes|nullable|date',
            'duration_minutes' => 'sometimes|integer|min:5|max:600',
            'attempts' => 'sometimes|integer|min:1|max:10',
        ], [], [
            'exam_id' => 'id del examen',
            'title' => 'nombre del examen',
            'date_start' => 'fecha de inicio',
            'date_end' => 'fecha de fin',
            'duration_minutes' => 'duración en minutos',
            'attempts' => 'intentos',
        ]);

        $examen = AcaExam::find($datos['exam_id']);

        if (! $examen) {
            throw ValidationException::withMessages(['exam_id' => 'No existe un examen con ese id.']);
        }

        $cursoId = $this->cursoDelExamen($examen);

        if (! $cursoId) {
            throw ValidationException::withMessages([
                'exam_id' => 'El examen no está asociado a un curso editable.',
            ]);
        }

        $this->cursoBorrador($cursoId);

        $cambios = [];

        foreach (['date_start', 'date_end', 'duration_minutes', 'attempts'] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $cambios[$campo] = $datos[$campo];
            }
        }

        if (array_key_exists('title', $datos)) {
            $cambios['description'] = trim((string) $datos['title']);
        }

        if ($cambios === []) {
            throw ValidationException::withMessages(['exam_id' => 'No enviaste ningún campo para actualizar.']);
        }

        $inicio = array_key_exists('date_start', $cambios) ? $cambios['date_start'] : $examen->date_start;
        $fin = array_key_exists('date_end', $cambios) ? $cambios['date_end'] : $examen->date_end;

        if ($inicio && $fin && strtotime((string) $fin) < strtotime((string) $inicio)) {
            throw ValidationException::withMessages([
                'date_end' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
            ]);
        }

        $examen->update($cambios);
        $examen = $examen->fresh();

        return $this->json([
            'exam' => [
                'id' => $examen->id,
                'title' => $examen->description,
                'date_start' => $examen->date_start ? (string) $examen->date_start : null,
                'date_end' => $examen->date_end ? (string) $examen->date_end : null,
                'duration_minutes' => (int) $examen->duration_minutes,
                'attempts' => (int) $examen->attempts,
                'questions' => $examen->questions()->count(),
            ],
            'updated_fields' => array_keys($cambios),
        ]);
    }
}
