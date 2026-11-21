<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaExam;

/**
 * Borrado de un examen de un curso en borrador. Las preguntas y respuestas se
 * eliminan en cascada por las claves foráneas.
 */
#[Name('academic_delete_exam')]
#[Description('Borra un examen de contenido, de módulo o final de un curso en borrador, junto con sus preguntas. Exige confirm: true. No sirve para cursos activos.')]
class AcademicDeleteExamTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'exam_id' => 'required|integer',
            'confirm' => 'required|accepted',
        ], [
            'confirm.accepted' => 'Confirma el borrado con confirm: true.',
        ], [
            'exam_id' => 'id del examen',
            'confirm' => 'confirmación',
        ]);

        $examen = AcaExam::find($datos['exam_id']);

        if (! $examen) {
            return Response::error('No existe un examen con ese id.');
        }

        $cursoId = $this->cursoDelExamen($examen);

        if (! $cursoId) {
            throw ValidationException::withMessages([
                'exam_id' => 'El examen no está asociado a un curso editable.',
            ]);
        }

        $this->cursoBorrador($cursoId);

        $resumen = [
            'id' => $examen->id,
            'title' => $examen->description,
            'type' => $examen->is_mock ? 'simulacro' : ($examen->module_id === null ? 'final' : 'regular'),
        ];

        $this->borrarExamen($examen);

        return $this->json([
            'deleted' => true,
            'exam' => $resumen,
        ]);
    }
}
