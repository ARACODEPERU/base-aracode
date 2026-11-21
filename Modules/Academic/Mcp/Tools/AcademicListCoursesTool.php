<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaCourse;

/**
 * Listado corto de cursos para comprobar duplicados antes de crear uno.
 */
#[Name('academic_list_courses')]
#[Description('Lista los cursos del ERP (id, nombre, estado, slug y fecha), con paginación. Úsala para comprobar que el curso no exista ya antes de crearlo.')]
class AcademicListCoursesTool extends AcademicTool
{
    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'search' => 'nullable|string|max:120',
            'status' => 'nullable|string|in:todos,borrador,activo',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $busqueda = trim((string) ($datos['search'] ?? ''));
        $estado = $datos['status'] ?? 'todos';
        $pagina = (int) ($datos['page'] ?? 1);
        $porPagina = (int) ($datos['per_page'] ?? 50);

        $consulta = AcaCourse::query()
            ->when($busqueda !== '', function ($consulta) use ($busqueda) {
                // Se escapan los comodines de LIKE con «!» (portable a MySQL y
                // SQLite): buscar «50%» no debe traer cualquier curso.
                $termino = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $busqueda) . '%';

                $consulta->whereRaw("description LIKE ? ESCAPE '!'", [$termino]);
            })
            ->when($estado === 'borrador', fn ($consulta) => $consulta->where('status', false))
            ->when($estado === 'activo', fn ($consulta) => $consulta->where('status', true));

        $total = (clone $consulta)->count();

        $cursos = $consulta
            ->orderByDesc('id')
            ->forPage($pagina, $porPagina)
            ->get(['id', 'description', 'slug', 'status', 'course_day', 'course_month', 'course_year', 'created_via'])
            ->map(fn (AcaCourse $curso) => [
                'id' => $curso->id,
                'name' => $curso->description,
                'status' => $curso->status ? 'activo' : 'borrador',
                'slug' => $curso->slug,
                'start_date' => trim($curso->course_year . '-' . $curso->course_month . '-' . $curso->course_day, '-'),
                'created_via' => $curso->created_via,
            ])
            ->values()
            ->all();

        return $this->json([
            'courses' => $cursos,
            'returned' => count($cursos),
            'total' => $total,
            'page' => $pagina,
            'per_page' => $porPagina,
            'has_more' => $pagina * $porPagina < $total,
        ]);
    }
}
