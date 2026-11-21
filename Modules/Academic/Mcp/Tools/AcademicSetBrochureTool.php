<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Services\CourseAuthoringService;

/**
 * Brochure del curso: los bloques de texto que alimentan el PDF y la ficha.
 * Son HTML del editor, así que pasan por el saneado del servicio.
 */
#[Name('academic_set_brochure')]
#[Description('Crea o actualiza los bloques de texto del brochure de un curso en borrador: resolution, presentation, benefits, frequent_questions y curriculum_plan (HTML saneado). Envía solo los bloques que cambian.')]
class AcademicSetBrochureTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    /** Bloques de texto del brochure. */
    private const BLOCKS = ['resolution', 'presentation', 'benefits', 'frequent_questions', 'curriculum_plan'];

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer',
            'resolution' => 'sometimes|nullable|string|max:' . CourseAuthoringService::MAX_HTML_CHARS,
            'presentation' => 'sometimes|nullable|string|max:' . CourseAuthoringService::MAX_HTML_CHARS,
            'benefits' => 'sometimes|nullable|string|max:' . CourseAuthoringService::MAX_HTML_CHARS,
            'frequent_questions' => 'sometimes|nullable|string|max:' . CourseAuthoringService::MAX_HTML_CHARS,
            'curriculum_plan' => 'sometimes|nullable|string|max:' . CourseAuthoringService::MAX_HTML_CHARS,
        ], [], [
            'course_id' => 'id del curso',
            'resolution' => 'resolución',
            'presentation' => 'presentación',
            'benefits' => 'beneficios',
            'frequent_questions' => 'preguntas frecuentes',
            'curriculum_plan' => 'plan de estudios',
        ]);

        $curso = $this->cursoBorrador((int) $datos['course_id']);

        $bloques = array_values(array_intersect(self::BLOCKS, array_keys($datos)));

        if ($bloques === []) {
            return Response::error('No enviaste ningún bloque del brochure: resolution, presentation, benefits, frequent_questions o curriculum_plan.');
        }

        $brochure = $this->authoring()->setBrochure($curso, $datos);

        return $this->json([
            'course_id' => $curso->id,
            'updated_blocks' => $bloques,
            'brochure' => [
                'resolution' => filled($brochure->resolution),
                'presentation' => filled($brochure->presentation),
                'benefits' => filled($brochure->benefits),
                'frequent_questions' => filled($brochure->frequent_questions),
                'curriculum_plan' => filled($brochure->curriculum_plan),
            ],
            'note' => 'El PDF del brochure se genera desde el ERP.',
        ]);
    }
}
