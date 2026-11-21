<?php

namespace Modules\Academic\Services;

use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Entities\AcaBrochure;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\AcaExamAnswer;
use Modules\Academic\Entities\AcaExamQuestion;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaTeacher;
use Modules\Academic\Entities\AcaTeacherCourse;
use Modules\Academic\Entities\AcaTheme;

/**
 * Creación de cursos paso a paso: borrador, módulos, clases y contenidos.
 *
 * Es el punto único que usan las herramientas MCP (y que puede reutilizar
 * cualquier otro canal) para que lo creado por un agente cumpla las mismas
 * reglas que el formulario web: posiciones numéricas, longitudes de columna,
 * HTML saneado y contenido guardado con htmlentities (AcaContent lo devuelve
 * decodificado al leerlo).
 */
class CourseAuthoringService
{
    /** Tope de módulos por curso y por llamada. */
    public const MAX_MODULES = 20;

    /** Tope de clases por módulo y por llamada. */
    public const MAX_LESSONS_PER_MODULE = 40;

    /** Tope de contenidos por clase y por llamada. */
    public const MAX_CONTENTS_PER_LESSON = 20;

    /** Tope de caracteres HTML por contenido (corte rápido antes de codificar). */
    public const MAX_HTML_CHARS = 120000;

    /**
     * Tope real en bytes del HTML codificado: aca_contents.content es TEXT y
     * admite 65 535 bytes. Se deja un margen para no rozar el límite.
     */
    public const MAX_HTML_BYTES = 65000;

    /** Tope de archivos adjuntos por llamada. */
    public const MAX_FILES_PER_CALL = 10;

    /** Tope de tamaño por archivo (10 MB, igual que el PDF de respuestas del ERP). */
    public const MAX_FILE_BYTES = 10485760;

    /** Extensiones admitidas en los materiales descargables. */
    public const FILE_EXTENSIONS = ['pdf', 'xlsx', 'xls', 'doc', 'docx'];

    /**
     * Tipos de aca_contents.is_file, tal como los interpreta la web del alumno.
     * 0 se renderiza con v-html (texto enriquecido o iframe de video).
     */
    public const CONTENT_TYPES = [
        0 => 'video_embebido',
        1 => 'enlace_archivo',
        2 => 'archivo',
        3 => 'videoconferencia',
        4 => 'examen',
        5 => 'grabacion',
        6 => 'texto',
    ];

    /**
     * Etiqueta real del contenido. El tipo 0 es el frame de video, pero los
     * textos que el MCP guardó antes con 0 no tienen iframe: se informan como
     * texto para no confundir al agente.
     */
    public static function contentType(AcaContent $contenido): string
    {
        $tipo = (int) $contenido->is_file;

        if ($tipo === 0 && ! str_contains((string) $contenido->content, '<iframe')) {
            return 'texto';
        }

        return self::CONTENT_TYPES[$tipo] ?? 'desconocido';
    }

    /**
     * Firma de los bytes de cada extensión: evita guardar un archivo renombrado
     * con una extensión que no le corresponde.
     */
    private const FILE_SIGNATURES = [
        'pdf' => '%PDF-',
        'xlsx' => "PK\x03\x04",
        'xls' => "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1",
        'doc' => "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1",
        'docx' => "PK\x03\x04",
    ];

    /** Dominios aceptados para cada tipo de enlace de clase. */
    private const LINK_DOMAINS = [
        'zoom' => ['zoom.us'],
        'meet' => ['meet.google.com'],
        'teams' => ['teams.microsoft.com', 'teams.live.com', 'teams.cloud.microsoft'],
        'vimeo' => ['vimeo.com', 'player.vimeo.com'],
        'youtube' => ['youtube.com', 'youtu.be', 'youtube-nocookie.com'],
        'archivo' => [],
    ];

    /**
     * Etiquetas que se conservan del HTML que escribe el agente. El editor de
     * la web (TinyMCE) trabaja con este mismo repertorio.
     */
    private const ALLOWED_TAGS = '<p><br><h1><h2><h3><h4><h5><h6><ul><ol><li><strong><b><em><i><u><s>'
        . '<blockquote><table><thead><tbody><tfoot><tr><th><td><a><span><code><pre><hr>';

    /**
     * Crea el curso en borrador.
     *
     * Con idempotency_key devuelve el curso ya creado con esa misma clave por
     * el mismo usuario en lugar de duplicarlo.
     *
     * @return array{0: AcaCourse, 1: bool} El curso y si se reutilizó uno existente.
     */
    public function createDraft(array $datos, int $userId): array
    {
        $huella = $this->idempotencyFingerprint($datos['idempotency_key'] ?? null, $userId);

        if ($huella !== null) {
            $existente = AcaCourse::where('idempotency_key', $huella)->first();

            if ($existente) {
                return [$existente, true];
            }
        }

        $marca = strtotime((string) $datos['start_date']);
        $titulo = trim((string) $datos['title']);

        try {
            $curso = AcaCourse::create([
                // Todo curso nace en borrador: lo activa una persona desde el ERP.
                'status' => false,
                'created_via' => 'mcp',
                'idempotency_key' => $huella,
                'description' => $titulo,
                'course_day' => date('d', $marca),
                'course_month' => date('m', $marca),
                'course_year' => date('Y', $marca),
                'category_id' => $datos['category_id'],
                'modality_id' => $datos['modality_id'],
                'type_description' => $datos['type_description'],
                'sector_description' => $datos['sector_description'] ?? null,
                'teacher_id' => $datos['teacher_id'] ?? null,
                'price' => $datos['price'] ?? 0,
                'discount' => $datos['discount'] ?? 0,
                'discount_applies' => $datos['discount_applies'] ?? null,
                'usine' => $datos['usine'] ?? null,
                'auto_certificate' => (bool) ($datos['auto_certificate'] ?? false),
                'round_grades' => (bool) ($datos['round_grades'] ?? false),
                'certificate_description' => $datos['certificate_description'] ?? null,
                // certificate_title es NOT NULL en la tabla: si no viene, se usa el nombre.
                'certificate_title' => trim((string) ($datos['certificate_title'] ?? '')) ?: $titulo,
            ]);
        } catch (QueryException $excepcion) {
            // Dos reintentos simultáneos con la misma clave chocan contra el
            // índice único: el primero crea el curso y el segundo devuelve ese
            // mismo curso en lugar de fallar con un error de base de datos.
            $existente = $huella !== null
                ? AcaCourse::where('idempotency_key', $huella)->first()
                : null;

            if ($existente) {
                return [$existente, true];
            }

            throw $excepcion;
        }

        return [$curso, false];
    }

