<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Academic\Entities\AcaTeacher;

/**
 * Ficha de solo lectura de un docente: cursos, alumnos y promedios.
 */
#[Name('academic_get_teacher')]
#[Description('Solo lectura. Devuelve un docente con sus cursos, cantidad de alumnos, alumnos activos, avance promedio, exámenes, asistencias y certificados. No devuelve contraseñas ni credenciales.')]
#[IsReadOnly]
class AcademicGetTeacherTool extends AcademicTool
{
    protected ?string $permission = 'aca_docente_listado';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'teacher_id' => 'required|integer|exists:aca_teachers,id',
        ], [], [
            'teacher_id' => 'id del docente',
        ]);

        $docente = AcaTeacher::with('person')->find($datos['teacher_id']);

        if (! $docente) {
            return Response::error('No se encontró el docente.');
        }

        return $this->json($this->insights()->teacherDetail($docente));
    }
}
