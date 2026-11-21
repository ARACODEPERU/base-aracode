<?php

namespace Modules\Academic\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Entities\AcaBrochure;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaCourseLanding;

/**
 * Cambios permitidos sobre cursos ya activos.
 *
 * Las herramientas de borrador siguen usando CourseAuthoringService. Este
 * servicio solo acepta un conjunto cerrado de cambios y bloquea los campos
 * financieros y el borrado de estructuras académicas. No activa, desactiva ni
 * elimina cursos.
 */
class ActiveCourseEditingService
{
    /** Campos de curso que un agente puede cambiar en un curso activo. */
    public const CAMPOS_CURSO = [
        'title' => 'description',
        'category_id' => 'category_id',
        'modality_id' => 'modality_id',
        'sector_description' => 'sector_description',
        'teacher_id' => 'teacher_id',
        'certificate_title' => 'certificate_title',
        'certificate_description' => 'certificate_description',
    ];

    /** Campos críticos que nunca se aceptan desde esta vía. */
    public const CAMPOS_PROHIBIDOS = [
        'price',
        'discount',
        'discount_applies',
        'type_description',
        'auto_certificate',
        'round_grades',
        'usine',
        'status',
        'slug',
        'image',
        'idempotency_key',
    ];

    /** Claves de landing que no se pueden modificar desde MCP. */
    public const CAMPOS_LANDING_PROHIBIDOS = [
        'investment',
        'investment_section',
        'url_slug',
        'is_published',
        'price_now',
        'price_before',
        'price_now_text',
        'price_before_text',
        'price_before_visible',
    ];

    public function __construct(
        private CourseAuthoringService $authoring,
        private CourseLandingService $landing
    ) {
    }

    /**
     * Comprueba que el curso exista y esté activo. Las herramientas de activos
     * no deben usarse sobre borradores: para ellos ya existen las herramientas
     * normales.
     */
    public function cursoActivo(int $courseId): AcaCourse
    {
        $curso = AcaCourse::find($courseId);

        if (! $curso) {
            throw ValidationException::withMessages([
                'course_id' => 'No existe un curso con ese id.',
            ]);
        }

        if (! $curso->status) {
            throw ValidationException::withMessages([
                'course_id' => 'El curso está en borrador. Usa las herramientas normales de edición; esta vía es solo para cursos activos.',
            ]);
        }

        return $curso;
    }

    /**
     * Describe los cambios de curso sin escribir. Sirve para que el agente
     * muestre el antes/después y pida confirmación.
     *
     * @return array<string, mixed>
     */
    public function describirCurso(AcaCourse $curso, array $datos): array
    {
        $this->rechazarCamposProhibidos($datos);

        $cambios = [];
        $antes = [];
        $despues = [];

        foreach (self::CAMPOS_CURSO as $entrada => $columna) {
            if (! array_key_exists($entrada, $datos)) {
                continue;
            }

            $valor = $datos[$entrada];
            $anterior = $curso->{$columna};
            $nuevo = $entrada === 'title'
                ? trim((string) $valor)
                : ($entrada === 'sector_description' || $entrada === 'certificate_description'
                    ? (blank($valor) ? null : trim((string) $valor))
                    : $valor);

            $cambios[$columna] = $nuevo;
            $antes[$entrada] = $anterior;
            $despues[$entrada] = $nuevo;
        }

        if (array_key_exists('start_date', $datos)) {
            $marca = strtotime((string) $datos['start_date']);

            if ($marca === false) {
                throw ValidationException::withMessages([
                    'start_date' => 'La fecha de inicio no es válida.',
                ]);
            }

            $cambios['course_day'] = date('d', $marca);
            $cambios['course_month'] = date('m', $marca);
            $cambios['course_year'] = date('Y', $marca);
            $antes['start_date'] = $this->fechaCurso($curso);
            $despues['start_date'] = date('Y-m-d', $marca);
        }

        if ($cambios === []) {
            throw ValidationException::withMessages([
                'course_id' => 'No enviaste ningún cambio permitido para el curso activo.',
            ]);
        }

        return [
            'course_id' => $curso->id,
            'title' => $curso->description,
            'before' => $antes,
            'after' => $despues,
            'changes' => $cambios,
            'protected' => [
                'price' => 'El precio y el descuento no se modifican desde MCP.',
                'status' => 'La activación y desactivación se hacen desde el ERP.',
                'type_description' => 'El tipo de curso no se cambia por MCP porque afecta el acceso de los alumnos.',
                'slug' => 'La URL del curso se conserva aunque cambie el título.',
                'deletion' => 'No se puede eliminar el curso ni sus módulos, clases, contenidos o exámenes.',
            ],
        ];
    }

