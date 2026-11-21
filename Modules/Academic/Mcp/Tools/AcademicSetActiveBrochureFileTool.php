<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * Reemplaza el PDF del brochure de un curso activo sin tocar precios ni
 * eliminar el curso.
 */
#[Name('academic_set_active_brochure_file')]
#[Description('Permiso de activos. Reemplaza únicamente el PDF del brochure de un curso ACTIVO. Acepta file_base64 o file_url (https público) con filename .pdf. No cambia precios, no publica la landing y no elimina el curso. Primero usa confirm=false y luego confirma con confirm=true y reason.')]
class AcademicSetActiveBrochureFileTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write_active';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer',
            'filename' => 'nullable|string|max:255',
            'file_base64' => 'required_without:file_url|nullable|string',
            'file_url' => 'required_without:file_base64|nullable|url:https',
            'confirm' => 'sometimes|boolean',
            'reason' => 'nullable|string|min:10|max:500|required_if:confirm,true',
        ], [
            'file_base64.required_without' => 'Envía file_base64 o file_url.',
            'file_url.required_without' => 'Envía file_base64 o file_url.',
            'reason.required_if' => 'Para aplicar un cambio en un curso activo escribe el motivo y la confirmación de la persona.',
        ], [
            'course_id' => 'id del curso',
            'file_base64' => 'PDF en base64',
            'file_url' => 'URL del PDF',
            'confirm' => 'confirmación',
            'reason' => 'motivo',
        ]);

        $curso = $this->activeEditing()->cursoActivo((int) $datos['course_id']);

        if (! ($datos['confirm'] ?? false)) {
            return $this->json([
                'requires_confirmation' => true,
                'course_id' => $curso->id,
                'preview' => [
                    'filename' => $datos['filename'] ?? null,
                    'source' => filled($datos['file_url'] ?? null) ? 'file_url' : 'file_base64',
                    'current_brochure' => \Modules\Academic\Entities\AcaBrochure::where('course_id', $curso->id)->value('path_file'),
                ],
                'note' => 'No se guardó nada. Confirma que quieres reemplazar solo el PDF del brochure y vuelve a llamar con confirm=true y reason.',
            ]);
        }

        $resultado = $this->activeEditing()->reemplazarBrochure($curso, $datos);

        return $this->json([
            'requires_confirmation' => false,
            'reason' => $datos['reason'],
            'result' => $resultado,
        ]);
    }
}
