<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/**
 * Suscripciones próximas a vencer o vencidas, para avisar a los estudiantes.
 */
#[Name('academic_list_expiring_subscriptions')]
#[Description('Solo lectura. Lista estudiantes con suscripciones por vencer en los próximos días, o vencidas si se pide. Devuelve plan, fechas, días restantes y datos de contacto del estudiante.')]
#[IsReadOnly]
class AcademicExpiringSubscriptionsTool extends AcademicTool
{
    protected ?string $permission = 'aca_estudiante_listado';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
            'include_expired' => 'nullable|boolean',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ], [], [
            'days' => 'días hacia adelante',
            'include_expired' => 'incluir vencidas',
            'page' => 'página',
            'per_page' => 'filas por página',
        ]);

        return $this->json($this->insights()->expiringSubscriptions(
            (int) ($datos['days'] ?? 15),
            (bool) ($datos['include_expired'] ?? false),
            (int) ($datos['page'] ?? 1),
            (int) ($datos['per_page'] ?? 25)
        ));
    }
}
