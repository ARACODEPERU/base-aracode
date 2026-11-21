<?php

namespace Modules\Academic\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Modules\Academic\Mcp\Prompts\CreateCourseFromBriefPrompt;
use Modules\Academic\Mcp\Resources\ContentStyleGuideResource;
use Modules\Academic\Mcp\Resources\LandingGuideResource;
use Modules\Academic\Mcp\Tools\AcademicAddExamQuestionsTool;
use Modules\Academic\Mcp\Tools\AcademicAddLessonFilesTool;
use Modules\Academic\Mcp\Tools\AcademicAddLessonLinksTool;
use Modules\Academic\Mcp\Tools\AcademicAddLessonsTool;
use Modules\Academic\Mcp\Tools\AcademicAddModulesTool;
use Modules\Academic\Mcp\Tools\AcademicCheckStudentAccessTool;
use Modules\Academic\Mcp\Tools\AcademicCourseOptionsTool;
use Modules\Academic\Mcp\Tools\AcademicCreateCourseDraftTool;
use Modules\Academic\Mcp\Tools\AcademicCreateExamTool;
use Modules\Academic\Mcp\Tools\AcademicCreateFinalExamTool;
use Modules\Academic\Mcp\Tools\AcademicCreateMockExamTool;
use Modules\Academic\Mcp\Tools\AcademicCreateModuleExamTool;
use Modules\Academic\Mcp\Tools\AcademicDeleteContentTool;
use Modules\Academic\Mcp\Tools\AcademicDeleteDraftTool;
use Modules\Academic\Mcp\Tools\AcademicDeleteExamTool;
use Modules\Academic\Mcp\Tools\AcademicDeleteLessonTool;
use Modules\Academic\Mcp\Tools\AcademicDeleteModuleTool;
use Modules\Academic\Mcp\Tools\AcademicEditActiveContentTool;
use Modules\Academic\Mcp\Tools\AcademicExpiringSubscriptionsTool;
use Modules\Academic\Mcp\Tools\AcademicGetCourseLandingTool;
use Modules\Academic\Mcp\Tools\AcademicGetCourseTool;
use Modules\Academic\Mcp\Tools\AcademicGetPermissionsTool;
use Modules\Academic\Mcp\Tools\AcademicGetStudentTool;
use Modules\Academic\Mcp\Tools\AcademicGetTeacherTool;
use Modules\Academic\Mcp\Tools\AcademicListCategoriesTool;
use Modules\Academic\Mcp\Tools\AcademicListCoursesTool;
use Modules\Academic\Mcp\Tools\AcademicListModalitiesTool;
use Modules\Academic\Mcp\Tools\AcademicListStudentsTool;
use Modules\Academic\Mcp\Tools\AcademicListTeachersTool;
use Modules\Academic\Mcp\Tools\AcademicManageActiveCourseImageTool;
use Modules\Academic\Mcp\Tools\AcademicOverviewTool;
use Modules\Academic\Mcp\Tools\AcademicSetActiveBrochureFileTool;
use Modules\Academic\Mcp\Tools\AcademicSetBrochureFileTool;
use Modules\Academic\Mcp\Tools\AcademicSetBrochureTool;
use Modules\Academic\Mcp\Tools\AcademicSetContentsTool;
use Modules\Academic\Mcp\Tools\AcademicSetCourseImageTool;
use Modules\Academic\Mcp\Tools\AcademicSetCourseLandingTool;
use Modules\Academic\Mcp\Tools\AcademicSetCourseTeachersTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateActiveCourseTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateActiveLandingTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateContentTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateCourseTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateExamTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateLandingSettingsTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateLessonTool;
use Modules\Academic\Mcp\Tools\AcademicUpdateModuleTool;
use Modules\Academic\Mcp\Tools\AcademicValidateCourseTool;

/**
 * Servidor MCP del Académico: los agentes de IA crean y corrigen cursos en
 * borrador con el mismo modelo de datos y las mismas validaciones que la web.
 */
