<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Services\CourseAuthoringService;

/**
 * Reescribe un contenido existente con el mismo saneado de la creación.
 */
#[Name('academic_update_content')]
#[Description('Actualiza el título, la posición o el HTML de un contenido de TEXTO de un curso en borrador. El HTML se sanea antes de guardarse (máximo 120000 caracteres). No permite convertir un video, videoconferencia, archivo o enlace en texto.')]
class AcademicUpdateContentTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'content_id' => 'required|integer',
            'title' => 'sometimes|required|string|max:255',
            'position' => 'sometimes|required|integer|min:1|max:99',
            'html' => 'sometimes|required|string|max:' . CourseAuthoringService::MAX_HTML_CHARS,
        ], [
            'html.max' => 'El HTML supera los ' . CourseAuthoringService::MAX_HTML_CHARS . ' caracteres; divídelo en varios contenidos.',
        ], [
            'content_id' => 'id del contenido',
            'title' => 'título del contenido',
            'html' => 'html del contenido',
        ]);

        $contenido = AcaContent::with('theme.module')->find($datos['content_id']);

        if (! $contenido || ! $contenido->theme || ! $contenido->theme->module) {
            throw ValidationException::withMessages(['content_id' => 'No existe un contenido con ese id.']);
        }

        $this->cursoBorrador((int) $contenido->theme->module->course_id);

        // Los tipos 2, 3, 4 y 5 guardan una ruta o una URL (archivo,
        // videoconferencia, examen o grabación): reemplazarlas por HTML rompe
        // la descarga o el reproductor. El tipo 0 es un iframe de video y el
        // tipo 1 un enlace real; solo los textos antiguos guardados con 0 o 1
        // (sin iframe ni URL) pueden seguir editándose como HTML.
        $tipo = (int) $contenido->is_file;
        $valor = trim((string) $contenido->content);
        $esVideo = $tipo === 0 && preg_match('#<iframe\b#i', $valor) === 1;
        $esEnlace = $tipo === 1 && preg_match('#^https?://#i', $valor) === 1;

        if (array_key_exists('html', $datos) && (in_array($tipo, [2, 3, 4, 5], true) || $esVideo || $esEnlace)) {
            throw ValidationException::withMessages([
                'html' => 'Ese contenido guarda un archivo, video o enlace, no HTML. Solo puedes cambiar title o position.',
            ]);
        }

        unset($datos['content_id']);
        $contenido = $this->authoring()->updateContent($contenido, $datos);

        return $this->json([
            'content' => [
                'id' => $contenido->id,
                'position' => (int) $contenido->position,
                'title' => $contenido->description,
                'characters' => mb_strlen((string) $contenido->content),
            ],
        ]);
    }
}
