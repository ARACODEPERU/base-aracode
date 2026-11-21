<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Academic\Entities\AcaCategoryCourse;
use Modules\Academic\Entities\AcaModality;

/**
 * Todo el catálogo de un curso en una sola llamada: categorías, modalidades,
 * tipos y sectores reales, para no gastar una llamada por cada lista.
 */
#[Name('academic_get_course_options')]
#[Description('Devuelve en una sola llamada las categorías, modalidades, tipos de curso y sectores reales del ERP. Úsala como primer paso antes de crear un curso.')]
#[IsReadOnly]
class AcademicCourseOptionsTool extends AcademicTool
{
    protected function execute(Request $request): Response
    {
        $tipos = getEnumValues('aca_courses', 'type_description');
        $sectores = getEnumValues('aca_courses', 'sector_description');

        return $this->json([
            'categories' => AcaCategoryCourse::orderBy('description')
                ->get(['id', 'description'])
                ->map(fn (AcaCategoryCourse $categoria) => [
                    'id' => $categoria->id,
                    'name' => $categoria->description,
                ])
                ->values()
                ->all(),
            'modalities' => AcaModality::orderBy('description')
                ->get(['id', 'description'])
                ->map(fn (AcaModality $modalidad) => [
                    'id' => $modalidad->id,
                    'name' => $modalidad->description,
                ])
                ->values()
                ->all(),
            'course_types' => array_values($tipos),
            'sectors' => array_values($sectores),
            'notes' => [
                'Los ids de categoría y modalidad deben salir de esta respuesta.',
                'type_description y sector_description son listas cerradas: usa exactamente uno de los valores devueltos.',
            ],
        ]);
    }
}
