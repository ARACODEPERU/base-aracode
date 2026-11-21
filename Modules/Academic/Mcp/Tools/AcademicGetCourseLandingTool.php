<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Academic\Entities\AcaCourseLanding;
use Modules\Academic\Services\CourseLandingService;

/**
 * Estado completo de la landing y del brochure del curso. Sirve para que el
 * agente lea lo ya escrito antes de editar y para saber qué datos pedirle a la
 * persona.
 */
#[Name('academic_get_course_landing')]
#[Description('Devuelve la landing completa de un curso (configuración, banner, secciones y brochure) y una lista de lo que falta. Si no existe, indica que se puede crear con academic_set_course_landing.')]
#[IsReadOnly]
class AcademicGetCourseLandingTool extends AcademicTool
{
    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer|exists:aca_courses,id',
        ], [], [
            'course_id' => 'id del curso',
        ]);

        $landing = AcaCourseLanding::where('course_id', $datos['course_id'])->first();

        if (! $landing) {
            return $this->json([
                'course_id' => (int) $datos['course_id'],
                'landing' => null,
                'missing' => ['landing'],
                'next_step' => 'El curso todavía no tiene landing. Créala con academic_set_course_landing; primero reúne título, fechas, docentes, imágenes, PDFs y contenido de las secciones.',
            ]);
        }

        return $this->json([
            'course_id' => (int) $datos['course_id'],
            'landing' => app(CourseLandingService::class)->state($landing),
            'next_step' => 'Edita solo lo que falte con academic_set_course_landing. Para el PDF usa academic_set_brochure_file; para los docentes, academic_set_course_teachers.',
        ]);
    }
}
