<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * Crea el curso en borrador. Nunca lo activa: la activación es una acción
 * humana en el ERP, con su revisión y su permiso.
 */
#[Name('academic_create_course_draft')]
#[Description('Crea un curso en BORRADOR con sus datos base y devuelve su id. No lo activa: una persona lo revisa y lo activa en el ERP. Usa una idempotency_key para que un reintento no duplique el curso.')]
class AcademicCreateCourseDraftTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_nuevo';

    protected string $ability = 'academic:write';

    /** Tope de cursos creados por hora y por usuario. */
    private const MAX_PER_HOUR = 10;

    protected function execute(Request $request): Response
    {
        $usuario = $request->user();

        $tipos = getEnumValues('aca_courses', 'type_description');
        $sectores = getEnumValues('aca_courses', 'sector_description');

        $reglas = [
            'title' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:aca_category_courses,id',
            'modality_id' => 'required|integer|exists:aca_modalities,id',
            'start_date' => 'required|date',
            'type_description' => ['required', 'string', 'max:50'],
            'sector_description' => ['nullable', 'string', 'max:50'],
            // aca_courses.price es decimal(8,2): el máximo real es 999999.99.
            'price' => 'nullable|numeric|min:0|max:999999.99',
            'teacher_id' => 'nullable|integer|exists:aca_teachers,id',
            'certificate_title' => 'nullable|string|max:255',
            'certificate_description' => 'nullable|string|max:65000',
            'usine' => 'nullable|string|max:255',
            'discount' => 'nullable|numeric|min:0|max:999999.99',
            'discount_applies' => ['nullable', Rule::in(['01', '02'])],
            'auto_certificate' => 'nullable|boolean',
            'round_grades' => 'nullable|boolean',
            'idempotency_key' => 'nullable|string|max:100',
        ];

        // Los enums solo se pueden comprobar contra la lista real de la tabla;
        // en motores que no exponen el enum (sqlite en pruebas) se deja pasar.
        if ($tipos !== []) {
            $reglas['type_description'][] = Rule::in($tipos);
        }

        if ($sectores !== []) {
            $reglas['sector_description'][] = Rule::in($sectores);
        }

        $datos = $request->validate($reglas, [
            'type_description.in' => 'El tipo de curso no es válido. Usa uno de los valores de academic_get_course_options.',
            'sector_description.in' => 'El sector no es válido. Usa uno de los valores de academic_get_course_options.',
        ], [
            'title' => 'nombre del curso',
            'category_id' => 'id de la categoría',
            'modality_id' => 'id de la modalidad',
            'start_date' => 'fecha de inicio',
            'type_description' => 'tipo de curso',
            'sector_description' => 'sector',
            'price' => 'precio',
            'teacher_id' => 'id del docente',
            'certificate_title' => 'título del certificado',
            'idempotency_key' => 'clave de idempotencia',
        ]);

        $usuarioId = (int) $usuario->getAuthIdentifier();
        $llave = 'academic:create-course:' . $usuarioId;

        // Un reintento con la misma idempotency_key devuelve el curso ya creado:
        // no debe gastar cupo ni quedar bloqueado por el límite de la hora.
        $existente = $this->authoring()->existingDraft($datos['idempotency_key'] ?? null, $usuarioId);

        if (! $existente && RateLimiter::tooManyAttempts($llave, self::MAX_PER_HOUR)) {
            return Response::error('Se alcanzó el límite de ' . self::MAX_PER_HOUR . ' cursos creados por hora. Revisa los borradores existentes con academic_list_courses antes de crear otro.');
        }

        [$curso, $reutilizado] = $this->authoring()->createDraft($datos, $usuarioId);

        // El cupo se gasta solo cuando el curso se crea de verdad.
        if (! $reutilizado) {
            RateLimiter::hit($llave, 3600);
        }

        // Si la clave ya existía, se avisa cuando los datos enviados no
        // coinciden con el curso guardado: un reintento no debe cambiar el
        // contenido en silencio.
        $diferencias = $existente ? $this->diferenciasConCurso($existente, $datos) : [];

        return $this->json([
            'created' => ! $reutilizado,
            'idempotent_replay' => $reutilizado,
            'idempotency_warning' => $diferencias === []
                ? null
                : 'La idempotency_key ya existía con otros datos; se devolvió el curso original. Revisa las diferencias antes de continuar.',
            'differences' => $diferencias,
            'course' => [
                'id' => $curso->id,
                'title' => $curso->description,
                'slug' => $curso->slug,
                'status' => 'borrador',
                'url' => route('aca_courses_information', $curso->id),
            ],
            'next_step' => 'Continúa con academic_add_modules usando course_id = ' . $curso->id . '. Recuerda que el curso queda en borrador hasta que una persona lo active en el ERP.',
        ]);
    }

    /**
     * Campos en los que el curso guardado difiere de la nueva petición.
     *
     * @return array<int, array{campo: string, guardado: mixed, enviado: mixed}>
     */
    private function diferenciasConCurso(\Modules\Academic\Entities\AcaCourse $curso, array $datos): array
    {
        $fechaGuardada = trim($curso->course_year . '-' . $curso->course_month . '-' . $curso->course_day, '-');
        $fechaEnviada = date('Y-m-d', strtotime((string) $datos['start_date']));

        $comparaciones = [
            'title' => [(string) $curso->description, trim((string) $datos['title'])],
            'category_id' => [(string) $curso->category_id, (string) $datos['category_id']],
            'modality_id' => [(string) $curso->modality_id, (string) $datos['modality_id']],
            'type_description' => [(string) $curso->type_description, (string) $datos['type_description']],
            'sector_description' => [(string) $curso->sector_description, (string) ($datos['sector_description'] ?? '')],
            'price' => [(string) (float) $curso->price, (string) (float) ($datos['price'] ?? 0)],
            'teacher_id' => [(string) $curso->teacher_id, (string) ($datos['teacher_id'] ?? '')],
            'start_date' => [$fechaGuardada, $fechaEnviada],
        ];

        if (array_key_exists('certificate_title', $datos) && filled($datos['certificate_title'])) {
            $comparaciones['certificate_title'] = [(string) $curso->certificate_title, trim((string) $datos['certificate_title'])];
        }

        $diferencias = [];

        foreach ($comparaciones as $campo => [$guardado, $enviado]) {
            if ($guardado !== $enviado) {
                $diferencias[] = ['campo' => $campo, 'guardado' => $guardado, 'enviado' => $enviado];
            }
        }

        return $diferencias;
    }
}
