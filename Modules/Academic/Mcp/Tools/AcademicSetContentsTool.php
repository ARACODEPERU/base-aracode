<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaTheme;
use Modules\Academic\Services\CourseAuthoringService;

/**
 * Agrega contenidos de texto a una clase en una sola llamada.
 */
#[Name('academic_set_contents')]
#[Description('Agrega contenidos de TEXTO (HTML) a una clase en borrador, en lote. Cada contenido lleva title y html; el HTML se sanea antes de guardarse. No uses esta herramienta para videos ni videoconferencias: para eso existe academic_add_lesson_links. Usa el id de la clase que devolvió academic_add_lessons.')]
class AcademicSetContentsTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'theme_id' => 'required|integer',
            'contents' => 'required|array|min:1|max:' . CourseAuthoringService::MAX_CONTENTS_PER_LESSON,
            'contents.*.title' => 'required|string|max:255',
            'contents.*.html' => 'required|string|max:' . CourseAuthoringService::MAX_HTML_CHARS,
            'contents.*.position' => 'nullable|integer|min:1|max:99',
        ], [
            'contents.*.html.required' => 'Cada contenido necesita su html.',
            'contents.*.html.max' => 'El HTML de un contenido supera los ' . CourseAuthoringService::MAX_HTML_CHARS . ' caracteres; divídelo en varios contenidos.',
        ], [
            'theme_id' => 'id de la clase',
            'contents' => 'contenidos',
            'contents.*.title' => 'título del contenido',
            'contents.*.html' => 'html del contenido',
            'contents.*.position' => 'posición del contenido',
        ]);

        $clase = AcaTheme::with('module')->find($datos['theme_id']);

        if (! $clase || ! $clase->module) {
            throw ValidationException::withMessages([
                'theme_id' => 'No existe una clase con ese id.',
            ]);
        }

        // Solo se toca una clase cuyo curso siga en borrador.
        $this->cursoBorrador((int) $clase->module->course_id);

        $creados = $this->authoring()->setContents($clase, $datos['contents']);

        return $this->json([
            'theme_id' => $clase->id,
            'contents' => array_map(fn (AcaContent $contenido) => [
                'id' => $contenido->id,
                'position' => (int) $contenido->position,
                'title' => $contenido->description,
                'type' => CourseAuthoringService::CONTENT_TYPES[(int) $contenido->is_file] ?? 'texto',
                'characters' => mb_strlen($contenido->content),
            ], $creados),
            'next_step' => 'Cuando el curso esté completo, avisa a la persona responsable para que lo revise y lo active en el ERP. La activación no se hace desde MCP.',
        ]);
    }
}
