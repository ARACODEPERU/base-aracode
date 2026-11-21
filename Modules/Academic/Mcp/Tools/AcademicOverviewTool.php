<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * Métricas agregadas para un informe académico: estudiantes, cursos, avance,
 * exámenes, asistencias y suscripciones por vencer.
 */
#[Name('academic_get_academic_overview')]
#[Description('Solo lectura. Devuelve métricas agregadas: estudiantes activos, distribución de cursos por alumno, cursos con más matrículas y actividad, avance promedio, exámenes, asistencias y suscripciones por vencer. Ideal para preparar un informe o documento.')]
#[IsReadOnly]
class AcademicOverviewTool extends AcademicTool
{
    protected ?string $permission = 'aca_estudiante_listado';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
            'top' => 'nullable|integer|min:1|max:50',
        ], [], [
            'days' => 'días de actividad',
            'top' => 'cantidad de cursos en rankings',
        ]);

        return $this->json($this->insights()->overview(
            (int) ($datos['days'] ?? 30),
            (int) ($datos['top'] ?? 10)
        ));
    }
}
