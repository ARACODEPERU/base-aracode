<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Services\ActiveCourseEditingService;

/**
 * Edita solo datos no financieros de un curso activo. El precio, descuento,
 * tipo de curso y estado quedan fuera de esta vía.
 */
#[Name('academic_update_active_course')]
#[Description('Permiso de activos. Edita datos permitidos de un curso ACTIVO: title, category_id, modality_id, sector_description, start_date, teacher_id, certificate_title y certificate_description. No cambia precio, descuento, tipo de curso, estado ni slug, y no elimina el curso. Primero llama con confirm=false para obtener el antes/después, pregunta a la persona y luego repite con confirm=true y reason.')]
class AcademicUpdateActiveCourseTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write_active';

    protected function execute(Request $request): Response
    {
        $sectores = getEnumValues('aca_courses', 'sector_description');

        $reglas = [
            'course_id' => 'required|integer',
            'title' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|integer|exists:aca_category_courses,id',
            'modality_id' => 'sometimes|required|integer|exists:aca_modalities,id',
            'sector_description' => ['sometimes', 'nullable', 'string', 'max:50'],
            'start_date' => 'sometimes|required|date',
            'teacher_id' => 'sometimes|nullable|integer|exists:aca_teachers,id',
            'certificate_title' => 'sometimes|required|string|max:255',
            'certificate_description' => 'sometimes|nullable|string|max:65000',
            'confirm' => 'sometimes|boolean',
            'reason' => 'nullable|string|min:10|max:500|required_if:confirm,true',
        ];

        if ($sectores !== []) {
            $reglas['sector_description'][] = Rule::in($sectores);
        }

        $datos = $request->validate($reglas, [
            'sector_description.in' => 'El sector no es válido. Usa academic_get_course_options.',
            'reason.required_if' => 'Para aplicar un cambio en un curso activo escribe el motivo y la confirmación de la persona.',
        ], [
            'course_id' => 'id del curso',
            'title' => 'nombre del curso',
            'category_id' => 'categoría',
            'modality_id' => 'modalidad',
            'start_date' => 'fecha de inicio',
            'teacher_id' => 'docente principal',
            'certificate_title' => 'título del certificado',
            'confirm' => 'confirmación',
            'reason' => 'motivo',
        ]);

        $prohibidos = array_values(array_intersect(
            array_keys($request->all()),
            ActiveCourseEditingService::CAMPOS_PROHIBIDOS
        ));

        if ($prohibidos !== []) {
            throw ValidationException::withMessages([
                'course_id' => 'No se permite cambiar por MCP: ' . implode(', ', $prohibidos) . '. El precio, descuento, tipo y estado se editan desde el ERP.',
            ]);
        }

        $curso = $this->activeEditing()->cursoActivo((int) $datos['course_id']);
        $cambios = $datos;
        unset($cambios['course_id'], $cambios['confirm'], $cambios['reason']);

        $preview = $this->activeEditing()->describirCurso($curso, $cambios);

        if (! ($datos['confirm'] ?? false)) {
            return $this->json([
                'requires_confirmation' => true,
                'preview' => $preview,
                'note' => 'No se guardó nada. Muestra este antes/después a la persona, confirma que el curso y los campos son los correctos, y vuelve a llamar con confirm=true y reason. No cambies precios, tipo ni estado.',
            ]);
        }

        $resultado = $this->activeEditing()->actualizarCurso($curso, $cambios);

        return $this->json([
            'requires_confirmation' => false,
            'result' => $resultado,
            'reason' => $datos['reason'],
            'note' => 'Cambios aplicados solo a los campos indicados. El slug del curso y el precio se conservaron.',
        ]);
    }
}
