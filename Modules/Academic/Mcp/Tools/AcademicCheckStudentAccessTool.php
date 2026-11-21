<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudent;

/**
 * Comprueba el acceso de un estudiante a un curso con las reglas del aula.
 */
#[Name('academic_check_student_access')]
#[Description('Solo lectura. Indica si un estudiante tiene acceso a un curso y por qué: matrícula vigente, suscripción activa o curso gratuito. Incluye el vencimiento de la suscripción y la matrícula.')]
#[IsReadOnly]
class AcademicCheckStudentAccessTool extends AcademicTool
{
    protected ?string $permission = 'aca_estudiante_listado';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'student_id' => 'required|integer|exists:aca_students,id',
            'course_id' => 'required|integer|exists:aca_courses,id',
        ], [], [
            'student_id' => 'id del estudiante',
            'course_id' => 'id del curso',
        ]);

        $estudiante = AcaStudent::with('person')->find($datos['student_id']);
        $curso = AcaCourse::find($datos['course_id']);

        if (! $estudiante || ! $curso) {
            return Response::error('No se encontró el estudiante o el curso.');
        }

        return $this->json($this->insights()->studentCourseAccess($estudiante, $curso));
    }
}
