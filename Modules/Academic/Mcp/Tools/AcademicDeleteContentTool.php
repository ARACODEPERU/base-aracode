<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaContent;

/**
 * Borra un contenido de una clase de un curso en borrador. Si es un archivo
 * descargable, también lo elimina del disco público.
 */
#[Name('academic_delete_content')]
#[Description('Borra un contenido de una clase de un curso en borrador, junto con su examen y su archivo si lo tiene. Exige confirm: true. No sirve para cursos activos.')]
class AcademicDeleteContentTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'content_id' => 'required|integer',
            'confirm' => 'required|accepted',
        ], [
            'confirm.accepted' => 'Confirma el borrado con confirm: true.',
        ], [
            'content_id' => 'id del contenido',
            'confirm' => 'confirmación',
        ]);

        $contenido = AcaContent::with('theme.module')->find($datos['content_id']);

        if (! $contenido || ! $contenido->theme || ! $contenido->theme->module) {
            throw ValidationException::withMessages(['content_id' => 'No existe un contenido con ese id.']);
        }

        $this->cursoBorrador((int) $contenido->theme->module->course_id);

        $ruta = $this->archivoDeContenido($contenido);
        $resumen = [
            'id' => $contenido->id,
            'title' => $contenido->description,
            'type' => (int) $contenido->is_file,
        ];

        $this->borrarContenido($contenido);
        $this->borrarArchivos($ruta !== null ? [$ruta] : []);

        return $this->json([
            'deleted' => true,
            'content' => $resumen,
            'deleted_files' => $ruta !== null ? 1 : 0,
        ]);
    }
}
