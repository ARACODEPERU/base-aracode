<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Services\CourseAuthoringService;

/**
 * Enlaces de una clase: videoconferencia en vivo (Zoom, Meet o Teams), video
 * embebido (Vimeo o YouTube) o un recurso externo. Coincide con los tipos 3, 0
 * y 1 del mantenedor del curso.
 */
#[Name('academic_add_lesson_links')]
#[Description('Agrega enlaces a una clase en borrador. type: "zoom", "meet" o "teams" para la videoconferencia en vivo; "vimeo" o "youtube" para un video embebido (se guarda el iframe del reproductor); "archivo" para un recurso externo como Google Drive o OneDrive. Cada enlace lleva title y url https. El texto de la clase NO se envía aquí: usa academic_set_contents. Usa el id de la clase que devolvió academic_add_lessons.')]
class AcademicAddLessonLinksTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'theme_id' => 'required|integer',
            'links' => 'required|array|min:1|max:' . CourseAuthoringService::MAX_CONTENTS_PER_LESSON,
            'links.*.title' => 'required|string|max:255',
            'links.*.type' => ['required', Rule::in(['zoom', 'meet', 'teams', 'vimeo', 'youtube', 'archivo'])],
            'links.*.url' => 'required|url:https|max:2048',
            'links.*.position' => 'nullable|integer|min:1|max:99',
        ], [
            'links.*.type.in' => 'Tipo de enlace no válido: usa zoom, meet, teams, vimeo, youtube o archivo.',
            'links.*.url.url' => 'Cada enlace necesita una URL completa.',
        ], [
            'theme_id' => 'id de la clase',
            'links' => 'enlaces',
            'links.*.title' => 'título del enlace',
            'links.*.type' => 'tipo de enlace',
            'links.*.url' => 'URL del enlace',
            'links.*.position' => 'posición del contenido',
        ]);

        $clase = AcaTheme::with('module')->find($datos['theme_id']);

        if (! $clase || ! $clase->module) {
            throw ValidationException::withMessages([
                'theme_id' => 'No existe una clase con ese id.',
            ]);
        }

        $this->cursoBorrador((int) $clase->module->course_id);

        $creados = $this->authoring()->addLessonLinks($clase, $datos['links']);

        return $this->json([
            'theme_id' => $clase->id,
            'links' => array_map(fn (AcaContent $contenido) => [
                'id' => $contenido->id,
                'position' => (int) $contenido->position,
                'title' => $contenido->description,
                'type' => CourseAuthoringService::contentType($contenido),
            ], $creados),
            'next_step' => 'La clase ya tiene su enlace. Cuando el curso esté completo llama a academic_validate_course.',
        ]);
    }
}