#[Name('Academic ARACODE')]
#[Version('0.10.0')]
#[Instructions('Servidor MCP del módulo Académico de ARACODE. Al comenzar, llama academic_get_permissions para saber si la persona dio permiso absoluto (mode=autonomo) o si debes confirmar cada lote de escritura (mode=confirmar). Aun con permiso absoluto, pide confirmación explícita para borrar o para publicar una landing y respeta los avisos del cliente MCP. Antes de crear un curso, reúne el brief completo: tema y título, público, duración, modalidad, precio/descuento, fecha de inicio, evaluación, docentes y encargado, imágenes/PDFs/ZIP o estructura de carpetas, y contenido de la landing. Si falta algo, pregunta y espera la respuesta; no inventes datos. Flujo recomendado: 1) academic_get_course_options y academic_list_teachers para leer categorías, modalidades, tipos, sectores y docentes reales (no inventes ids); 2) academic_create_course_draft con una idempotency_key; 3) academic_set_course_teachers para asignar el equipo y el encargado; 4) academic_add_modules; 5) academic_add_lessons por cada módulo; 6) academic_set_contents con el texto/HTML de cada clase (nunca videos); 7) academic_add_lesson_files para materiales PDF, Excel o Word y academic_add_lesson_links para la videoconferencia (zoom, meet o teams), el video embebido (vimeo o youtube) o un enlace externo (archivo); 8) si el brief pide evaluación: academic_create_exam + academic_add_exam_questions para un examen de contenido, y academic_create_module_exam o academic_create_mock_exam para el examen regular o el simulacro de un módulo, y academic_create_final_exam para el examen final del curso; 9) academic_set_course_landing para crear o editar la landing completa (banner, secciones, inversión, FAQ y UTM), academic_set_brochure y academic_set_brochure_file para la ficha; 10) academic_validate_course, academic_get_course_landing y resumen final. Para corregir usa academic_update_course, academic_update_exam, academic_update_module, academic_update_lesson o academic_update_content; para borrar, academic_delete_exam, academic_delete_module, academic_delete_lesson o academic_delete_content con confirm: true (solo en borradores). Todo curso nace en borrador (status = false): no existe herramienta para activarlo, una persona lo revisa y lo activa en el ERP. Solo un borrador creado por un agente se puede borrar, con academic_delete_draft y confirm: true. La landing puede quedar publicada solo si la persona lo pidió expresamente; revisa antes. Límites: 20 módulos por llamada, 40 clases por módulo, 20 contenidos por clase, 10 archivos por llamada (10 MB cada uno), 120000 caracteres de HTML por contenido (máximo 64 KB al guardarse), 50 preguntas por examen. Los archivos admiten PDF, Excel (.xlsx, .xls) y Word (.doc, .docx). Los enlaces deben ser https y del dominio correcto: zoom.us, meet.google.com, teams.microsoft.com/teams.live.com/teams.cloud.microsoft, vimeo.com o youtube.com; Vimeo y YouTube se guardan como iframe de video. Consultas de estudiantes y docentes: usa academic_list_students para el listado con cursos y suscripciones, academic_get_student para la ficha completa, academic_check_student_access para verificar acceso a un curso, academic_list_expiring_subscriptions para vencimientos, academic_get_academic_overview para métricas de informes y academic_get_teacher para docentes. Estas herramientas son de solo lectura: no crean, editan ni eliminan estudiantes, docentes, matrículas o suscripciones. Nunca muestres contraseñas, hashes, tokens, secretos ni datos de autenticación; los campos de contacto son para el informe solicitado. Edición de cursos activos: solo si academic_get_permissions devuelve can_edit_active=true. Usa academic_update_active_course para datos permitidos, academic_edit_active_content para reemplazar texto/archivo/enlace o convertir una videoconferencia en video, academic_update_active_landing para secciones de landing, academic_manage_active_course_image solo para reemplazar o quitar la portada y academic_set_active_brochure_file solo para reemplazar el PDF del brochure. Antes de escribir, llama con confirm=false, muestra el antes/después y pide a la persona que confirme el curso y el elemento exacto; después repite con confirm=true y reason. En activos nunca cambies precio, descuento, tipo de curso, estado, slug, publicación ni la sección de inversión de la landing; no elimines cursos, módulos, clases, contenidos, exámenes ni documentos. Una imagen de portada sí se puede quitar. El permiso absoluto no exime de estas confirmaciones. Si lo pedido no está permitido, explica que debe hacerse desde el ERP.')]
class AcademicServer extends Server
{
    protected array $tools = [
        AcademicGetPermissionsTool::class,
        AcademicListStudentsTool::class,
        AcademicGetStudentTool::class,
        AcademicCheckStudentAccessTool::class,
        AcademicExpiringSubscriptionsTool::class,
        AcademicOverviewTool::class,
        AcademicGetTeacherTool::class,
        AcademicCourseOptionsTool::class,
        AcademicListCategoriesTool::class,
        AcademicListModalitiesTool::class,
        AcademicListTeachersTool::class,
        AcademicListCoursesTool::class,
        AcademicGetCourseTool::class,
        AcademicGetCourseLandingTool::class,
        AcademicCreateCourseDraftTool::class,
        AcademicUpdateCourseTool::class,
        AcademicUpdateExamTool::class,
        AcademicSetCourseImageTool::class,
        AcademicSetCourseTeachersTool::class,
        AcademicAddModulesTool::class,
        AcademicUpdateModuleTool::class,
        AcademicAddLessonsTool::class,
        AcademicUpdateLessonTool::class,
        AcademicSetContentsTool::class,
        AcademicAddLessonFilesTool::class,
        AcademicAddLessonLinksTool::class,
        AcademicUpdateContentTool::class,
        AcademicDeleteContentTool::class,
        AcademicDeleteLessonTool::class,
        AcademicDeleteModuleTool::class,
        AcademicDeleteExamTool::class,
        AcademicCreateExamTool::class,
        AcademicAddExamQuestionsTool::class,
        AcademicCreateModuleExamTool::class,
        AcademicCreateMockExamTool::class,
        AcademicCreateFinalExamTool::class,
        AcademicSetBrochureTool::class,
        AcademicSetBrochureFileTool::class,
        AcademicSetCourseLandingTool::class,
        AcademicUpdateLandingSettingsTool::class,
        AcademicValidateCourseTool::class,
        AcademicDeleteDraftTool::class,
        AcademicUpdateActiveCourseTool::class,
        AcademicEditActiveContentTool::class,
        AcademicUpdateActiveLandingTool::class,
        AcademicManageActiveCourseImageTool::class,
        AcademicSetActiveBrochureFileTool::class,
    ];

    protected array $resources = [
        ContentStyleGuideResource::class,
        LandingGuideResource::class,
    ];

    protected array $prompts = [
        CreateCourseFromBriefPrompt::class,
    ];
}
