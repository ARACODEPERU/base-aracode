<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Academic\Entities\AcaModality;

/**
 * Modalidades de dictado (En Vivo, Presencial, E-learning...).
 */
#[Name('academic_list_modalities')]
#[Description('Lista las modalidades de dictado del ERP con su id y nombre. Consúltala antes de crear un curso.')]
#[IsReadOnly]
class AcademicListModalitiesTool extends AcademicTool
{
    protected function execute(Request $request): Response
    {
        $modalidades = AcaModality::orderBy('description')
            ->get(['id', 'description'])
            ->map(fn (AcaModality $modalidad) => [
                'id' => $modalidad->id,
                'name' => $modalidad->description,
            ])
            ->values()
            ->all();

        return $this->json([
            'modalities' => $modalidades,
            'total' => count($modalidades),
        ]);
    }
}
