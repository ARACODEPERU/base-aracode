<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * Corrige los datos base del curso mientras siga en borrador.
 */
#[Name('academic_update_course')]
#[Description('Actualiza datos del curso en borrador (nombre, categoría, modalidad, tipo, sector, fecha, precio, docente o título del certificado). Envía solo lo que cambia. Un curso activo no se puede modificar desde MCP.')]
class AcademicUpdateCourseTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $tipos = getEnumValues('aca_courses', 'type_description');
        $sectores = getEnumValues('aca_courses', 'sector_description');

        $reglas = [
            'course_id' => 'required|integer',
            'title' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|integer|exists:aca_category_courses,id',
            'modality_id' => 'sometimes|required|integer|exists:aca_modalities,id',
            'type_description' => ['sometimes', 'required', 'string', 'max:50'],
            'sector_description' => ['sometimes', 'nullable', 'string', 'max:50'],
            'start_date' => 'sometimes|required|date',
            // aca_courses.price es decimal(8,2): el máximo real es 999999.99.
            'price' => 'sometimes|nullable|numeric|min:0|max:999999.99',
            'teacher_id' => 'sometimes|nullable|integer|exists:aca_teachers,id',
            'certificate_title' => 'sometimes|nullable|string|max:255',
            'certificate_description' => 'sometimes|nullable|string|max:65000',
            'usine' => 'sometimes|nullable|string|max:255',
            'discount' => 'sometimes|nullable|numeric|min:0|max:999999.99',
            'discount_applies' => ['sometimes', 'nullable', Rule::in(['01', '02'])],
            'auto_certificate' => 'sometimes|boolean',
            'round_grades' => 'sometimes|boolean',
        ];

        if ($tipos !== []) {
            $reglas['type_description'][] = Rule::in($tipos);
        }

        if ($sectores !== []) {
            $reglas['sector_description'][] = Rule::in($sectores);
        }

        $datos = $request->validate($reglas, [
            'type_description.in' => 'El tipo de curso no es válido. Usa academic_get_course_options.',
            'sector_description.in' => 'El sector no es válido. Usa academic_get_course_options.',
        ], [
            'course_id' => 'id del curso',
            'title' => 'nombre del curso',
            'start_date' => 'fecha de inicio',
        ]);

        $curso = $this->cursoBorrador((int) $datos['course_id']);

        $cambios = [];

        foreach ([
            'title',
            'category_id',
            'modality_id',
            'type_description',
            'sector_description',
            'price',
            'teacher_id',
            'certificate_title',
            'certificate_description',
            'usine',
            'discount',
            'discount_applies',
            'auto_certificate',
            'round_grades',
        ] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $valor = $datos[$campo];

                if (in_array($campo, ['auto_certificate', 'round_grades'], true)) {
                    $valor = (bool) $valor;
                }

                $cambios[$campo === 'title' ? 'description' : $campo] = $valor;
            }
        }

        if (array_key_exists('start_date', $datos)) {
            $marca = strtotime((string) $datos['start_date']);
            $cambios['course_day'] = date('d', $marca);
            $cambios['course_month'] = date('m', $marca);
            $cambios['course_year'] = date('Y', $marca);
        }

        if ($cambios === []) {
            throw ValidationException::withMessages([
                'course_id' => 'No enviaste ningún campo para actualizar.',
            ]);
        }

        if (array_key_exists('certificate_title', $cambios) && blank($cambios['certificate_title'])) {
            // Si en la misma llamada también cambia el nombre, el certificado
            // debe quedar con el nombre nuevo, no con el anterior.
            $cambios['certificate_title'] = $cambios['description'] ?? $curso->description;
        }

        $curso->update($cambios);

        return $this->json([
            'course' => [
                'id' => $curso->id,
                'title' => $curso->fresh()->description,
                'slug' => $curso->fresh()->slug,
                'status' => 'borrador',
                'url' => route('aca_courses_information', $curso->id),
            ],
            'updated_fields' => array_keys($cambios),
        ]);
    }
}