    /**
     * Curso ya creado con esa clave por el mismo usuario, si existe. Sirve
     * para que un reintento idempotente no gaste cupo de creación.
     */
    public function existingDraft(?string $clave, int $userId): ?AcaCourse
    {
        $huella = $this->idempotencyFingerprint($clave, $userId);

        return $huella === null ? null : AcaCourse::where('idempotency_key', $huella)->first();
    }

    /**
     * Última posición numérica de una relación. aca_modules, aca_themes y
     * aca_contents guardan position como texto, así que un MAX() de SQL
     * compara como cadena («9» > «10») y repetiría la posición 10 en el
     * registro número once. Aquí se convierten los valores a entero.
     */
    protected function lastPosition($relation): int
    {
        return (int) $relation->pluck('position')
            ->map(fn ($posicion) => (int) $posicion)
            ->max();
    }

    /**
     * Agrega módulos al final del curso respetando la numeración existente.
     *
     * @return array<int, AcaModule>
     */
    public function addModules(AcaCourse $curso, array $modulos): array
    {
        return DB::transaction(function () use ($curso, $modulos) {
            $creados = [];
            $posicion = $this->lastPosition($curso->modules());

            foreach ($modulos as $modulo) {
                $posicion = isset($modulo['position']) ? (int) $modulo['position'] : $posicion + 1;

                $creados[] = AcaModule::create([
                    'course_id' => $curso->id,
                    'position' => (string) $posicion,
                    'description' => trim((string) $modulo['title']),
                    'allow_certificate_download' => false,
                ]);
            }

            return $creados;
        });
    }

    /**
     * Agrega clases (aca_themes) al final del módulo.
     *
     * @return array<int, AcaTheme>
     */
    public function addLessons(AcaModule $modulo, array $clases): array
    {
        return DB::transaction(function () use ($modulo, $clases) {
            $creadas = [];
            $posicion = $this->lastPosition($modulo->themes());

            foreach ($clases as $clase) {
                $posicion = isset($clase['position']) ? (int) $clase['position'] : $posicion + 1;

                $creadas[] = AcaTheme::create([
                    'module_id' => $modulo->id,
                    'position' => (string) $posicion,
                    'description' => trim((string) $clase['title']),
                ]);
            }

            return $creadas;
        });
    }

    /**
     * Agrega contenidos de texto a una clase.
     *
     * @return array<int, AcaContent>
     */
    public function setContents(AcaTheme $tema, array $contenidos): array
    {
        return DB::transaction(function () use ($tema, $contenidos) {
            $creados = [];
            $posicion = $this->lastPosition($tema->contents());

            foreach ($contenidos as $contenido) {
                $posicion = isset($contenido['position']) ? (int) $contenido['position'] : $posicion + 1;
                $html = $this->encodeHtml(static::sanitizeHtml((string) $contenido['html']));

                $registro = AcaContent::create([
                    'theme_id' => $tema->id,
                    'position' => (string) $posicion,
                    'description' => trim((string) $contenido['title']),
                    // 6 = texto/HTML. El 0 está reservado para el iframe de video.
                    'is_file' => 6,
                ]);

                $registro->content = $html;
                $registro->save();

                $creados[] = $registro;
            }

            return $creados;
        });
    }

