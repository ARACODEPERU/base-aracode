<?php

namespace Modules\Academic\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

/**
 * Guía para que el agente sepa qué preguntar y cómo armar la landing sin
 * inventar secciones ni datos.
 */
#[Uri('academic://guides/course-landing')]
#[MimeType('text/markdown')]
#[Description('Guía para crear la landing de un curso: preguntas previas, secciones, imágenes, PDF, docentes y publicación.')]
class LandingGuideResource extends Resource
{
    public function handle(Request $request): Response
    {
        return Response::text(<<<'MD'
# Landing de un curso

## Antes de escribir
Pregunta y confirma:
- Título, tema, público y propuesta de valor.
- Fechas de inicio y fin, idioma y video de YouTube o Vimeo.
- Docentes que aparecen en el staff y quién es el encargado.
- Precio, descuento y planes de inversión.
- Imágenes: portada, plan de estudios y fotos de testimonios.
- PDF del brochure y materiales PDF/Excel/Word de cada clase.
- Si hay una carpeta o ZIP: pide el árbol módulo → clase → archivo, o que adjunte los archivos. No inventes rutas locales.

## Secciones y estructura
`academic_set_course_landing` acepta solo las secciones que cambian:
- `professional`, `results`, `problem`: `name`, `title`, `description`, `items[]` con `icon`, `title`, `description`.
- `staff`: `name`, `title`, `description`, `teacher_ids[]` reales de `academic_list_teachers` (o `teachers[]` con `teacher_id`). La BD completa nombres y cargos.
- `testimonials`: `items[]` con `name`, `presentation`, `description`, `image_url` o `image_base64`.
- `study_plan`: `name`, `title`, `description`, `image_url`/`image_base64`, `items[]` con `number`, `title`, `description`.
- `investment`: `items[]` con `tag`, `title`, `description`, `price_before*`, `price_now*`, `features[]`.
- `faq`: `items[]` con `question`, `answer`, `sort`, `visible`.
- `utm_config`: `campaign`, `channels[]` con `id`, `term`, `content`, `active`.

## Assets
- Portada: `academic_set_course_image`, JPG/PNG/WebP, mínimo 600x300, hasta 3 MB.
- Imágenes de landing: URL https o base64; se guardan en `uploads/landing/...`.
- Brochure: `academic_set_brochure_file` con un PDF.
- Materiales de clase: `academic_add_lesson_files` con PDF, Excel o Word.
- Videos: `academic_add_lesson_links` con `youtube` o `vimeo`; nunca metas un iframe en un texto.
- Videoconferencias: `academic_add_lesson_links` con `zoom`, `meet` o `teams`.
- Archivos externos: `academic_add_lesson_links` con `archivo` y una URL pública (Drive, OneDrive, etc.).

## Reglas
- No inventes testimonios, resultados, docentes, precios ni fechas.
- La landing nace sin publicar. Usa `is_published: true` solo si la persona lo pidió expresamente.
- Después de guardar, llama a `academic_get_course_landing` y revisa `missing`.
- El curso sigue en borrador: una persona lo revisa y lo activa en el ERP.
MD);
    }
}
