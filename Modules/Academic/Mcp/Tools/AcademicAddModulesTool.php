<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Services\CourseAuthoringService;

/**
 * Agrega varios módulos al final del curso en una sola llamada.
 */
#[Name('academic_add_modules')]
#[Description('Agrega módulos al final de un curso en borrador, en lote. Cada módulo lleva un title de hasta 200 caracteres; la posición se numera sola.')]
class AcademicAddModulesTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer',
            'modules' => 'required|array|min:1|max:' . CourseAuthoringService::MAX_MODULES,
            'modules.*.title' => 'required|string|max:200',
            'modules.*.position' => 'nullable|integer|min:1|max:99',
        ], [], [
            'course_id' => 'id del curso',
            'modules' => 'módulos',
            'modules.*.title' => 'nombre del módulo',
            'modules.*.position' => 'posición del módulo',
        ]);

        $curso = $this->cursoBorrador((int) $datos['course_id']);

        $creados = $this->authoring()->addModules($curso, $datos['modules']);

        return $this->json([
            'course_id' => $curso->id,
            'modules' => array_map(fn (AcaModule $modulo) => [
                'id' => $modulo->id,
                'position' => (int) $modulo->position,
                'title' => $modulo->description,
            ], $creados),
            'next_step' => 'Continúa con academic_add_lessons usando el id de cada módulo.',
        ]);
    }
}
