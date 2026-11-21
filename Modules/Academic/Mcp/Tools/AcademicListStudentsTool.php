<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * Listado de estudiantes con matrículas, suscripciones y actividad. Solo
 * lectura: no permite crear, editar ni borrar estudiantes.
 */
#[Name('academic_list_students')]
#[Description('Solo lectura. Lista estudiantes con cantidad de cursos, cursos activos, estado de suscripción, vencimiento y última actividad. Permite buscar por nombre, correo o documento y filtrar por curso o estado de suscripción.')]
#[IsReadOnly]
class AcademicListStudentsTool extends AcademicTool
{
    protected ?string $permission = 'aca_estudiante_listado';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'search' => 'nullable|string|max:120',
            'course_id' => 'nullable|integer|exists:aca_courses,id',
            'subscription_status' => 'nullable|in:todas,activa,por_vencer,vencida,sin_suscripcion',
            'expiring_days' => 'nullable|integer|min:1|max:365',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ], [], [
            'search' => 'búsqueda',
            'course_id' => 'id del curso',
            'subscription_status' => 'estado de suscripción',
            'expiring_days' => 'días para por vencer',
            'page' => 'página',
            'per_page' => 'filas por página',
        ]);

        return $this->json($this->insights()->listStudents($datos));
    }
}
