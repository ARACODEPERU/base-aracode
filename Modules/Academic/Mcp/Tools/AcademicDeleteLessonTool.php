<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaTheme;

/**
 * Borra una clase de un curso en borrador con sus contenidos, exámenes y
 * materiales descargables.
 */
#[Name('academic_delete_lesson')]
#[Description('Borra una clase de un curso en borrador con sus contenidos y exámenes. Exige confirm: true. No sirve para cursos activos.')]
class AcademicDeleteLessonTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'lesson_id' => 'required|integer',
            'confirm' => 'required|accepted',
        ], [
            'confirm.accepted' => 'Confirma el borrado con confirm: true.',
        ], [
            'lesson_id' => 'id de la clase',
            'confirm' => 'confirmación',
        ]);

        $clase = AcaTheme::with(['module', 'contents'])->find($datos['lesson_id']);

        if (! $clase || ! $clase->module) {
            throw ValidationException::withMessages(['lesson_id' => 'No existe una clase con ese id.']);
        }

        $this->cursoBorrador((int) $clase->module->course_id);

        $rutas = [];

        foreach ($clase->contents as $contenido) {
            $ruta = $this->archivoDeContenido($contenido);

            if ($ruta !== null) {
                $rutas[] = $ruta;
            }
        }

        $resumen = [
            'id' => $clase->id,
            'title' => $clase->description,
            'contents' => $clase->contents->count(),
        ];

        foreach ($clase->contents as $contenido) {
            $this->borrarContenido($contenido);
        }

        $clase->delete();
        $this->borrarArchivos($rutas);

        return $this->json([
            'deleted' => true,
            'lesson' => $resumen,
            'deleted_files' => count(array_unique($rutas)),
        ]);
    }
}
