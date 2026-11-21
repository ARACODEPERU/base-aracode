<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * Asigna el equipo docente del curso y marca quién es el encargado principal.
 * Los ids siempre se validan contra aca_teachers.
 */
#[Name('academic_set_course_teachers')]
#[Description('Reemplaza los docentes asignados a un curso en borrador y define el docente encargado (main_teacher_id). Consulta primero academic_list_teachers para usar ids reales.')]
class AcademicSetCourseTeachersTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer',
            'teacher_ids' => 'required|array|max:20',
            'teacher_ids.*' => 'integer|exists:aca_teachers,id',
            'main_teacher_id' => 'nullable|integer|exists:aca_teachers,id',
        ], [], [
            'course_id' => 'id del curso',
            'teacher_ids' => 'docentes del curso',
            'main_teacher_id' => 'docente encargado',
        ]);

        $curso = $this->cursoBorrador((int) $datos['course_id']);
        $docentes = $this->authoring()->setTeachers(
            $curso,
            $datos['teacher_ids'],
            isset($datos['main_teacher_id']) ? (int) $datos['main_teacher_id'] : null
        );

        return $this->json([
            'course_id' => $curso->id,
            'teachers' => $docentes,
            'main_teacher_id' => $curso->fresh()->teacher_id,
            'main_teacher' => collect($docentes)->firstWhere('id', (int) $curso->fresh()->teacher_id),
            'note' => 'Los docentes del curso y el encargado quedaron guardados. Usa esos mismos ids en la sección staff de la landing.',
        ]);
    }
}
