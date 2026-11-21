<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Academic\Entities\AcaTeacher;

/**
 * Docentes ya registrados en el ERP, para asignar teacher_id sin inventarlo.
 */
#[Name('academic_list_teachers')]
#[Description('Lista los docentes registrados en el ERP con su id, código, nombre y ocupación. Úsala antes de asignar el equipo docente y el encargado del curso.')]
#[IsReadOnly]
class AcademicListTeachersTool extends AcademicTool
{
    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'search' => 'nullable|string|max:120',
        ]);

        $busqueda = trim((string) ($datos['search'] ?? ''));

        $docentes = AcaTeacher::with('person')
            ->get()
            ->map(fn (AcaTeacher $docente) => [
                'id' => $docente->id,
                'code' => $docente->teacher_code,
                'name' => $docente->person?->full_name
                    ?? trim(implode(' ', array_filter([
                        $docente->person?->names,
                        $docente->person?->father_lastname,
                        $docente->person?->mother_lastname,
                    ]))),
                'occupation' => $docente->person?->ocupacion,
                'image' => $docente->person?->image,
            ])
            ->filter(fn (array $docente) => $docente['name'] !== '')
            ->when($busqueda !== '', fn ($coleccion) => $coleccion->filter(
                fn (array $docente) => mb_stripos($docente['name'], $busqueda) !== false
            ))
            ->sortBy('name')
            ->values()
            ->take(50)
            ->all();

        return $this->json([
            'teachers' => $docentes,
            'returned' => count($docentes),
        ]);
    }
}
