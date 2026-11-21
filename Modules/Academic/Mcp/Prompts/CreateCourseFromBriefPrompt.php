<?php

namespace Modules\Academic\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

/**
 * Prompt de sistema para que el agente arme un curso completo a partir de un
 * brief corto. La primera tarea no es crear: es completar el brief con la
 * persona, consultando los docentes y opciones reales del ERP.
 */
#[Name('crear_curso_desde_brief')]
#[Description('Prepara un curso completo con su landing a partir de un brief. Antes de crear pregunta los datos que falten, consulta docentes reales y ordena las herramientas para curso, materiales, exámenes y landing.')]
class CreateCourseFromBriefPrompt extends Prompt
{
    public function handle(Request $request): Response
    {
        $brief = trim((string) $request->get('brief', ''));
        $modulos = (int) $request->get('modules', 4);
        $clases = (int) $request->get('lessons_per_module', 3);

        $modulos = max(1, min($modulos, 20));
        $clases = max(1, min($clases, 40));

        return Response::text(<<<TEXT
Vas a crear un curso completo, con su landing, en el ERP de ARACODE usando el servidor MCP "Academic".

Brief recibido:
{$brief}

## 0. Modo de permiso
Al empezar, llama a `academic_get_permissions`. Si responde `mode: autonomo`, la persona ya dio permiso absoluto para borradores: trabaja por lotes y no le pidas confirmación por cada herramienta de creación o edición de borradores. Si responde `mode: confirmar`, pide confirmación antes de cada lote de escritura y espera la respuesta. En ambos casos confirma antes de borrar o de publicar una landing, y respeta los avisos propios del cliente MCP.

Si `can_edit_active` es verdadero, existen herramientas para cursos ya activos: `academic_update_active_course`, `academic_edit_active_content`, `academic_update_active_landing`, `academic_manage_active_course_image` y `academic_set_active_brochure_file`. Para cada cambio, primero llama con `confirm=false`, muestra el antes/después y pide a la persona que confirme el curso y el elemento exacto; después repite con `confirm=true` y `reason`. El permiso absoluto no exime de esta confirmación. Nunca cambies precios, descuentos, tipo de curso, estado, slug, publicación ni la inversión de la landing, y no elimines cursos, módulos, clases, contenidos, exámenes ni documentos. Una portada sí se puede reemplazar o quitar. Si lo pedido no está permitido, explica que debe hacerse desde el ERP.

## 1. Completar el brief antes de crear
No llames a `academic_create_course_draft` hasta tener confirmados estos datos. Si el brief no los trae, pregunta de forma agrupada y espera la respuesta; no inventes precios, docentes, fechas, imágenes ni enlaces.

- Tema y título exacto del curso.
- Público objetivo, nivel y duración; modalidad (presencial, virtual o mixta).
- Categoría, tipo de curso, sector y fecha de inicio.
- Precio, descuento y si aplica a todos o a suscripción.
- Evaluación: si lleva examen de contenido, examen de módulo, simulacro o examen final.
- Docentes que participarán y quién será el **docente encargado**. Consulta primero `academic_list_teachers`; nunca inventes ids.
- Landing: fechas de inicio/fin, idioma, video de YouTube/Vimeo, WhatsApp, facilidades de pago y contacto corporativo.
- Contenido de landing: propuesta de valor, actualización profesional, staff, resultados, testimonios, plan de estudios, problema, inversión y preguntas frecuentes.
- Archivos: portada (JPG/PNG/WebP), imágenes para plan/testimonios, PDF del brochure y materiales PDF/Excel/Word por clase.
- Si la persona tiene una carpeta o ZIP, pide el árbol de carpetas (módulo → clase → archivos) o que adjunte los archivos. No prometas descomprimir un ZIP si el cliente no te lo entrega como archivo accesible.

Si la persona ya dio todos los datos, resume lo entendido y continúa. Si falta algo importante, haz las preguntas antes de crear.

## 2. Leer datos reales
1. `academic_get_course_options` para categorías, modalidades, tipos y sectores.
2. `academic_list_teachers` para el equipo docente y el encargado.
3. `academic_get_course_landing` si el curso ya existe y hay que completar su landing.
4. `academic://guides/content-style` para el tono y el HTML de las clases.

## 3. Crear la estructura
1. Crea el curso con `academic_create_course_draft` y una `idempotency_key` estable. Queda en borrador.
2. Asigna docentes con `academic_set_course_teachers`; usa `main_teacher_id` para el encargado.
3. Agrega {$modulos} módulos con `academic_add_modules` y {$clases} clases por módulo con `academic_add_lessons`.
4. Escribe cada clase con `academic_set_contents`. No uses esa herramienta para videos ni videoconferencias.
5. Materiales:
   - PDF/Excel/Word: `academic_add_lesson_files` (base64 o URL https).
   - Video: `academic_add_lesson_links` con `youtube` o `vimeo`.
   - Clase en vivo: `zoom`, `meet` o `teams`.
   - Carpeta o archivo externo: `archivo` con URL pública de Drive, OneDrive u otro.
6. Evaluaciones: `academic_create_exam` + `academic_add_exam_questions`; `academic_create_module_exam` y `academic_create_mock_exam`; `academic_create_final_exam`.

## 4. Landing
Crea o completa la landing con `academic_set_course_landing`. Puedes enviar solo las secciones que cambian:
- `professional`: actualización profesional con tarjetas (icono, título, descripción).
- `staff`: usa `teacher_ids` reales; el servicio completa nombres y cargos desde la BD.
- `results`, `problem`: tarjetas con icono, título y descripción.
- `testimonials`: nombre, presentación, testimonio y `image_url`/`image_base64` opcional.
- `study_plan`: temas numerados e `image_url`/`image_base64` opcional.
- `investment`: planes con precio anterior/actual, características y etiqueta; el primer precio actual se sincroniza al carrito cuando existe.
- `faq`: pregunta, respuesta, orden y visibilidad.
- `utm_config`: campaña y canales.
- Fechas y video del banner: `banner_start_date`, `banner_end_date`, `banner_language`, `banner_video_link` (solo YouTube/Vimeo).
- Imagen de portada: `academic_set_course_image`.
- PDF del brochure: `academic_set_brochure_file`.

Si no hay imágenes o PDFs, crea primero las secciones de texto y deja claro qué archivos faltan. No inventes testimonios, resultados ni docentes. Para publicar la landing usa `is_published: true` solo si la persona lo pidió expresamente.

## 5. Cierre
- Ejecuta `academic_validate_course` y `academic_get_course_landing`.
- Revisa que no queden secciones vacías, enlaces inválidos, exámenes sin preguntas ni archivos sin subir.
- Resume: id, URL del curso, docentes y encargado, módulos, clases, materiales, exámenes, estado de la landing y pendientes.
- Recuerda que el curso queda en borrador y que una persona debe revisarlo y activarlo en el ERP.
- Si una llamada falla por validación, corrige el argumento y reintenta sin cambiar la `idempotency_key`.
TEXT);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument('brief', 'Descripción del curso: tema, público, duración, modalidad, precio, evaluación, docentes, landing y archivos disponibles.', true),
            new Argument('modules', 'Cantidad de módulos (por defecto 4).', false),
            new Argument('lessons_per_module', 'Clases por módulo (por defecto 3).', false),
        ];
    }
}
