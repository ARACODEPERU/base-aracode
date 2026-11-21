<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\Academic\Entities\AcaCategoryCourse;

/**
 * Categorías de cursos disponibles.
 */
#[Name('academic_list_categories')]
#[Description('Lista las categorías de cursos del ERP con su id y nombre. Consúltala antes de crear un curso.')]
#[IsReadOnly]
class AcademicListCategoriesTool extends AcademicTool
{
    protected function execute(Request $request): Response
    {
        $categorias = AcaCategoryCourse::orderBy('description')
            ->get(['id', 'description'])
            ->map(fn (AcaCategoryCourse $categoria) => [
                'id' => $categoria->id,
                'name' => $categoria->description,
            ])
            ->values()
            ->all();

        return $this->json([
            'categories' => $categorias,
            'total' => count($categorias),
        ]);
    }
}
