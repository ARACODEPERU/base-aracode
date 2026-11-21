<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaExam;
use Modules\Academic\Entities\McpAuditLog;
use Modules\Academic\Services\ActiveCourseEditingService;
use Modules\Academic\Services\CourseAuthoringService;
use Modules\Academic\Services\StudentInsightsService;
use Throwable;

/**
 * Base de las herramientas MCP del Académico.
 *
 * Centraliza lo que no debe repetirse en cada herramienta: exigir usuario,
 * token y permiso real del ERP, convertir los errores en mensajes legibles
 * para el agente y dejar constancia en mcp_audit_logs.
 */
abstract class AcademicTool extends Tool
{
    /** Permiso Spatie que se exige (null solo si la herramienta no toca datos). */
    protected ?string $permission = 'aca_cursos_listado';

    /** Ability del token Sanctum que se exige. */
    protected string $ability = 'academic:read';

    final public function handle(Request $request): Response
    {
        $inicio = microtime(true);
        $usuario = $request->user();

        try {
            $this->autorizar($usuario);
            $respuesta = $this->execute($request);
            $this->registrarAuditoria($usuario, $request, 'ok', null, $inicio);

            return $respuesta;
        } catch (Throwable $excepcion) {
            $this->registrarAuditoria($usuario, $request, 'error', $excepcion->getMessage(), $inicio);

            throw $excepcion;
        }
    }

    /**
     * Cuerpo de la herramienta. Implementado por cada tool.
     */
    abstract protected function execute(Request $request): Response;

