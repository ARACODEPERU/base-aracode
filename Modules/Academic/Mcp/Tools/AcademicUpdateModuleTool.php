<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaModule;

/**
 * Corrige el nombre o la posición de un módulo mientras el curso sea borrador.
 */
#[Name('academic_update_module')]
#[Description('Actualiza el título o la posición de un módulo de un curso en borrador. El título admite hasta 200 caracteres.')]
class AcademicUpdateModuleTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'module_id' => 'required|integer',
            'title' => 'sometimes|required|string|max:200',
            'position' => 'sometimes|required|integer|min:1|max:99',
        ], [], [
            'module_id' => 'id del módulo',
            'title' => 'nombre del módulo',
            'position' => 'posición',
        ]);

        $modulo = AcaModule::find($datos['module_id']);

        if (! $modulo) {
            throw ValidationException::withMessages(['module_id' => 'No existe un módulo con ese id.']);
        }

        $this->cursoBorrador((int) $modulo->course_id);

        $cambios = [];

        if (array_key_exists('title', $datos)) {
            $cambios['description'] = trim((string) $datos['title']);
        }

        if (array_key_exists('position', $datos)) {
            $cambios['position'] = (string) $datos['position'];
        }

        if ($cambios === []) {
            throw ValidationException::withMessages(['module_id' => 'No enviaste ningún campo para actualizar.']);
        }

        $modulo->update($cambios);

        return $this->json([
            'module' => [
                'id' => $modulo->id,
                'position' => (int) $modulo->position,
                'title' => $modulo->description,
            ],
            'updated_fields' => array_keys($cambios),
        ]);
    }
}