    /**
     * Aplica cambios de curso ya descritos. Usa updateQuietly para no disparar
     * la regeneración automática del slug al cambiar el título.
     *
     * @return array<string, mixed>
     */
    public function actualizarCurso(AcaCourse $curso, array $datos): array
    {
        $this->cursoActivo((int) $curso->id);

        $descripcion = $this->describirCurso($curso, $datos);
        $cambios = $descripcion['changes'];

        DB::transaction(function () use ($curso, $cambios) {
            $curso->updateQuietly($cambios);
        });

        $curso->refresh();

        return [
            'course_id' => $curso->id,
            'title' => $curso->description,
            'slug' => $curso->slug,
            'status' => $curso->status ? 'activo' : 'borrador',
            'before' => $descripcion['before'],
            'after' => $descripcion['after'],
            'updated_fields' => array_keys($cambios),
            'protected' => $descripcion['protected'],
        ];
    }

    /**
     * Cambia el contenido de una clase activa sin borrar el registro. Las
     * operaciones permitidas son texto, archivo y enlace/conversión de tipo.
     *
     * @return array<string, mixed>
     */
    public function cambiarContenido(AcaContent $contenido, array $datos): array
    {
        $contenido->loadMissing('theme.module.course');
        $curso = $contenido->theme?->module?->course;

        if (! $curso) {
            throw ValidationException::withMessages([
                'content_id' => 'El contenido no pertenece a un curso válido.',
            ]);
        }

        $this->cursoActivo((int) $curso->id);

        $operacion = (string) $datos['operation'];
        $antes = $this->resumenContenido($contenido);

        if ($operacion === 'update_text') {
            $contenido = $this->authoring->updateContent($contenido, array_filter([
                'title' => $datos['title'] ?? null,
                'html' => $datos['html'] ?? null,
            ], fn ($valor) => $valor !== null));
        } elseif ($operacion === 'replace_file') {
            $contenido = $this->authoring->replaceLessonFile($contenido, $datos);
        } elseif ($operacion === 'replace_link') {
            $contenido = $this->authoring->replaceLessonLink($contenido, $datos);
        } else {
            throw ValidationException::withMessages([
                'operation' => 'Operación no permitida. Usa update_text, replace_file o replace_link.',
            ]);
        }

        return [
            'content_id' => $contenido->id,
            'course_id' => $curso->id,
            'before' => $antes,
            'after' => $this->resumenContenido($contenido->fresh()),
            'old_content' => $antes['content_preview'],
            'note' => 'Se reemplazó el contenido de la misma fila. No se eliminó el contenido, la clase, el módulo ni el curso.',
        ];
    }

    /**
     * Actualiza una landing activa con campos permitidos. No acepta inversión,
     * precios, publicación ni cambio de slug.
     *
     * @return array<string, mixed>
     */
    public function actualizarLanding(AcaCourse $curso, array $datos): array
    {
        $this->cursoActivo((int) $curso->id);

        $landing = AcaCourseLanding::where('course_id', $curso->id)->first();

        if (! $landing) {
            throw ValidationException::withMessages([
                'course_id' => 'El curso activo no tiene landing. Créala y revísala desde el ERP.',
            ]);
        }

        $this->rechazarCamposLanding($datos);

        $actualizada = $this->landing->update($curso, $datos);

        return [
            'course_id' => $curso->id,
            'landing' => $this->landing->state($actualizada),
            'updated_fields' => array_values(array_diff(array_keys($datos), ['confirm', 'reason'])),
            'protected' => [
                'investment' => 'Los precios y la sección de inversión no se modifican desde MCP.',
                'is_published' => 'Publicar o despublicar se hace desde el ERP.',
                'url_slug' => 'La URL pública de la landing no se cambia desde MCP.',
            ],
        ];
    }

