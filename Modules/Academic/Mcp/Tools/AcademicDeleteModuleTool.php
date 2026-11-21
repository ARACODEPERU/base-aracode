<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\AcaModule;

/**
 * Borra un módulo completo de un curso en borrador: sus clases, contenidos,
 * exámenes y materiales descargables.
 */
#[Name('academic_delete_module')]
#[Description('Borra un módulo de un curso en borrador con sus clases, contenidos y exámenes. Exige confirm: true. No sirve para cursos activos.')]
class AcademicDeleteModuleTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'module_id' => 'required|integer',
            'confirm' => 'required|accepted',
        ], [
            'confirm.accepted' => 'Confirma el borrado con confirm: true.',
        ], [
            'module_id' => 'id del módulo',
            'confirm' => 'confirmación',
        ]);

        $modulo = AcaModule::with(['themes.contents'])->find($datos['module_id']);

        if (! $modulo) {
            return Response::error('No existe un módulo con ese id.');
        }

        $this->cursoBorrador((int) $modulo->course_id);

        $rutas = [];

        foreach ($modulo->themes as $clase) {
            foreach ($clase->contents as $contenido) {
                $ruta = $this->archivoDeContenido($contenido);

                if ($ruta !== null) {
                    $rutas[] = $ruta;
                }
            }
        }

        $resumen = [
            'id' => $modulo->id,
            'title' => $modulo->description,
            'lessons' => $modulo->themes->count(),
            'contents' => $modulo->themes->sum(fn ($clase) => $clase->contents->count()),
        ];

        // Los exámenes de módulo no tienen clave foránea y los contenidos
        // dependen de sus clases: se borra todo explícitamente, de abajo arriba.
        foreach ($modulo->themes as $clase) {
            foreach ($clase->contents as $contenido) {
                $this->borrarContenido($contenido);
            }

            $clase->delete();
        }

        foreach (AcaExam::where('module_id', $modulo->id)->get() as $examen) {
            $this->borrarExamen($examen);
        }

        $modulo->delete();
        $this->borrarArchivos($rutas);

        return $this->json([
            'deleted' => true,
            'module' => $resumen,
            'deleted_files' => count(array_unique($rutas)),
        ]);
    }
}
