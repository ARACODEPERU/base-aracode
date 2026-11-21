<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Services\CourseAuthoringService;

/**
 * Agrega varias clases (aca_themes) a un módulo en una sola llamada.
 */
#[Name('academic_add_lessons')]
#[Description('Agrega clases a un módulo en borrador, en lote. Cada clase lleva un title de hasta 255 caracteres; la posición se numera sola. Usa el id del módulo que devolvió academic_add_modules.')]
class AcademicAddLessonsTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'module_id' => 'required|integer',
            'lessons' => 'required|array|min:1|max:' . CourseAuthoringService::MAX_LESSONS_PER_MODULE,
            'lessons.*.title' => 'required|string|max:255',
            'lessons.*.position' => 'nullable|integer|min:1|max:99',
        ], [], [
            'module_id' => 'id del módulo',
            'lessons' => 'clases',
            'lessons.*.title' => 'nombre de la clase',
            'lessons.*.position' => 'posición de la clase',
        ]);

        $modulo = AcaModule::with('course')->find($datos['module_id']);

        if (! $modulo) {
            throw ValidationException::withMessages([
                'module_id' => 'No existe un módulo con ese id.',
            ]);
        }

        // Solo se toca un módulo cuyo curso siga en borrador.
        $this->cursoBorrador((int) $modulo->course_id);

        $creadas = $this->authoring()->addLessons($modulo, $datos['lessons']);

        return $this->json([
            'module_id' => $modulo->id,
            'lessons' => array_map(fn (AcaTheme $clase) => [
                'id' => $clase->id,
                'position' => (int) $clase->position,
                'title' => $clase->description,
            ], $creadas),
            'next_step' => 'Continúa con academic_set_contents usando el id de cada clase.',
        ]);
    }
}