    /**
     * Reemplaza o elimina únicamente la portada del curso. Nunca elimina el
     * curso.
     *
     * @return array<string, mixed>
     */
    public function cambiarImagen(AcaCourse $curso, string $accion, ?string $imagenBase64OUrl): array
    {
        $this->cursoActivo((int) $curso->id);

        $anterior = $curso->image;

        if ($accion === 'remove') {
            if (blank($anterior)) {
                throw ValidationException::withMessages([
                    'image' => 'El curso no tiene una imagen de portada para eliminar.',
                ]);
            }

            $curso->image = null;
            $curso->saveQuietly();
            $this->borrarImagenLocal($anterior);

            return [
                'course_id' => $curso->id,
                'action' => 'remove',
                'before' => $anterior,
                'after' => null,
                'note' => 'Se eliminó solo la imagen de portada; el curso, sus módulos y sus contenidos siguen intactos.',
            ];
        }

        if ($accion !== 'replace') {
            throw ValidationException::withMessages([
                'action' => 'Acción no permitida. Usa replace o remove.',
            ]);
        }

        if (blank($imagenBase64OUrl)) {
            throw ValidationException::withMessages([
                'image_base64' => 'Envía image_base64 o image_url para reemplazar la portada.',
            ]);
        }

        $ruta = $this->authoring->setImage($curso, (string) $imagenBase64OUrl);

        if (filled($anterior) && $anterior !== $ruta) {
            $this->borrarImagenLocal($anterior);
        }

        return [
            'course_id' => $curso->id,
            'action' => 'replace',
            'before' => $anterior,
            'after' => $ruta,
            'url' => asset('storage/' . $ruta),
            'note' => 'Se reemplazó solo la portada. El resto del curso no cambió.',
        ];
    }

    /**
     * Reemplaza el PDF del brochure de un curso activo. No borra el curso ni
     * cambia precios.
     *
     * @return array<string, mixed>
     */
    public function reemplazarBrochure(AcaCourse $curso, array $datos): array
    {
        $this->cursoActivo((int) $curso->id);

        $anterior = AcaBrochure::where('course_id', $curso->id)->value('path_file');
        $brochure = $this->landing->setBrochureFile($curso, $datos);

        return [
            'course_id' => $curso->id,
            'before' => $anterior,
            'after' => $brochure->path_file,
            'note' => 'Se reemplazó el PDF del brochure. El curso y sus contenidos no se eliminaron.',
        ];
    }

    /**
     * Rechaza campos de curso que no están en la lista permitida y que además
     * son críticos. Así un request no puede colar un precio entre los cambios.
     */
    private function rechazarCamposProhibidos(array $datos): void
    {
        $prohibidos = array_values(array_intersect(array_keys($datos), self::CAMPOS_PROHIBIDOS));

        if ($prohibidos !== []) {
            throw ValidationException::withMessages([
                'course_id' => 'No se permite cambiar por MCP: ' . implode(', ', $prohibidos) . '. El precio, descuento, tipo y estado se editan desde el ERP.',
            ]);
        }
    }

    /**
     * Rechaza la sección de inversión/precios, publicación y slug, incluso si
     * llegan anidados en una estructura no validada.
     */
    public function rechazarCamposLanding(array $datos): void
    {
        $encontrados = [];
        $this->buscarClavesProhibidas($datos, $encontrados);
        $encontrados = array_values(array_unique($encontrados));

        if ($encontrados !== []) {
            throw ValidationException::withMessages([
                'landing' => 'La landing activa no admite por MCP: ' . implode(', ', $encontrados) . '. Los precios, la publicación y el slug se editan desde el ERP.',
            ]);
        }
    }

    private function buscarClavesProhibidas(array $datos, array &$encontrados): void
    {
        foreach ($datos as $clave => $valor) {
            if (in_array((string) $clave, self::CAMPOS_LANDING_PROHIBIDOS, true)) {
                $encontrados[] = (string) $clave;
            }

            if (is_array($valor)) {
                $this->buscarClavesProhibidas($valor, $encontrados);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resumenContenido(AcaContent $contenido): array
    {
        $valor = (string) $contenido->content;

        return [
            'id' => $contenido->id,
            'title' => $contenido->description,
            'type' => CourseAuthoringService::contentType($contenido),
            'is_file' => (int) $contenido->is_file,
            'content_preview' => mb_strlen($valor) > 300 ? mb_substr($valor, 0, 300) . '…' : $valor,
        ];
    }

    private function fechaCurso(AcaCourse $curso): ?string
    {
        if (blank($curso->course_year) || blank($curso->course_month) || blank($curso->course_day)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $curso->course_year, $curso->course_month, $curso->course_day);
    }

    private function borrarImagenLocal(?string $ruta): void
    {
        if (filled($ruta) && str_starts_with((string) $ruta, 'uploads/')) {
            Storage::disk('public')->delete((string) $ruta);
        }
    }
}
