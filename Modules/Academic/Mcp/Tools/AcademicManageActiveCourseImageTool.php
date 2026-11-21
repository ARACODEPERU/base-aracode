<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * Solo toca la imagen de portada del curso. No elimina el curso.
 */
#[Name('academic_manage_active_course_image')]
#[Description('Permiso de activos. Reemplaza (replace) o elimina (remove) únicamente la imagen de portada de un curso ACTIVO. remove no elimina el curso ni sus contenidos. Para replace envía image_base64 o image_url; formatos JPG, PNG o WebP de hasta 3 MB y al menos 600x300. Primero usa confirm=false y luego confirma con confirm=true y reason.')]
class AcademicManageActiveCourseImageTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write_active';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer',
            'action' => ['required', Rule::in(['replace', 'remove'])],
            'image_base64' => 'sometimes|nullable|string',
            'image_url' => 'sometimes|nullable|url:https',
            'confirm' => 'sometimes|boolean',
            'reason' => 'nullable|string|min:10|max:500|required_if:confirm,true',
        ], [
            'action.in' => 'Acción no permitida. Usa replace o remove.',
            'reason.required_if' => 'Para aplicar un cambio en un curso activo escribe el motivo y la confirmación de la persona.',
        ], [
            'course_id' => 'id del curso',
            'action' => 'acción sobre la imagen',
            'confirm' => 'confirmación',
            'reason' => 'motivo',
        ]);

        $curso = $this->activeEditing()->cursoActivo((int) $datos['course_id']);
        $accion = (string) $datos['action'];
        $imagen = (string) ($datos['image_base64'] ?? $datos['image_url'] ?? '');

        if ($accion === 'replace' && $imagen === '') {
            throw ValidationException::withMessages([
                'image_base64' => 'Para replace envía image_base64 o image_url.',
            ]);
        }

        if (! ($datos['confirm'] ?? false)) {
            return $this->json([
                'requires_confirmation' => true,
                'course_id' => $curso->id,
                'preview' => [
                    'action' => $accion,
                    'before' => $curso->image,
                    'after' => $accion === 'remove' ? null : 'nueva imagen pendiente de validar',
                ],
                'note' => 'No se guardó nada. Confirma que quieres cambiar solo la portada y vuelve a llamar con confirm=true y reason.',
            ]);
        }

        $resultado = $this->activeEditing()->cambiarImagen($curso, $accion, $accion === 'replace' ? $imagen : null);

        return $this->json([
            'requires_confirmation' => false,
            'reason' => $datos['reason'],
            'result' => $resultado,
        ]);
    }
}
