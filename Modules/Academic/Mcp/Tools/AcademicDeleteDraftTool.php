<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaExam;

/**
 * Único borrado permitido desde MCP: un borrador que creó un agente. Nunca un
 * curso activo ni uno creado a mano en el ERP.
 */
#[Name('academic_delete_draft')]
#[Description('Borra un curso SOLO si sigue en borrador y fue creado por un agente (created_via = mcp). Exige confirm: true. No sirve para cursos activos ni creados en el ERP.')]
class AcademicDeleteDraftTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer',
            'confirm' => 'required|accepted',
        ], [
            'confirm.accepted' => 'Confirma el borrado con confirm: true.',
        ], [
            'course_id' => 'id del curso',
            'confirm' => 'confirmación',
        ]);

        $curso = AcaCourse::find($datos['course_id']);

        if (! $curso) {
            return Response::error('No existe un curso con ese id.');
        }

        if ($curso->status) {
            throw ValidationException::withMessages([
                'course_id' => 'El curso está activo: no se puede borrar desde MCP.',
            ]);
        }

        if ($curso->created_via !== 'mcp') {
            throw ValidationException::withMessages([
                'course_id' => 'Ese borrador no lo creó un agente; bórralo desde el ERP.',
            ]);
        }

        // Se recogen los archivos antes de borrar las filas: la portada, los
        // materiales descargables y el PDF del brochure no se borran con el
        // curso y quedarían huérfanos en el disco público.
        $rutas = [];

        if (filled($curso->image)) {
            $rutas[] = (string) $curso->image;
        }

        $curso->load(['modules.themes.contents', 'brochure']);

        foreach ($curso->modules as $modulo) {
            foreach ($modulo->themes as $clase) {
                foreach ($clase->contents as $contenido) {
                    $ruta = $this->archivoDeContenido($contenido);

                    if ($ruta !== null) {
                        $rutas[] = $ruta;
                    }
                }
            }
        }

        if (filled($curso->brochure?->path_file)) {
            $rutas[] = (string) $curso->brochure->path_file;
        }

        // Los exámenes pueden colgar del curso o de un módulo sin clave
        // foránea: se borran primero, junto con sus preguntas y respuestas.
        $examenes = AcaExam::where('course_id', $curso->id)
            ->orWhereIn('module_id', $curso->modules->pluck('id'))
            ->get();

        foreach ($examenes as $examen) {
            $this->borrarExamen($examen);
        }

        foreach ($curso->modules as $modulo) {
            foreach ($modulo->themes as $clase) {
                foreach ($clase->contents as $contenido) {
                    $this->borrarContenido($contenido);
                }

                $clase->delete();
            }

            $modulo->delete();
        }

        $titulo = $curso->description;
        $curso->delete();
        $this->borrarArchivos($rutas);

        return $this->json([
            'deleted' => true,
            'course_id' => (int) $datos['course_id'],
            'title' => $titulo,
            'deleted_files' => count($rutas),
        ]);
    }
}
