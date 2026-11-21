<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Academic\Entities\AcaCourse;

/**
 * Revisión previa: devuelve el checklist que necesita una persona para activar
 * el curso. La activación se hace en el ERP, no desde MCP.
 */
#[Name('academic_validate_course')]
#[Description('Revisa un curso antes de activarlo: portada, categoría, modalidad, tipo, fecha, certificado, módulos, clases, contenidos, materiales (archivos y enlaces), exámenes y simulacros. Devuelve un checklist con lo que falta.')]
#[IsReadOnly]
class AcademicValidateCourseTool extends AcademicTool
{
    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer',
        ], [], [
            'course_id' => 'id del curso',
        ]);

        $curso = AcaCourse::find($datos['course_id']);

        if (! $curso) {
            return Response::error('No existe un curso con ese id.');
        }

        $resultado = $this->authoring()->validateCourse($curso);
        $pendientes = array_values(array_filter($resultado['checks'], fn (array $check) => ! $check['ok']));

        return $this->json([
            'course_id' => $curso->id,
            'title' => $curso->description,
            'status' => $curso->status ? 'activo' : 'borrador',
            'ready_for_review' => $resultado['ready'],
            'pending' => $pendientes,
            'checks' => $resultado['checks'],
            'url' => route('aca_courses_information', $curso->id),
            'next_step' => $resultado['ready']
                ? 'El curso está completo. Avisa a la persona responsable para que lo revise y lo active en el ERP; desde MCP no se activa.'
                : 'Corrige los puntos pendientes antes de pedir la activación.',
        ]);
    }
}