    /**
     * Guarda la portada del curso desde una imagen en base64 (data URI) o una
     * URL https pública. Devuelve la ruta guardada en el disco público.
     */
    public function setImage(AcaCourse $curso, string $imagen): string
    {
        $contenido = $this->readImage($imagen);

        if (strlen($contenido) > 3 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'image_base64' => 'La imagen supera los 3 MB. Envía una versión más ligera.',
            ]);
        }

        $informacion = @getimagesizefromstring($contenido);
        $mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = is_array($informacion) ? ($informacion['mime'] ?? '') : '';

        if (! isset($mimes[$mime])) {
            throw ValidationException::withMessages([
                'image_base64' => 'El archivo no es una imagen JPG, PNG o WebP válida.',
            ]);
        }

        if (($informacion[0] ?? 0) < 600 || ($informacion[1] ?? 0) < 300) {
            throw ValidationException::withMessages([
                'image_base64' => 'La portada debe medir al menos 600x300 píxeles.',
            ]);
        }

        $ruta = 'uploads/courses/' . uniqid('curso-', true) . '.' . $mimes[$mime];
        Storage::disk('public')->put($ruta, $contenido);

        $curso->image = $ruta;
        $curso->save();

        return $ruta;
    }

    /**
     * Guarda una imagen de una sección de la landing (plan de estudios o
     * testimonio) en el disco público. Acepta data URI o URL https pública.
     */
    public function storeLandingImage(string $imagen, string $carpeta, int $maxBytes = 5242880): string
    {
        $contenido = $this->readImage($imagen);

        if (strlen($contenido) > $maxBytes) {
            throw ValidationException::withMessages([
                'image' => 'La imagen supera el límite de ' . (int) ($maxBytes / 1024 / 1024) . ' MB.',
            ]);
        }

        $informacion = @getimagesizefromstring($contenido);
        $mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = is_array($informacion) ? ($informacion['mime'] ?? '') : '';

        if (! isset($mimes[$mime])) {
            throw ValidationException::withMessages([
                'image' => 'El archivo no es una imagen JPG, PNG o WebP válida.',
            ]);
        }

        $carpeta = trim($carpeta, '/');
        $ruta = 'uploads/landing/' . $carpeta . '/' . uniqid('img-', true) . '.' . $mimes[$mime];
        Storage::disk('public')->put($ruta, $contenido);

        return $ruta;
    }

    /**
     * Guarda el PDF del brochure y lo enlaza al registro del curso. El archivo
     * se valida con la misma firma y tamaño que los materiales de clase.
     */
    public function setBrochureFile(AcaCourse $curso, array $datos): AcaBrochure
    {
        [$bytes, $extension] = $this->readMaterial([
            'filename' => $datos['filename'] ?? 'brochure.pdf',
            'file_base64' => $datos['file_base64'] ?? '',
            'file_url' => $datos['file_url'] ?? '',
        ]);

        if ($extension !== 'pdf') {
            throw ValidationException::withMessages([
                'file_base64' => 'El brochure debe ser un archivo PDF.',
            ]);
        }

        $ruta = 'uploads/brochures/' . uniqid('brochure-', true) . '.pdf';
        Storage::disk('public')->put($ruta, $bytes);

        return AcaBrochure::updateOrCreate(
            ['course_id' => $curso->id],
            ['path_file' => asset('storage/' . $ruta)]
        );
    }

    /**
     * Reemplaza los docentes del curso y define el encargado principal. Los
     * ids se validan contra la tabla real para que el agente no invente
     * personas.
     *
     * @return array<int, array{id:int, name:string, code:?string}>
     */
    public function setTeachers(AcaCourse $curso, array $teacherIds, ?int $mainTeacherId = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $teacherIds))));
        $docentes = AcaTeacher::with('person')->whereIn('id', $ids)->get()->keyBy('id');

        if ($docentes->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'teacher_ids' => 'Uno o más docentes no existen en el ERP. Usa academic_list_teachers.',
            ]);
        }

        if ($mainTeacherId !== null && ! in_array($mainTeacherId, $ids, true)) {
            throw ValidationException::withMessages([
                'main_teacher_id' => 'El docente encargado debe estar incluido en teacher_ids.',
            ]);
        }

        // Si no se indicó encargado, el primero del equipo queda como principal.
        $mainTeacherId = $mainTeacherId ?? ($ids[0] ?? null);

        DB::transaction(function () use ($curso, $ids, $mainTeacherId) {
            AcaTeacherCourse::where('course_id', $curso->id)->delete();

            foreach ($ids as $teacherId) {
                AcaTeacherCourse::create([
                    'course_id' => $curso->id,
                    'teacher_id' => $teacherId,
                ]);
            }

            $curso->teacher_id = $mainTeacherId;
            $curso->save();
        });

        return $docentes->map(fn (AcaTeacher $docente) => [
            'id' => $docente->id,
            'name' => $docente->person?->full_name ?? 'Docente sin nombre',
            'code' => $docente->teacher_code,
        ])->values()->all();
    }

    /**
     * Crea el examen regular de un contenido. Queda inactivo hasta que una
     * persona lo revise junto con el curso.
     */
    public function createExam(AcaContent $contenido, array $datos): AcaExam
    {
        $tema = $contenido->theme()->with('module')->first();

        return AcaExam::create([
            'course_id' => $tema?->module?->course_id,
            'module_id' => $tema?->module_id,
            'theme_id' => $contenido->theme_id,
            'content_id' => $contenido->id,
            'description' => trim((string) $datos['title']),
            'date_start' => $datos['date_start'] ?? null,
            'date_end' => $datos['date_end'] ?? null,
            'status' => false,
            'is_mock' => false,
            'attempts' => $datos['attempts'] ?? 1,
            'duration_minutes' => $datos['duration_minutes'] ?? 120,
        ]);
    }

    /**
     * Agrega preguntas con sus opciones a un examen.
     *
     * Respeta las reglas del mantenedor: una sola respuesta correcta en
     * «Alternativas» y suma de puntajes correctos que no supere la pregunta en
     * «Varias respuestas».
     *
     * @return array<int, AcaExamQuestion>
     */
    public function addExamQuestions(AcaExam $examen, array $preguntas): array
    {
        return DB::transaction(function () use ($examen, $preguntas) {
            $creadas = [];

            foreach (array_values($preguntas) as $indice => $pregunta) {
                $numero = $indice + 1;
                $tipo = (string) $pregunta['type'];
                $puntaje = (int) $pregunta['score'];
                $opciones = $pregunta['options'] ?? [];

                if (in_array($tipo, ['Alternativas', 'Varias respuestas'], true) && count($opciones) < 2) {
                    throw ValidationException::withMessages([
                        'questions' => "La pregunta {$numero} necesita al menos dos opciones.",
                    ]);
                }

                $correctas = array_values(array_filter($opciones, fn (array $opcion) => ! empty($opcion['correct'])));

                if ($tipo === 'Alternativas' && count($correctas) !== 1) {
                    throw ValidationException::withMessages([
                        'questions' => "La pregunta {$numero} (Alternativas) debe tener exactamente una opción correcta.",
                    ]);
                }

                if ($tipo === 'Varias respuestas' && count($correctas) < 1) {
                    throw ValidationException::withMessages([
                        'questions' => "La pregunta {$numero} (Varias respuestas) necesita al menos una opción correcta.",
                    ]);
                }

                if ($correctas !== [] && array_sum(array_map(fn (array $opcion) => (int) ($opcion['score'] ?? $puntaje), $correctas)) > $puntaje) {
                    throw ValidationException::withMessages([
                        'questions' => "La pregunta {$numero}: la suma de puntajes de las respuestas correctas supera los {$puntaje} puntos de la pregunta.",
                    ]);
                }

                $registro = AcaExamQuestion::create([
                    'exam_id' => $examen->id,
                    'description' => trim((string) $pregunta['statement']),
                    'score' => $puntaje,
                    'type_answers' => $tipo,
                ]);

                foreach ($opciones as $opcion) {
                    AcaExamAnswer::create([
                        'question_id' => $registro->id,
                        'description' => trim((string) $opcion['text']),
                        'correct' => ! empty($opcion['correct']),
                        'score' => (int) ($opcion['score'] ?? (! empty($opcion['correct']) ? $puntaje : 0)),
                    ]);
                }

                $creadas[] = $registro;
            }

            return $creadas;
        });
    }

    /**
     * Agrega archivos PDF, Excel o Word a una clase y los guarda en el disco
     * público, igual que la opción «Subir Archivo» del mantenedor. El agente
     * puede enviarlos en base64 o como URL https pública.
     *
     * @return array<int, AcaContent>
     */
    public function addLessonFiles(AcaTheme $tema, array $archivos): array
    {
        return DB::transaction(function () use ($tema, $archivos) {
            $creados = [];
            $posicion = $this->lastPosition($tema->contents());

            foreach ($archivos as $archivo) {
                $posicion = isset($archivo['position']) ? (int) $archivo['position'] : $posicion + 1;
                [$bytes, $extension] = $this->readMaterial($archivo);

                $ruta = 'uploads/courses/content/' . uniqid('material-', true) . '.' . $extension;
                Storage::disk('public')->put($ruta, $bytes);

                $registro = AcaContent::create([
                    'theme_id' => $tema->id,
                    'position' => (string) $posicion,
                    'description' => trim((string) $archivo['title']),
                    // 2 = archivo subido: la web lo descarga desde storage.
                    'is_file' => 2,
                ]);

                $registro->content = $ruta;
                $registro->save();

                $creados[] = $registro;
            }

            return $creados;
        });
    }

    /**
     * Agrega enlaces a una clase: videoconferencia (Zoom, Meet o Teams), video
     * embebido (Vimeo o YouTube) o un enlace a un archivo externo.
     *
     * Para Vimeo y YouTube se guarda el iframe ya armado, que es lo que la web
     * del alumno muestra con v-html; el agente nunca inyecta HTML propio aquí.
     * El texto de una clase se guarda aparte, con is_file = 6, para que nunca
     * termine dentro de un contenido de video.
     *
     * @return array<int, AcaContent>
     */
    public function addLessonLinks(AcaTheme $tema, array $enlaces): array
    {
        return DB::transaction(function () use ($tema, $enlaces) {
            $creados = [];
            $posicion = $this->lastPosition($tema->contents());

            foreach ($enlaces as $enlace) {
                $posicion = isset($enlace['position']) ? (int) $enlace['position'] : $posicion + 1;
                $tipo = strtolower((string) $enlace['type']);
                $url = $this->publicUrl((string) $enlace['url']);

                $this->assertLinkDomain($tipo, $url);

                $contenido = in_array($tipo, ['vimeo', 'youtube'], true)
                    ? $this->videoEmbed($tipo, $url)
                    : $url;

                $registro = AcaContent::create([
                    'theme_id' => $tema->id,
                    'position' => (string) $posicion,
                    'description' => trim((string) $enlace['title']),
                    // 0 = frame de video, 3 = videoconferencia, 1 = enlace externo.
                    'is_file' => in_array($tipo, ['zoom', 'meet', 'teams'], true)
                        ? 3
                        : ($tipo === 'archivo' ? 1 : 0),
                ]);

                $registro->content = $contenido;
                $registro->save();

                $creados[] = $registro;
            }

            return $creados;
        });
    }

    /**
     * Crea o actualiza el examen de un módulo, regular o simulacro, con la
     * misma clave que usa el mantenedor del ERP: curso + módulo + tipo.
     *
     * @return array{0: AcaExam, 1: bool} El examen y si se creó ahora.
     */
    public function createModuleExam(AcaModule $modulo, array $datos, bool $simulacro): array
    {
        $examen = AcaExam::updateOrCreate(
            [
                'course_id' => $modulo->course_id,
                'module_id' => $modulo->id,
                // null evita pisar un examen colgado de un contenido del módulo.
                'content_id' => null,
                'is_mock' => $simulacro,
            ],
            [
                'description' => trim((string) $datos['title']),
                'date_start' => $datos['date_start'] ?? null,
                'date_end' => $datos['date_end'] ?? null,
                'duration_minutes' => $datos['duration_minutes'] ?? 120,
                'attempts' => $datos['attempts'] ?? 1,
                // Nace inactivo: se publica cuando una persona activa el curso.
                'status' => false,
            ]
        );

        return [$examen, $examen->wasRecentlyCreated];
    }

    /**
     * Crea o actualiza el examen final del curso (sin módulo ni contenido),
     * igual que el mantenedor del ERP.
     *
     * @return array{0: AcaExam, 1: bool}
     */
    public function createFinalExam(AcaCourse $curso, array $datos): array
    {
        $examen = AcaExam::updateOrCreate(
            [
                'course_id' => $curso->id,
                'module_id' => null,
                'content_id' => null,
                'is_mock' => false,
            ],
            [
                'description' => trim((string) $datos['title']),
                'date_start' => $datos['date_start'] ?? null,
                'date_end' => $datos['date_end'] ?? null,
                'duration_minutes' => $datos['duration_minutes'] ?? 120,
                'attempts' => $datos['attempts'] ?? 1,
                'status' => false,
            ]
        );

        return [$examen, $examen->wasRecentlyCreated];
    }

    /**
     * Actualiza el texto de un contenido con el mismo saneado de la creación.
     */
    public function updateContent(AcaContent $contenido, array $datos): AcaContent
    {
        if (array_key_exists('title', $datos)) {
            $contenido->description = trim((string) $datos['title']);
        }

        if (array_key_exists('position', $datos) && $datos['position'] !== null) {
            $contenido->position = (string) $datos['position'];
        }

        if (array_key_exists('html', $datos)) {
            $contenido->content = $this->encodeHtml(static::sanitizeHtml((string) $datos['html']), 'html');
        }

        $contenido->save();

        return $contenido;
    }

    /**
     * Reemplaza el archivo de un contenido de tipo 2 sin crear otra fila. La
     * validación de extensión, tamaño y firma es la misma que al subirlo.
     */
    public function replaceLessonFile(AcaContent $contenido, array $archivo): AcaContent
    {
        if ((int) $contenido->is_file !== 2) {
            throw ValidationException::withMessages([
                'content_id' => 'Solo se puede reemplazar un archivo cuando el contenido actual es de tipo archivo.',
            ]);
        }

        [$bytes, $extension] = $this->readMaterial($archivo);
        $ruta = 'uploads/courses/content/' . uniqid('material-', true) . '.' . $extension;
        Storage::disk('public')->put($ruta, $bytes);

        $contenido->description = trim((string) ($archivo['title'] ?? '')) ?: $contenido->description;
        $contenido->is_file = 2;
        $contenido->content = $ruta;
        $contenido->save();

        return $contenido;
    }

    /**
     * Reemplaza un enlace o convierte su tipo (por ejemplo, videoconferencia a
     * video de YouTube) validando el dominio y guardando el mismo formato que
     * usa la web del alumno.
     */
    public function replaceLessonLink(AcaContent $contenido, array $enlace): AcaContent
    {
        if (! in_array((int) $contenido->is_file, [0, 1, 3, 5], true)) {
            throw ValidationException::withMessages([
                'content_id' => 'Solo se puede reemplazar un enlace cuando el contenido actual es un video, una videoconferencia, una grabación o un enlace externo.',
            ]);
        }

        $tipo = strtolower((string) $enlace['link_type']);
        $url = $this->publicUrl((string) $enlace['url']);
        $this->assertLinkDomain($tipo, $url);

        $contenido->description = trim((string) ($enlace['title'] ?? '')) ?: $contenido->description;
        $contenido->is_file = in_array($tipo, ['zoom', 'meet', 'teams'], true)
            ? 3
            : ($tipo === 'archivo' ? 1 : 0);
        $contenido->content = in_array($tipo, ['vimeo', 'youtube'], true)
            ? $this->videoEmbed($tipo, $url)
            : $url;
        $contenido->save();

        return $contenido;
    }

    /**
     * Crea o actualiza el brochure del curso. Los bloques son HTML del editor,
     * así que pasan por el mismo saneado antes de guardarse.
     */
    public function setBrochure(AcaCourse $curso, array $datos): AcaBrochure
    {
        $valores = [];

        foreach (['resolution', 'presentation', 'benefits', 'frequent_questions', 'curriculum_plan'] as $bloque) {
            if (array_key_exists($bloque, $datos)) {
                $valores[$bloque] = $datos[$bloque] === null
                    ? null
                    : static::sanitizeHtml((string) $datos[$bloque]);
            }
        }

        return AcaBrochure::updateOrCreate(['course_id' => $curso->id], $valores);
    }

    /**
     * Lista de comprobación para revisar un curso antes de activarlo. Devuelve
     * datos legibles para el agente y para la persona que revisa.
     *
     * @return array{ready: bool, checks: array<int, array{item: string, ok: bool, detail: string}>}
     */
    public function validateCourse(AcaCourse $curso): array
    {
        $modulos = $curso->modules()
            ->with([
                'themes.contents.exam.questions.answers',
                'exam.questions.answers',
                'mockExam.questions.answers',
            ])
            ->get();

        $clases = $modulos->flatMap(fn (AcaModule $modulo) => $modulo->themes);
        $contenidos = $clases->flatMap(fn (AcaTheme $clase) => $clase->contents);

        $examenesContenido = $contenidos->map(fn (AcaContent $contenido) => $contenido->exam)->filter();
        $examenesModulo = $modulos->flatMap(fn (AcaModule $modulo) => [$modulo->exam, $modulo->mockExam])->filter();
        $simulacros = $modulos->map(fn (AcaModule $modulo) => $modulo->mockExam)->filter();
        $examenFinal = $this->finalExam($curso);

        $preguntasInvalidas = $this->invalidQuestions(
            $examenesContenido->merge($examenesModulo)->merge([$examenFinal])->filter()->flatMap(fn (AcaExam $examen) => $examen->questions)
        );

        $materialesIncompletos = $contenidos->reject(fn (AcaContent $contenido) => $this->materialComplete($contenido));

        $simulacrosSinPreguntas = $simulacros->filter(fn (AcaExam $examen) => $examen->questions->isEmpty());

        $checks = [
            ['item' => 'portada', 'ok' => filled($curso->image), 'detail' => filled($curso->image) ? 'Portada cargada.' : 'Falta la imagen de portada.'],
            ['item' => 'categoria', 'ok' => filled($curso->category_id), 'detail' => filled($curso->category_id) ? 'Categoría asignada.' : 'Falta la categoría.'],
            ['item' => 'modalidad', 'ok' => filled($curso->modality_id), 'detail' => filled($curso->modality_id) ? 'Modalidad asignada.' : 'Falta la modalidad.'],
            ['item' => 'tipo', 'ok' => filled($curso->type_description), 'detail' => filled($curso->type_description) ? 'Tipo de curso asignado.' : 'Falta el tipo de curso.'],
            ['item' => 'fecha_inicio', 'ok' => filled($curso->course_year) && filled($curso->course_month) && filled($curso->course_day), 'detail' => 'Fecha de inicio del curso.'],
            ['item' => 'certificado', 'ok' => filled($curso->certificate_title), 'detail' => filled($curso->certificate_title) ? 'Título del certificado definido.' : 'Falta el título del certificado.'],
            ['item' => 'modulos', 'ok' => $modulos->isNotEmpty(), 'detail' => $modulos->count() . ' módulo(s).'],
            ['item' => 'clases', 'ok' => $clases->isNotEmpty(), 'detail' => $clases->count() . ' clase(s).'],
            ['item' => 'contenidos', 'ok' => $contenidos->isNotEmpty(), 'detail' => $contenidos->count() . ' contenido(s).'],
            ['item' => 'contenido_vacio', 'ok' => $contenidos->every(fn (AcaContent $contenido) => trim((string) $contenido->content) !== ''), 'detail' => 'Cada contenido tiene texto o archivo.'],
            ['item' => 'materiales', 'ok' => $materialesIncompletos->isEmpty(), 'detail' => $materialesIncompletos->isEmpty() ? 'Archivos y enlaces completos.' : $materialesIncompletos->count() . ' material(es) con enlace o archivo inválido.'],
            ['item' => 'examenes', 'ok' => $preguntasInvalidas->isEmpty(), 'detail' => $preguntasInvalidas->isEmpty() ? 'Exámenes coherentes.' : $preguntasInvalidas->count() . ' pregunta(s) sin respuesta válida.'],
            ['item' => 'simulacros', 'ok' => $simulacrosSinPreguntas->isEmpty(), 'detail' => $simulacros->isEmpty() ? 'Sin simulacros (opcional).' : ($simulacrosSinPreguntas->isEmpty() ? $simulacros->count() . ' simulacro(s) con preguntas.' : $simulacrosSinPreguntas->count() . ' simulacro(s) sin preguntas.')],
        ];

        return [
            'ready' => collect($checks)->every(fn (array $check) => $check['ok']),
            'checks' => $checks,
        ];
    }

    /**
     * Examen final del curso, si ya existe.
     */
    public function finalExam(AcaCourse $curso): ?AcaExam
    {
        return AcaExam::where('course_id', $curso->id)
            ->whereNull('module_id')
            ->whereNull('content_id')
            ->where('is_mock', false)
            ->with('questions.answers')
            ->first();
    }

    /**
     * Preguntas que no cumplen las reglas del mantenedor: Alternativas con una
     * sola opción o sin una única correcta, y Varias respuestas sin correctas.
     */
    protected function invalidQuestions($preguntas)
    {
        return $preguntas->filter(function (AcaExamQuestion $pregunta) {
            if (in_array($pregunta->type_answers, ['Escribir', 'Subir Archivo'], true)) {
                return false;
            }

            $correctas = $pregunta->answers->where('correct', true)->count();

            return $pregunta->type_answers === 'Alternativas'
                ? $pregunta->answers->count() < 2 || $correctas !== 1
                : $correctas < 1;
        });
    }

    /**
     * Indica si un contenido tiene lo que la web necesita para mostrarlo.
     */
    protected function materialComplete(AcaContent $contenido): bool
    {
        $valor = trim((string) $contenido->content);

        if ($valor === '') {
            return false;
        }

        return match ((int) $contenido->is_file) {
            1, 3 => (bool) filter_var($valor, FILTER_VALIDATE_URL),
            5 => str_contains($valor, '<iframe') && preg_match('#https://#', $valor) === 1,
            2 => ! preg_match('#^https?://#i', $valor),
            default => true,
        };
    }

    /**
     * Devuelve los bytes y la extensión de un archivo enviado en base64 o por
     * URL https, validando extensión, tamaño y firma real del contenido.
     *
     * @return array{0: string, 1: string}
     */
    protected function readMaterial(array $archivo): array
    {
        $nombre = trim((string) ($archivo['filename'] ?? ''));
        $base64 = trim((string) ($archivo['file_base64'] ?? ''));
        $url = trim((string) ($archivo['file_url'] ?? ''));

        if ($base64 !== '') {
            if (preg_match('#^data:([a-z0-9.+/-]+);base64,#i', $base64, $coincidencia)) {
                $base64 = substr($base64, strlen($coincidencia[0]));

                if ($nombre === '') {
                    $nombre = 'archivo.' . $this->extensionForMime($coincidencia[1]);
                }
            }

            $bytes = base64_decode($base64, true);

            if ($bytes === false) {
                throw ValidationException::withMessages(['files' => 'El archivo en base64 no es válido.']);
            }
        } elseif ($url !== '') {
            $bytes = $this->readHttps(
                $url,
                'files',
                'El enlace del archivo debe ser una URL https pública.',
                'No se pudo descargar el archivo de esa URL.'
            );

            if ($nombre === '') {
                $nombre = basename((string) parse_url($url, PHP_URL_PATH));
            }
        } else {
            throw ValidationException::withMessages([
                'files' => 'Cada archivo necesita file_base64 o file_url.',
            ]);
        }

        $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

        if (! in_array($extension, self::FILE_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'files' => 'Solo se permiten archivos PDF, Excel (.xlsx, .xls) o Word (.doc, .docx).',
            ]);
        }

        if (strlen($bytes) > self::MAX_FILE_BYTES) {
            throw ValidationException::withMessages([
                'files' => 'El archivo ' . $nombre . ' supera los 10 MB.',
            ]);
        }

        if (! str_starts_with($bytes, self::FILE_SIGNATURES[$extension])) {
            throw ValidationException::withMessages([
                'files' => 'El contenido de ' . $nombre . ' no corresponde a un archivo .' . $extension . ' válido.',
            ]);
        }

        return [$bytes, $extension];
    }

    /**
     * Extensión que corresponde a un mime de un data URI.
     */
    protected function extensionForMime(string $mime): string
    {
        return match (strtolower($mime)) {
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel' => 'xls',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            default => '',
        };
    }

    /**
     * Valida que el enlace sea https y que no apunte a la red local.
     */
    protected function publicUrl(string $url): string
    {
        $url = trim($url);
        $partes = parse_url($url);

        if (($partes['scheme'] ?? '') !== 'https' || empty($partes['host'])) {
            throw ValidationException::withMessages([
                'links' => 'El enlace debe ser una URL https completa.',
            ]);
        }

        $ips = $this->publicIps((string) $partes['host']);

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw ValidationException::withMessages([
                    'links' => 'La URL apunta a una dirección no pública.',
                ]);
            }
        }

        return $url;
    }

    /**
     * Comprueba que el enlace pertenezca al dominio del tipo elegido: Zoom,
     * Meet, Teams, Vimeo o YouTube. «archivo» acepta cualquier URL pública.
     */
    protected function assertLinkDomain(string $tipo, string $url): void
    {
        $dominios = self::LINK_DOMAINS[$tipo] ?? null;

        if ($dominios === null) {
            throw ValidationException::withMessages([
                'links' => 'Tipo de enlace no válido: usa zoom, meet, vimeo, youtube o archivo.',
            ]);
        }

        if ($dominios === []) {
            return;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        foreach ($dominios as $dominio) {
            if ($host === $dominio || str_ends_with($host, '.' . $dominio)) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'links' => 'El enlace no pertenece al dominio esperado para el tipo ' . $tipo . '.',
        ]);
    }

    /**
     * Arma el iframe del reproductor a partir de una URL de Vimeo o YouTube.
     */
    public function videoEmbed(string $tipo, string $url): string
    {
        $id = null;

        if ($tipo === 'vimeo' && preg_match('#vimeo\.com/(?:[^/]+/)*(\d+)#i', $url, $coincidencia)) {
            $id = $coincidencia[1];
        }

        if ($tipo === 'youtube'
            && preg_match('#(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{6,})#i', $url, $coincidencia)) {
            $id = $coincidencia[1];
        }

        if ($id === null) {
            throw ValidationException::withMessages([
                'links' => 'No pude reconocer el id del video en esa URL de ' . $tipo . '.',
            ]);
        }

        $src = $tipo === 'vimeo'
            ? 'https://player.vimeo.com/video/' . $id
            : 'https://www.youtube.com/embed/' . $id;

        return '<iframe width="100%" height="400" src="' . e($src) . '" frameborder="0" '
            . 'allow="autoplay; fullscreen; picture-in-picture; encrypted-media" allowfullscreen></iframe>';
    }

    /**
     * Direcciones del host: IPv4 e IPv6. gethostbynamel solo devuelve A, así
     * que un dominio con únicamente AAAA quedaba sin comprobar.
     *
     * @return array<int, string>
     */
    protected function publicIps(string $host): array
    {
        $host = trim($host, '[]');
        $ips = [];

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            $ips = gethostbynamel($host) ?: [];

            foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $registro) {
                if (! empty($registro['ipv6'])) {
                    $ips[] = $registro['ipv6'];
                }
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * Exige una URL https que resuelva solo a direcciones públicas. Se aplica
     * a la URL inicial y a la final después de las redirecciones, para que una
     * página pública no pueda llevar la descarga a la red local.
     */
    protected function assertPublicTarget(string $url, string $campo, string $mensajeFormato): void
    {
        $partes = parse_url($url);
        $host = (string) ($partes['host'] ?? '');

        if (($partes['scheme'] ?? '') !== 'https' || $host === '') {
            throw ValidationException::withMessages([$campo => $mensajeFormato]);
        }

        $ips = $this->publicIps($host);

        if ($ips === []) {
            throw ValidationException::withMessages([$campo => 'No se pudo resolver el dominio de la URL.']);
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw ValidationException::withMessages([$campo => 'La URL apunta a una dirección no pública.']);
            }
        }
    }

    /**
     * Descarga una URL https pública con los mismos controles para imágenes y
     * archivos: sin http, sin red local y con el error en el campo que falló.
     * Las redirecciones se limitan y se valida también su destino final.
     */
    protected function readHttps(string $url, string $campo, string $mensajeFormato, string $mensajeDescarga): string
    {
        $this->assertPublicTarget($url, $campo, $mensajeFormato);

        $respuesta = Http::timeout(20)->withOptions([
            'allow_redirects' => [
                'max' => 3,
                'strict' => true,
                'referer' => false,
                'protocols' => ['https'],
            ],
        ])->get($url);

        if (! $respuesta->successful()) {
            throw ValidationException::withMessages([$campo => $mensajeDescarga]);
        }

        $efectiva = (string) ($respuesta->effectiveUri() ?? '');

        if ($efectiva !== '') {
            $this->assertPublicTarget($efectiva, $campo, 'La URL redirige a una dirección no permitida.');
        }

        return $respuesta->body();
    }

    /**
     * Sanea el HTML que redacta el agente: quita scripts, iframes y manejadores
     * de eventos, y deja solo etiquetas y atributos seguros. Así el contenido
     * se abre igual en el editor de la web y no inyecta nada en la landing.
     */
    public static function sanitizeHtml(string $html): string
    {
        // Contenido completo de etiquetas peligrosas (con su interior).
        $html = (string) preg_replace(
            '#<(script|style|iframe|object|embed|form|noscript|template)\b[^>]*>.*?</\1>#is',
            '',
            $html
        );

        // Etiquetas peligrosas sueltas o sin cierre.
        $html = (string) preg_replace(
            '#</?(script|style|iframe|object|embed|form|input|button|link|meta|base)\b[^>]*>#i',
            '',
            $html
        );

        $html = strip_tags($html, self::ALLOWED_TAGS);

        // Se descartan todos los atributos; solo sobreviven href de enlaces,
        // colspan/rowspan de tablas y el style del texto en <span>.
        $html = (string) preg_replace_callback('#<(/?)([a-z0-9]+)\b([^>]*)>#i', function (array $coincidencias) {
            $cierre = $coincidencias[1] === '/';
            $etiqueta = strtolower($coincidencias[2]);

            if ($cierre) {
                return '</' . $etiqueta . '>';
            }

            if ($etiqueta === 'br' || $etiqueta === 'hr') {
                return '<' . $etiqueta . '>';
            }

            $atributos = '';

            if ($etiqueta === 'a' && preg_match('/href\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $coincidencias[3], $href)) {
                $url = trim(($href[1] ?? '') !== '' ? $href[1] : ($href[2] ?? ''));

                if (preg_match('#^(https?://|mailto:|/)#i', $url)) {
                    $atributos = ' href="' . e($url) . '" rel="noopener noreferrer" target="_blank"';
                }
            }

            if ($etiqueta === 'td' || $etiqueta === 'th') {
                foreach (['colspan', 'rowspan'] as $union) {
                    if (preg_match('/' . $union . '\s*=\s*"(\d{1,2})"/i', $coincidencias[3], $valor)) {
                        $atributos .= ' ' . $union . '="' . (int) $valor[1] . '"';
                    }
                }
            }

            return '<' . $etiqueta . $atributos . '>';
        }, $html);

        return trim($html);
    }

    /**
     * Codifica el HTML igual que la web y comprueba que quepa en la columna
     * TEXT de aca_contents. Así un contenido demasiado grande se rechaza con
     * un mensaje claro en lugar de fallar al guardar.
     */
    protected function encodeHtml(string $html, string $campo = 'contents'): string
    {
        $codificado = htmlentities($html, ENT_QUOTES, 'UTF-8');

        if (strlen($codificado) > self::MAX_HTML_BYTES) {
            throw ValidationException::withMessages([
                $campo => 'El HTML ocupa más de ' . (int) (self::MAX_HTML_BYTES / 1024) . ' KB al guardarse; divídelo en varios contenidos.',
            ]);
        }

        return $codificado;
    }

    /**
     * Devuelve los bytes de la imagen: data URI en base64 o URL https pública.
     */
    protected function readImage(string $imagen): string
    {
        $imagen = trim($imagen);

        if (preg_match('#^data:image/[a-z+]+;base64,#i', $imagen)) {
            $bytes = base64_decode((string) preg_replace('#^data:image/[a-z+]+;base64,#i', '', $imagen), true);

            if ($bytes === false) {
                throw ValidationException::withMessages(['image_base64' => 'El base64 de la imagen no es válido.']);
            }

            return $bytes;
        }

        return $this->readHttps(
            $imagen,
            'image_base64',
            'Envía la imagen en base64 (data URI) o una URL https pública.',
            'No se pudo descargar la imagen de esa URL.'
        );
    }

    /**
     * Huella estable de la clave de idempotencia, acotada a 64 caracteres para
     * la columna y separada por usuario para que dos personas puedan reutilizar
     * la misma clave sin chocar.
     */
    protected function idempotencyFingerprint(?string $clave, int $userId): ?string
    {
        $clave = trim((string) $clave);

        if ($clave === '') {
            return null;
        }

        return hash('sha256', $userId . '|' . $clave);
    }
}