    /**
     * Contrato de entrada de cada herramienta.
     *
     * El agente arma sus llamadas con este esquema, así que vive junto a la
     * autorización y no dentro de cada clase: una sola tabla que se revisa de
     * una vez y que debe coincidir con las reglas de validación de cada tool.
     */
    public function schema(JsonSchema $schema): array
    {
        $texto = fn (string $descripcion, bool $requerido = false) => $schema->string()->description($descripcion)->required($requerido);
        $entero = fn (string $descripcion, bool $requerido = false) => $schema->integer()->description($descripcion)->required($requerido);
        $booleano = fn (string $descripcion, bool $requerido = false) => $schema->boolean()->description($descripcion)->required($requerido);

        $leerCurso = fn () => $entero('Id del curso. Se obtiene de academic_create_course_draft o academic_list_courses.', true);

        $preguntasExamen = fn () => $schema->array()
            ->description('Preguntas del examen (máximo 50). Opcional: también puedes usar academic_add_exam_questions después.')
            ->items($schema->object([
                'statement' => $texto('Enunciado de la pregunta.', true),
                'score' => $entero('Puntaje de la pregunta (1 a 100).', true),
                'type' => $schema->string()->description('Tipo de pregunta.')
                    ->enum(['Escribir', 'Alternativas', 'Varias respuestas', 'Subir Archivo'])->required(),
                'options' => $schema->array()
                    ->description('Opciones. Solo para Alternativas y Varias respuestas.')
                    ->items($schema->object([
                        'text' => $texto('Texto de la opción.', true),
                        'correct' => $booleano('Marca la opción correcta. Exactamente una en Alternativas.'),
                        'score' => $entero('Puntaje de la opción, si aplica.'),
                    ]))
                    ->max(10),
            ]))
            ->min(1)
            ->max(50);

        return match ($this->name()) {
            'academic_get_course_options',
            'academic_list_categories',
            'academic_list_modalities' => [],

            'academic_list_teachers' => [
                'search' => $texto('Filtro opcional por nombre o documento del docente.'),
            ],

            'academic_list_courses' => [
                'search' => $texto('Filtro opcional por nombre del curso.'),
                'status' => $schema->string()->description('Estado a listar: todos, borrador o activo. Por defecto todos.')
                    ->enum(['todos', 'borrador', 'activo']),
            ],

            'academic_list_students' => [
                'search' => $texto('Buscar por nombre, correo o documento.'),
                'course_id' => $entero('Filtrar estudiantes matriculados en este curso.'),
                'subscription_status' => $schema->string()
                    ->description('Filtrar por estado de suscripción.')
                    ->enum(['todas', 'activa', 'por_vencer', 'vencida', 'sin_suscripcion']),
                'expiring_days' => $entero('Ventana de días para por_vencer. Por defecto 15, máximo 365.'),
                'page' => $entero('Página del listado. Por defecto 1.'),
                'per_page' => $entero('Filas por página. Por defecto 25, máximo 100.'),
            ],

            'academic_get_student' => [
                'student_id' => $entero('Id del estudiante. Usa este campo o document/email.'),
                'document' => $texto('Documento de identidad del estudiante.'),
                'email' => $texto('Correo del estudiante. Debe ser exacto.'),
            ],

            'academic_check_student_access' => [
                'student_id' => $entero('Id del estudiante.', true),
                'course_id' => $entero('Id del curso.', true),
            ],

            'academic_get_academic_overview' => [
                'days' => $entero('Días de actividad para las métricas. Por defecto 30, máximo 365.'),
                'top' => $entero('Cantidad de cursos en los rankings. Por defecto 10, máximo 50.'),
            ],

            'academic_list_expiring_subscriptions' => [
                'days' => $entero('Días hacia adelante para buscar vencimientos. Por defecto 15, máximo 365.'),
                'include_expired' => $booleano('Incluir también las suscripciones ya vencidas.'),
                'page' => $entero('Página del listado. Por defecto 1.'),
                'per_page' => $entero('Filas por página. Por defecto 25, máximo 100.'),
            ],

            'academic_get_teacher' => [
                'teacher_id' => $entero('Id del docente.', true),
            ],

            'academic_get_course' => [
                'course_id' => $leerCurso(),
                'include_html' => $booleano('Incluir el HTML completo de los contenidos. Por defecto false: se devuelve un recorte para no llenar el contexto.'),
            ],

            'academic_get_course_landing',
            'academic_validate_course' => [
                'course_id' => $leerCurso(),
            ],

            'academic_create_course_draft' => [
                'title' => $texto('Nombre del curso (máximo 255 caracteres).', true),
                'category_id' => $entero('Id real de la categoría. Se obtiene de academic_list_categories.', true),
                'modality_id' => $entero('Id real de la modalidad. Se obtiene de academic_list_modalities.', true),
                'start_date' => $texto('Fecha de inicio en formato YYYY-MM-DD.', true),
                'type_description' => $schema->string()->description('Tipo de curso. Usa uno de academic_get_course_options.')
                    ->enum(getEnumValues('aca_courses', 'type_description') ?: ['Curso'])->required(),
                'sector_description' => $schema->string()->description('Sector del curso. Usa uno de academic_get_course_options.')
                    ->enum(getEnumValues('aca_courses', 'sector_description') ?: ['Empresas']),
                'price' => $schema->number()->description('Precio en soles. 0 o vacío si es gratuito.')->min(0),
                'teacher_id' => $entero('Id real del docente. Se obtiene de academic_list_teachers.'),
                'certificate_title' => $texto('Texto que saldrá en el certificado. Si se omite, se usa el nombre del curso.'),
                'certificate_description' => $texto('Descripción que acompaña al certificado (máximo 65000 caracteres).'),
                'usine' => $texto('Código USINE, si la institución lo usa.'),
                'discount' => $schema->number()->description('Descuento en soles.')->min(0),
                'discount_applies' => $schema->string()->description('A quién aplica el descuento: 01 = todos, 02 = suscripción.')
                    ->enum(['01', '02']),
                'auto_certificate' => $booleano('Genera el certificado automáticamente al aprobar el curso.'),
                'round_grades' => $booleano('Redondea las notas del curso.'),
                'idempotency_key' => $texto('Clave única para reintentos seguros (máximo 100 caracteres). Reutilízala en los reintentos.'),
            ],

            'academic_update_course' => [
                'course_id' => $leerCurso(),
                'title' => $texto('Nuevo nombre del curso.'),
                'category_id' => $entero('Nuevo id de categoría.'),
                'modality_id' => $entero('Nuevo id de modalidad.'),
                'type_description' => $schema->string()->description('Nuevo tipo de curso.')
                    ->enum(getEnumValues('aca_courses', 'type_description') ?: ['Curso']),
                'sector_description' => $schema->string()->description('Nuevo sector.')
                    ->enum(getEnumValues('aca_courses', 'sector_description') ?: ['Empresas']),
                'start_date' => $texto('Nueva fecha de inicio en formato YYYY-MM-DD.'),
                'price' => $schema->number()->description('Nuevo precio en soles.'),
                'teacher_id' => $entero('Nuevo docente principal.'),
                'certificate_title' => $texto('Nuevo título del certificado.'),
                'certificate_description' => $texto('Nueva descripción del certificado.'),
                'usine' => $texto('Nuevo código USINE.'),
                'discount' => $schema->number()->description('Nuevo descuento en soles.')->min(0),
                'discount_applies' => $schema->string()->description('A quién aplica el descuento: 01 = todos, 02 = suscripción.')
                    ->enum(['01', '02']),
                'auto_certificate' => $booleano('Genera el certificado automáticamente al aprobar.'),
                'round_grades' => $booleano('Redondea las notas del curso.'),
            ],

            'academic_set_course_image' => [
                'course_id' => $leerCurso(),
                'image_url' => $texto('URL https pública de la portada (JPG, PNG o WebP).'),
                'image_base64' => $texto('Imagen en base64, con o sin prefijo data:image/...;base64.'),
            ],

            'academic_add_modules' => [
                'course_id' => $leerCurso(),
                'modules' => $schema->array()
                    ->description('Módulos en el orden en que se dictan (máximo 20).')
                    ->items($schema->object([
                        'title' => $texto('Nombre del módulo.', true),
                        'position' => $entero('Posición. Si se omite, se agrega al final.'),
                    ]))
                    ->min(1)
                    ->max(CourseAuthoringService::MAX_MODULES)
                    ->required(),
            ],

            'academic_update_module' => [
                'module_id' => $entero('Id del módulo que devolvió academic_add_modules.', true),
                'title' => $texto('Nuevo nombre del módulo.'),
                'position' => $entero('Nueva posición del módulo.'),
            ],

            'academic_add_lessons' => [
                'module_id' => $entero('Id del módulo que devolvió academic_add_modules.', true),
                'lessons' => $schema->array()
                    ->description('Clases del módulo en orden (máximo ' . CourseAuthoringService::MAX_LESSONS_PER_MODULE . ').')
                    ->items($schema->object([
                        'title' => $texto('Nombre de la clase.', true),
                        'position' => $entero('Posición. Si se omite, se agrega al final.'),
                    ]))
                    ->min(1)
                    ->max(CourseAuthoringService::MAX_LESSONS_PER_MODULE)
                    ->required(),
            ],

            'academic_update_lesson' => [
                'lesson_id' => $entero('Id de la clase que devolvió academic_add_lessons.', true),
                'title' => $texto('Nuevo nombre de la clase.'),
                'position' => $entero('Nueva posición de la clase.'),
            ],

            'academic_set_contents' => [
                'theme_id' => $entero('Id de la clase que devolvió academic_add_lessons.', true),
                'contents' => $schema->array()
                    ->description('Contenidos de texto de la clase (máximo ' . CourseAuthoringService::MAX_CONTENTS_PER_LESSON . ').')
                    ->items($schema->object([
                        'title' => $texto('Título visible del contenido.', true),
                        'html' => $texto('HTML de un contenido de TEXTO (no video). Etiquetas permitidas: p, h2, h3, h4, ul, ol, li, strong, em, blockquote, table, a y br. Para videos usa academic_add_lesson_links.', true),
                        'position' => $entero('Posición dentro de la clase.'),
                    ]))
                    ->min(1)
                    ->max(CourseAuthoringService::MAX_CONTENTS_PER_LESSON)
                    ->required(),
            ],

            'academic_update_content' => [
                'content_id' => $entero('Id del contenido.', true),
                'title' => $texto('Nuevo título del contenido.'),
                'position' => $entero('Nueva posición del contenido.'),
                'html' => $texto('Nuevo HTML. Se sanea antes de guardarse.'),
            ],

            'academic_add_lesson_files' => [
                'theme_id' => $entero('Id de la clase que devolvió academic_add_lessons.', true),
                'files' => $schema->array()
                    ->description('Archivos PDF, Excel o Word (máximo ' . CourseAuthoringService::MAX_FILES_PER_CALL . ', 10 MB cada uno).')
                    ->items($schema->object([
                        'title' => $texto('Título visible del archivo.', true),
                        'filename' => $texto('Nombre con extensión: guia.pdf, planilla.xlsx, clase.docx.'),
                        'file_base64' => $texto('Contenido en base64, con o sin prefijo data:application/pdf;base64.'),
                        'file_url' => $texto('URL https pública del archivo, como alternativa a file_base64.'),
                        'position' => $entero('Posición dentro de la clase.'),
                    ]))
                    ->min(1)
                    ->max(CourseAuthoringService::MAX_FILES_PER_CALL)
                    ->required(),
            ],

            'academic_add_lesson_links' => [
                'theme_id' => $entero('Id de la clase que devolvió academic_add_lessons.', true),
                'links' => $schema->array()
                    ->description('Enlaces de la clase (máximo ' . CourseAuthoringService::MAX_CONTENTS_PER_LESSON . ').')
                    ->items($schema->object([
                        'title' => $texto('Título visible del enlace.', true),
                        'type' => $schema->string()
                            ->description('zoom/meet/teams = clase en vivo; vimeo/youtube = video embebido; archivo = recurso externo (Drive, OneDrive, etc.).')
                            ->enum(['zoom', 'meet', 'teams', 'vimeo', 'youtube', 'archivo'])->required(),
                        'url' => $texto('URL https del enlace.', true),
                        'position' => $entero('Posición dentro de la clase.'),
                    ]))
                    ->min(1)
                    ->max(CourseAuthoringService::MAX_CONTENTS_PER_LESSON)
                    ->required(),
            ],

            'academic_update_exam' => [
                'exam_id' => $entero('Id del examen (de contenido, de módulo o final).', true),
                'title' => $texto('Nuevo nombre del examen.'),
                'date_start' => $texto('Nueva fecha de inicio (YYYY-MM-DD o YYYY-MM-DD HH:MM).'),
                'date_end' => $texto('Nueva fecha de fin. Debe ser igual o posterior a la de inicio.'),
                'duration_minutes' => $entero('Nueva duración en minutos (5 a 600).'),
                'attempts' => $entero('Nuevos intentos permitidos (1 a 10).'),
            ],

            'academic_delete_exam' => [
                'exam_id' => $entero('Id del examen que quieres borrar.', true),
                'confirm' => $booleano('Debe ser true. Confirma el borrado.', true),
            ],

            'academic_delete_module' => [
                'module_id' => $entero('Id del módulo que quieres borrar con sus clases y contenidos.', true),
                'confirm' => $booleano('Debe ser true. Confirma el borrado.', true),
            ],

            'academic_delete_lesson' => [
                'lesson_id' => $entero('Id de la clase que quieres borrar con sus contenidos.', true),
                'confirm' => $booleano('Debe ser true. Confirma el borrado.', true),
            ],

            'academic_delete_content' => [
                'content_id' => $entero('Id del contenido que quieres borrar.', true),
                'confirm' => $booleano('Debe ser true. Confirma el borrado.', true),
            ],

            'academic_create_module_exam',
            'academic_create_mock_exam' => [
                'module_id' => $entero('Id del módulo que devolvió academic_add_modules.', true),
                'title' => $texto('Nombre del examen o simulacro.', true),
                'date_start' => $texto('Inicio de disponibilidad (YYYY-MM-DD o YYYY-MM-DD HH:MM).', true),
                'date_end' => $texto('Fin de disponibilidad. Debe ser igual o posterior a date_start.', true),
                'duration_minutes' => $entero('Duración en minutos (5 a 600).', true),
                'attempts' => $entero('Intentos permitidos (1 a 10). Por defecto 1.'),
                'questions' => $preguntasExamen(),
            ],

            'academic_create_final_exam' => [
                'course_id' => $leerCurso(),
                'title' => $texto('Nombre del examen final.', true),
                'date_start' => $texto('Inicio de disponibilidad (YYYY-MM-DD o YYYY-MM-DD HH:MM).', true),
                'date_end' => $texto('Fin de disponibilidad. Debe ser igual o posterior a date_start.', true),
                'duration_minutes' => $entero('Duración en minutos (5 a 600).', true),
                'attempts' => $entero('Intentos permitidos (1 a 10). Por defecto 1.'),
                'questions' => $preguntasExamen(),
            ],

            'academic_create_exam' => [
                'content_id' => $entero('Id del contenido al que se cuelga el examen (el examen vive en el contenido de una clase).', true),
                'title' => $texto('Nombre del examen.', true),
                'date_start' => $texto('Inicio de disponibilidad (YYYY-MM-DD o YYYY-MM-DD HH:MM).'),
                'date_end' => $texto('Fin de disponibilidad. Debe ser igual o posterior a date_start.'),
                'attempts' => $entero('Intentos permitidos (1 a 10). Por defecto 1.'),
                'duration_minutes' => $entero('Duración en minutos (5 a 600).'),
            ],

            'academic_add_exam_questions' => [
                'exam_id' => $entero('Id del examen que devolvió academic_create_exam.', true),
                'questions' => $schema->array()
                    ->description('Preguntas del examen (máximo 50).')
                    ->items($schema->object([
                        'statement' => $texto('Enunciado de la pregunta.', true),
                        'score' => $entero('Puntaje de la pregunta (1 a 100).', true),
                        'type' => $schema->string()->description('Tipo de pregunta.')
                            ->enum(['Escribir', 'Alternativas', 'Varias respuestas', 'Subir Archivo'])->required(),
                        'options' => $schema->array()
                            ->description('Opciones. Solo para Alternativas y Varias respuestas.')
                            ->items($schema->object([
                                'text' => $texto('Texto de la opción.', true),
                                'correct' => $booleano('Marca la opción correcta. Exactamente una en Alternativas.'),
                                'score' => $entero('Puntaje de la opción, si aplica.'),
                            ]))
                            ->max(10),
                    ]))
                    ->min(1)
                    ->max(50)
                    ->required(),
            ],

            'academic_set_brochure' => [
                'course_id' => $leerCurso(),
                'resolution' => $texto('Resolución o aval institucional (HTML).'),
                'presentation' => $texto('Presentación del curso (HTML).'),
                'benefits' => $texto('Beneficios (HTML, normalmente una lista).'),
                'frequent_questions' => $texto('Preguntas frecuentes (HTML).'),
                'curriculum_plan' => $texto('Plan de estudios (HTML).'),
            ],

            'academic_update_landing_settings' => [
                'course_id' => $leerCurso(),
                'url_slug' => $texto('Slug de la landing: minúsculas, números y guiones (ejemplo: excel-financiero-pymes).'),
                'whatsapp_link' => $texto('Enlace de WhatsApp (https).'),
                'flow_id' => $texto('ID del flujo o plantilla de WhatsApp.'),
                'payment_facilities_link' => $texto('Enlace de facilidades de pago (https).'),
                'corporate_contact_link' => $texto('Enlace de contacto corporativo (https).'),
                'banner_video_link' => $texto('Enlace de YouTube/Vimeo o iframe del video del banner.'),
                'is_published' => $booleano('Publicar la landing. Úsalo solo si la persona lo pidió.'),
            ],

            'academic_set_course_landing' => [
                'course_id' => $leerCurso(),
                'url_slug' => $texto('Slug de la landing; se normaliza a minúsculas y guiones.'),
                'whatsapp_link' => $texto('Enlace de WhatsApp (https).'),
                'flow_id' => $texto('ID del flujo o plantilla de WhatsApp.'),
                'payment_facilities_link' => $texto('Enlace de facilidades de pago (https).'),
                'corporate_contact_link' => $texto('Enlace de contacto corporativo (https).'),
                'banner_video_link' => $texto('Enlace de YouTube/Vimeo o iframe del video del banner.'),
                'banner_start_date' => $texto('Fecha de inicio del banner (YYYY-MM-DD).'),
                'banner_end_date' => $texto('Fecha de fin del banner (YYYY-MM-DD).'),
                'banner_language' => $schema->string()->description('Idioma de la landing.')
                    ->enum(['es', 'en', 'zh']),
                'is_published' => $booleano('Publicar la landing. Úsalo solo si la persona lo pidió.'),
                'professional' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'items' => $schema->array()->items($schema->object([
                        'icon' => $texto('Nombre de icono FontAwesome, por ejemplo fa-briefcase.'),
                        'title' => $texto('Título de la tarjeta.'),
                        'description' => $texto('Texto de la tarjeta.'),
                    ]))->max(20),
                ]),
                'staff' => $schema->object([
                    'name' => $texto('Nombre de la sección, por ejemplo Nuestro Staff.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'teacher_ids' => $schema->array()->description('Ids reales de academic_list_teachers que se mostrarán en la landing.')
                        ->items($entero('Id del docente.'))->max(20),
                    'teachers' => $schema->array()->items($schema->object([
                        'teacher_id' => $entero('Id real del docente.', true),
                        'teacher_names' => $texto('Nombre visible, si se quiere sobrescribir el de la BD.'),
                        'teacher_ocupation' => $texto('Cargo visible, si se quiere sobrescribir el de la BD.'),
                    ]))->max(20),
                ]),
                'results' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'items' => $schema->array()->items($schema->object([
                        'icon' => $texto('Nombre de icono FontAwesome.'),
                        'title' => $texto('Título de la tarjeta.'),
                        'description' => $texto('Texto de la tarjeta.'),
                    ]))->max(20),
                ]),
                'testimonials' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'items' => $schema->array()->items($schema->object([
                        'name' => $texto('Nombre del alumno.', true),
                        'presentation' => $texto('Cargo o presentación del alumno.'),
                        'description' => $texto('Testimonio.'),
                        'image' => $texto('Ruta existente en uploads/; normalmente se omite.'),
                        'image_url' => $texto('URL https pública de la foto.'),
                        'image_base64' => $texto('Foto en base64, opcional.'),
                    ]))->max(20),
                ]),
                'study_plan' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'image' => $texto('Ruta existente en uploads/; normalmente se omite.'),
                    'image_url' => $texto('URL https pública de la imagen del plan.'),
                    'image_base64' => $texto('Imagen en base64, opcional.'),
                    'items' => $schema->array()->items($schema->object([
                        'number' => $entero('Número del tema.'),
                        'title' => $texto('Título del tema.', true),
                        'description' => $texto('Descripción del tema.'),
                    ]))->max(40),
                ]),
                'problem' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'items' => $schema->array()->items($schema->object([
                        'icon' => $texto('Nombre de icono FontAwesome.'),
                        'title' => $texto('Título del problema.'),
                        'description' => $texto('Texto del problema.'),
                    ]))->max(20),
                ]),
                'investment' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'items' => $schema->array()->items($schema->object([
                        'tag' => $texto('Etiqueta del plan, por ejemplo Más popular.'),
                        'title' => $texto('Nombre del plan.'),
                        'description' => $texto('Descripción del plan.'),
                        'price_before' => $texto('Precio anterior.'),
                        'price_before_text' => $texto('Texto del precio anterior.'),
                        'price_before_visible' => $booleano('Mostrar el precio anterior.'),
                        'price_now' => $texto('Precio actual.'),
                        'price_now_text' => $texto('Texto del precio actual.'),
                        'features' => $schema->array()->items($texto('Característica incluida.'))->max(20),
                    ]))->max(4),
                ]),
                'faq' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'items' => $schema->array()->items($schema->object([
                        'question' => $texto('Pregunta.', true),
                        'answer' => $texto('Respuesta.'),
                        'sort' => $entero('Orden de la pregunta.'),
                        'visible' => $booleano('Mostrar la pregunta.'),
                    ]))->max(30),
                ]),
                'utm_config' => $schema->object([
                    'campaign' => $texto('Nombre de campaña UTM.'),
                    'channels' => $schema->array()->items($schema->object([
                        'id' => $texto('Identificador del canal, por ejemplo facebook.', true),
                        'term' => $texto('utm_term del canal.'),
                        'content' => $texto('utm_content del canal.'),
                        'active' => $booleano('Canal activo.'),
                    ]))->max(30),
                ]),
            ],

            'academic_set_course_teachers' => [
                'course_id' => $leerCurso(),
                'teacher_ids' => $schema->array()->description('Ids reales de docentes; reemplaza el equipo actual.')
                    ->items($entero('Id del docente.'))->max(20)->required(),
                'main_teacher_id' => $entero('Id del docente encargado; debe estar en teacher_ids.'),
            ],

            'academic_set_brochure_file' => [
                'course_id' => $leerCurso(),
                'filename' => $texto('Nombre del PDF, por ejemplo brochure-curso.pdf.'),
                'file_base64' => $texto('PDF en base64, con o sin prefijo data:application/pdf;base64.'),
                'file_url' => $texto('URL https pública del PDF.'),
            ],

            'academic_update_active_course' => [
                'course_id' => $entero('Id del curso activo.', true),
                'title' => $texto('Nuevo nombre. El slug público se conserva.'),
                'category_id' => $entero('Nueva categoría real.'),
                'modality_id' => $entero('Nueva modalidad real.'),
                'sector_description' => $texto('Nuevo sector.'),
                'start_date' => $texto('Nueva fecha de inicio (YYYY-MM-DD).'),
                'teacher_id' => $entero('Nuevo docente principal.'),
                'certificate_title' => $texto('Nuevo título del certificado.'),
                'certificate_description' => $texto('Nueva descripción del certificado.'),
                'confirm' => $booleano('Debe ser true para aplicar. Si se omite, solo devuelve el antes/después.'),
                'reason' => $texto('Motivo y confirmación de la persona. Obligatorio cuando confirm=true.'),
            ],

            'academic_edit_active_content' => [
                'content_id' => $entero('Id del contenido existente.', true),
                'operation' => $schema->string()
                    ->description('update_text = reemplazar texto; replace_file = reemplazar un PDF/Excel/Word; replace_link = reemplazar enlace o convertir videoconferencia en video.')
                    ->enum(['update_text', 'replace_file', 'replace_link'])->required(),
                'title' => $texto('Nuevo título del contenido (opcional).'),
                'html' => $texto('HTML para update_text. Se sanea y no puede superar 120000 caracteres.'),
                'filename' => $texto('Nombre con extensión para replace_file.'),
                'file_base64' => $texto('Archivo en base64 para replace_file.'),
                'file_url' => $texto('URL https del archivo para replace_file.'),
                'link_type' => $schema->string()
                    ->description('Tipo destino para replace_link: zoom/meet/teams para videoconferencia; vimeo/youtube para video; archivo para enlace externo.')
                    ->enum(['zoom', 'meet', 'teams', 'vimeo', 'youtube', 'archivo']),
                'url' => $texto('URL https del enlace o video para replace_link.'),
                'confirm' => $booleano('Debe ser true para aplicar. Si se omite, solo devuelve el antes/después.'),
                'reason' => $texto('Motivo y confirmación de la persona. Obligatorio cuando confirm=true.'),
            ],

            'academic_update_active_landing' => [
                'course_id' => $entero('Id del curso activo.', true),
                'whatsapp_link' => $texto('Enlace de WhatsApp (https).'),
                'flow_id' => $texto('ID del flujo o plantilla de WhatsApp.'),
                'payment_facilities_link' => $texto('Enlace de facilidades de pago (https).'),
                'corporate_contact_link' => $texto('Enlace de contacto corporativo (https).'),
                'banner_video_link' => $texto('Enlace de YouTube/Vimeo o iframe del video del banner.'),
                'banner_start_date' => $texto('Fecha de inicio del banner (YYYY-MM-DD).'),
                'banner_end_date' => $texto('Fecha de fin del banner (YYYY-MM-DD).'),
                'banner_language' => $schema->string()->description('Idioma de la landing.')->enum(['es', 'en', 'zh']),
                'professional' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'items' => $schema->array()->items($schema->object([
                        'icon' => $texto('Icono FontAwesome.'),
                        'title' => $texto('Título de la tarjeta.'),
                        'description' => $texto('Texto de la tarjeta.'),
                    ]))->max(20),
                ]),
                'staff' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'teacher_ids' => $schema->array()->items($entero('Id real del docente.'))->max(20),
                ]),
                'results' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'items' => $schema->array()->items($schema->object([
                        'icon' => $texto('Icono FontAwesome.'),
                        'title' => $texto('Título de la tarjeta.'),
                        'description' => $texto('Texto de la tarjeta.'),
                    ]))->max(20),
                ]),
                'testimonials' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'items' => $schema->array()->items($schema->object([
                        'name' => $texto('Nombre del alumno.', true),
                        'presentation' => $texto('Cargo o presentación.'),
                        'description' => $texto('Testimonio.'),
                        'image' => $texto('Ruta existente en uploads/; omite o deja vacío para quitar la imagen.'),
                        'image_url' => $texto('URL https de la foto.'),
                        'image_base64' => $texto('Foto en base64, opcional.'),
                    ]))->max(20),
                ]),
                'study_plan' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'image' => $texto('Ruta existente en uploads/; omite o deja vacío para quitar la imagen.'),
                    'image_url' => $texto('URL https de la imagen.'),
                    'image_base64' => $texto('Imagen en base64, opcional.'),
                    'items' => $schema->array()->items($schema->object([
                        'number' => $entero('Número del tema.'),
                        'title' => $texto('Título del tema.', true),
                        'description' => $texto('Descripción del tema.'),
                    ]))->max(40),
                ]),
                'problem' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'items' => $schema->array()->items($schema->object([
                        'icon' => $texto('Icono FontAwesome.'),
                        'title' => $texto('Título del problema.'),
                        'description' => $texto('Texto del problema.'),
                    ]))->max(20),
                ]),
                'faq' => $schema->object([
                    'name' => $texto('Nombre de la sección.'),
                    'title' => $texto('Título de la sección.'),
                    'description' => $texto('Introducción de la sección.'),
                    'items' => $schema->array()->items($schema->object([
                        'question' => $texto('Pregunta.', true),
                        'answer' => $texto('Respuesta.'),
                        'sort' => $entero('Orden de la pregunta.'),
                        'visible' => $booleano('Mostrar la pregunta.'),
                    ]))->max(30),
                ]),
                'utm_config' => $schema->object([
                    'campaign' => $texto('Nombre de campaña UTM.'),
                    'channels' => $schema->array()->items($schema->object([
                        'id' => $texto('Identificador del canal.', true),
                        'term' => $texto('utm_term del canal.'),
                        'content' => $texto('utm_content del canal.'),
                        'active' => $booleano('Canal activo.'),
                    ]))->max(30),
                ]),
                'confirm' => $booleano('Debe ser true para aplicar. Si se omite, solo devuelve la vista previa.'),
                'reason' => $texto('Motivo y confirmación de la persona. Obligatorio cuando confirm=true.'),
            ],

            'academic_manage_active_course_image' => [
                'course_id' => $entero('Id del curso activo.', true),
                'action' => $schema->string()->description('replace = reemplazar la portada; remove = eliminar solo la portada.')
                    ->enum(['replace', 'remove'])->required(),
                'image_base64' => $texto('Imagen en base64 para replace.'),
                'image_url' => $texto('URL https de la imagen para replace.'),
                'confirm' => $booleano('Debe ser true para aplicar. Si se omite, solo devuelve la vista previa.'),
                'reason' => $texto('Motivo y confirmación de la persona. Obligatorio cuando confirm=true.'),
            ],

            'academic_set_active_brochure_file' => [
                'course_id' => $entero('Id del curso activo.', true),
                'filename' => $texto('Nombre del PDF, por ejemplo brochure-curso.pdf.'),
                'file_base64' => $texto('PDF en base64.'),
                'file_url' => $texto('URL https del PDF.'),
                'confirm' => $booleano('Debe ser true para aplicar. Si se omite, solo devuelve la vista previa.'),
                'reason' => $texto('Motivo y confirmación de la persona. Obligatorio cuando confirm=true.'),
            ],

            'academic_delete_draft' => [
                'course_id' => $entero('Id del curso en borrador creado por un agente.', true),
                'confirm' => $booleano('Debe ser true. Confirma que quieres borrar el borrador.', true),
            ],

            default => [],
        };
    }

    /**
     * Comprueba el token (ability) y el permiso Spatie del usuario.
     */
    protected function autorizar(?Authenticatable $usuario): void
    {
        if (! $usuario) {
            throw new AuthorizationException('Se requiere un token de API válido para usar el servidor MCP del Académico.');
        }

        // Solo se revisa la ability cuando la autenticación vino de un token:
        // una sesión de navegador no tiene abilities y no debe entrar por aquí.
        if ($this->ability !== ''
            && method_exists($usuario, 'currentAccessToken')
            && $usuario->currentAccessToken() !== null
            && method_exists($usuario, 'tokenCan')
            && ! $usuario->tokenCan($this->ability)) {
            // El comando mcp:token recibe el correo, no el id del usuario.
            $correo = method_exists($usuario, 'getEmailForPasswordReset')
                ? $usuario->getEmailForPasswordReset()
                : (string) $usuario->getAuthIdentifier();

            throw new AuthorizationException(
                "El token no tiene la ability [{$this->ability}]. Genera uno con: php artisan mcp:token {$correo} --abilities=academic:read,academic:write (o academic:write_active para cursos activos)."
            );
        }

        // La edición de activos necesita leer el estado actual para mostrar el
        // antes/después. Se exige también academic:read, aunque el token se haya
        // creado por fuera de los comandos y controladores habituales.
        if ($this->ability === 'academic:write_active'
            && method_exists($usuario, 'currentAccessToken')
            && $usuario->currentAccessToken() !== null
            && method_exists($usuario, 'tokenCan')
            && ! $usuario->tokenCan('academic:read')) {
            throw new AuthorizationException(
                'La edición de cursos activos también requiere la ability [academic:read] para revisar el estado actual.'
            );
        }

        if ($this->permission !== null && method_exists($usuario, 'can') && ! $usuario->can($this->permission)) {
            throw new AuthorizationException(
                "El usuario no tiene el permiso [{$this->permission}] del Académico."
            );
        }
    }

    /**
     * Devuelve datos al agente como texto JSON legible.
     */
    protected function json(array $datos): Response
    {
        return Response::text(json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Curso que se puede modificar desde MCP: debe existir y seguir en borrador.
     * Un curso activo ya tiene alumnos y se edita solo desde el ERP.
     */
    protected function cursoBorrador(int $courseId): AcaCourse
    {
        $curso = AcaCourse::find($courseId);

        if (! $curso) {
            throw ValidationException::withMessages([
                'course_id' => 'No existe un curso con ese id.',
            ]);
        }

        if ($curso->status) {
            throw ValidationException::withMessages([
                'course_id' => 'El curso ya está activo: desde MCP solo se modifican borradores. Desactívalo en el ERP si necesitas editarlo.',
            ]);
        }

        return $curso;
    }

    /**
     * Servicio de dominio compartido con la web.
     */
    protected function authoring(): CourseAuthoringService
    {
        return app(CourseAuthoringService::class);
    }

    /**
     * Cambios seguros sobre cursos activos, con allowlist y confirmación.
     */
    protected function activeEditing(): ActiveCourseEditingService
    {
        return app(ActiveCourseEditingService::class);
    }

    /**
     * Consultas de solo lectura para estudiantes, docentes y métricas.
     */
    protected function insights(): StudentInsightsService
    {
        return app(StudentInsightsService::class);
    }

    /**
     * Curso al que pertenece un examen: el propio o, si cuelga de un
     * contenido, el curso de su clase y módulo.
     */
    protected function cursoDelExamen(AcaExam $examen): ?int
    {
        if (filled($examen->course_id)) {
            return (int) $examen->course_id;
        }

        return AcaContent::with('theme.module')
            ->find($examen->content_id)?->theme?->module?->course_id;
    }

    /**
     * Ruta en el disco público de un contenido descargable, si la tiene.
     */
    protected function archivoDeContenido(AcaContent $contenido): ?string
    {
        $valor = trim((string) $contenido->content);

        if ((int) $contenido->is_file !== 2 || $valor === '' || preg_match('#^https?://#i', $valor)) {
            return null;
        }

        return $valor;
    }

    /**
     * Borra un examen con sus preguntas y respuestas. No se depende de las
     * claves foráneas: aca_exams.course_id y module_id no las tienen, y en
     * algunos motores (sqlite en pruebas) las cascadas no están activas.
     */
    protected function borrarExamen(AcaExam $examen): void
    {
        foreach ($examen->questions()->with('answers')->get() as $pregunta) {
            $pregunta->answers()->delete();
            $pregunta->delete();
        }

        $examen->delete();
    }

    /**
     * Borra un contenido y el examen que cuelga de él.
     */
    protected function borrarContenido(AcaContent $contenido): void
    {
        foreach (AcaExam::where('content_id', $contenido->id)->get() as $examen) {
            $this->borrarExamen($examen);
        }

        $contenido->delete();
    }

    /**
     * Borra del disco público los archivos recogidos antes de eliminar filas.
     *
     * @param array<int, string> $rutas
     */
    protected function borrarArchivos(array $rutas): void
    {
        $rutas = array_values(array_unique(array_filter($rutas)));

        if ($rutas !== []) {
            Storage::disk('public')->delete($rutas);
        }
    }

    /**
     * Guarda la llamada en mcp_audit_logs. La auditoría nunca debe tumbar la
     * herramienta: si falla el registro, la respuesta sigue su curso.
     */
    protected function registrarAuditoria(
        ?Authenticatable $usuario,
        Request $request,
        string $estado,
        ?string $error,
        float $inicio
    ): void {
        try {
            McpAuditLog::create([
                'user_id' => $usuario?->getAuthIdentifier(),
                'tool' => $this->name(),
                'status' => $estado,
                'arguments' => $this->resumirArgumentos($request->all()),
                'error' => $error !== null ? Str::limit($error, 500) : null,
                'duration_ms' => (int) round((microtime(true) - $inicio) * 1000),
                'ip' => request()->ip(),
            ]);
        } catch (Throwable) {
            // Sin auditoría no se pierde la operación; el error queda en el log de la app.
        }
    }

    /**
     * Copia corta de los argumentos: recorta los textos largos para que el
     * registro no crezca con el HTML de un curso completo.
     */
    protected function resumirArgumentos(array $argumentos): array
    {
        $resumen = [];

        foreach ($argumentos as $clave => $valor) {
            if (is_string($valor) && mb_strlen($valor) > 300) {
                $resumen[$clave] = mb_substr($valor, 0, 300) . '…';
            } elseif (is_array($valor)) {
                $resumen[$clave] = $this->resumirArgumentos($valor);
            } else {
                $resumen[$clave] = $valor;
            }
        }

        return $resumen;
    }
}
