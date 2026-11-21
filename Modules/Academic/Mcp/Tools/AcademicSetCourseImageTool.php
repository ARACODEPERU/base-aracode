<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;

/**
 * Portada del curso: imagen JPG, PNG o WebP en base64 o URL https pública.
 */
#[Name('academic_set_course_image')]
#[Description('Carga la portada de un curso en borrador. Acepta image_base64 (data URI) o image_url (https público). Formatos JPG, PNG o WebP de hasta 3 MB y al menos 600x300 píxeles.')]
class AcademicSetCourseImageTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer',
            'image_base64' => 'required_without:image_url|nullable|string',
            'image_url' => 'required_without:image_base64|nullable|url:https',
        ], [
            'image_base64.required_without' => 'Envía image_base64 o image_url.',
            'image_url.required_without' => 'Envía image_base64 o image_url.',
        ], [
            'course_id' => 'id del curso',
            'image_base64' => 'imagen en base64',
            'image_url' => 'URL de la imagen',
        ]);

        $curso = $this->cursoBorrador((int) $datos['course_id']);

        $ruta = $this->authoring()->setImage(
            $curso,
            (string) ($datos['image_base64'] ?? $datos['image_url'] ?? '')
        );

        return $this->json([
            'course_id' => $curso->id,
            'image' => $ruta,
            'url' => asset('storage/' . $ruta),
            'message' => 'Portada guardada. Revisa que se vea bien antes de activar el curso.',
        ]);
    }
}
