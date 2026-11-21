<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaTheme;

/**
 * Corrige el nombre o la posición de una clase mientras el curso sea borrador.
 */
#[Name('academic_update_lesson')]
#[Description('Actualiza el título o la posición de una clase de un curso en borrador. El título admite hasta 255 caracteres.')]
class AcademicUpdateLessonTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'lesson_id' => 'required|integer',
            'title' => 'sometimes|required|string|max:255',
            'position' => 'sometimes|required|integer|min:1|max:99',
        ], [], [
            'lesson_id' => 'id de la clase',
            'title' => 'nombre de la clase',
            'position' => 'posición',
        ]);

        $clase = AcaTheme::with('module')->find($datos['lesson_id']);

        if (! $clase || ! $clase->module) {
            throw ValidationException::withMessages(['lesson_id' => 'No existe una clase con ese id.']);
        }

        $this->cursoBorrador((int) $clase->module->course_id);

        $cambios = [];

        if (array_key_exists('title', $datos)) {
            $cambios['description'] = trim((string) $datos['title']);
        }

        if (array_key_exists('position', $datos)) {
            $cambios['position'] = (string) $datos['position'];
        }

        if ($cambios === []) {
            throw ValidationException::withMessages(['lesson_id' => 'No enviaste ningún campo para actualizar.']);
        }

        $clase->update($cambios);

        return $this->json([
            'lesson' => [
                'id' => $clase->id,
                'position' => (int) $clase->position,
                'title' => $clase->description,
            ],
            'updated_fields' => array_keys($cambios),
        ]);
    }
}
