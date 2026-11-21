<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Services\CourseLandingService;

/**
 * Ajustes simples de la landing. Si el curso todavía no tiene landing, esta
 * herramienta la crea con un slug único; las secciones se editan con
 * academic_set_course_landing.
 */
#[Name('academic_update_landing_settings')]
#[Description('Crea la landing si no existe y actualiza sus ajustes: url_slug, whatsapp_link, flow_id, facilidades de pago, contacto corporativo, video del banner o is_published. Para las secciones usa academic_set_course_landing.')]
class AcademicUpdateLandingSettingsTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer',
            'url_slug' => 'sometimes|nullable|string|max:120',
            'whatsapp_link' => 'sometimes|nullable|url:https|max:500',
            'flow_id' => 'sometimes|nullable|string|max:255',
            'payment_facilities_link' => 'sometimes|nullable|url:https|max:500',
            'corporate_contact_link' => 'sometimes|nullable|url:https|max:500',
            'banner_video_link' => 'sometimes|nullable|string|max:1500',
            'is_published' => 'sometimes|boolean',
        ], [
            'url_slug.max' => 'El slug admite como máximo 120 caracteres.',
        ], [
            'course_id' => 'id del curso',
            'url_slug' => 'slug de la landing',
        ]);

        $curso = $this->cursoBorrador((int) $datos['course_id']);
        $servicio = app(CourseLandingService::class);
        $landing = $servicio->update($curso, $datos);
        $estado = $servicio->state($landing);

        return $this->json([
            'course_id' => $curso->id,
            'landing' => $estado,
            'missing' => $estado['missing'],
            'note' => 'La landing se crea o actualiza. Las secciones y el brochure se completan con academic_set_course_landing y academic_set_brochure_file.',
        ]);
    }
}
