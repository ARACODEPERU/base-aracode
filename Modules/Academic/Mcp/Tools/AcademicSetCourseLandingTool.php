<?php

namespace Modules\Academic\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Services\CourseLandingService;

/**
 * Crea o actualiza la landing completa del curso. Las secciones usan la misma
 * estructura JSON que el mantenedor web; solo se tocan las que se envían.
 */
#[Name('academic_set_course_landing')]
#[Description('Crea la landing del curso si no existe y actualiza su configuración y secciones (banner, actualización profesional, staff, resultados, testimonios, plan de estudios, problema, inversión, FAQ y UTM). Envía solo lo que cambia. Para publicar, envía is_published: true solo si la persona lo pidió.')]
class AcademicSetCourseLandingTool extends AcademicTool
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
            'banner_start_date' => 'sometimes|nullable|date',
            'banner_end_date' => 'sometimes|nullable|date',
            'banner_language' => 'sometimes|required|in:es,en,zh',
            'is_published' => 'sometimes|boolean',

            'professional' => 'sometimes|nullable|array',
            'professional.name' => 'sometimes|nullable|string|max:255',
            'professional.title' => 'sometimes|nullable|string|max:300',
            'professional.description' => 'sometimes|nullable|string|max:10000',
            'professional.items' => 'sometimes|array|max:20',
            'professional.items.*.icon' => 'sometimes|nullable|string|max:100',
            'professional.items.*.title' => 'sometimes|nullable|string|max:255',
            'professional.items.*.description' => 'sometimes|nullable|string|max:5000',

            'staff' => 'sometimes|nullable|array',
            'staff.name' => 'sometimes|nullable|string|max:255',
            'staff.title' => 'sometimes|nullable|string|max:300',
            'staff.description' => 'sometimes|nullable|string|max:10000',
            'staff.teacher_ids' => 'sometimes|array|max:20',
            'staff.teacher_ids.*' => 'integer|exists:aca_teachers,id',
            'staff.teachers' => 'sometimes|array|max:20',
            'staff.teachers.*.teacher_id' => 'required_with:staff.teachers|integer|exists:aca_teachers,id',
            'staff.teachers.*.teacher_names' => 'sometimes|nullable|string|max:255',
            'staff.teachers.*.teacher_ocupation' => 'sometimes|nullable|string|max:255',

            'results' => 'sometimes|nullable|array',
            'results.name' => 'sometimes|nullable|string|max:255',
            'results.title' => 'sometimes|nullable|string|max:300',
            'results.description' => 'sometimes|nullable|string|max:10000',
            'results.items' => 'sometimes|array|max:20',
            'results.items.*.icon' => 'sometimes|nullable|string|max:100',
            'results.items.*.title' => 'sometimes|nullable|string|max:255',
            'results.items.*.description' => 'sometimes|nullable|string|max:5000',

            'testimonials' => 'sometimes|nullable|array',
            'testimonials.name' => 'sometimes|nullable|string|max:255',
            'testimonials.title' => 'sometimes|nullable|string|max:300',
            'testimonials.description' => 'sometimes|nullable|string|max:10000',
            'testimonials.items' => 'sometimes|array|max:20',
            'testimonials.items.*.name' => 'required_with:testimonials.items|string|max:255',
            'testimonials.items.*.presentation' => 'sometimes|nullable|string|max:255',
            'testimonials.items.*.description' => 'sometimes|nullable|string|max:10000',
            'testimonials.items.*.image' => 'sometimes|nullable|string|max:500',
            'testimonials.items.*.image_url' => 'sometimes|nullable|url:https|max:1500',
            'testimonials.items.*.image_base64' => 'sometimes|nullable|string',

            'study_plan' => 'sometimes|nullable|array',
            'study_plan.name' => 'sometimes|nullable|string|max:255',
            'study_plan.title' => 'sometimes|nullable|string|max:300',
            'study_plan.description' => 'sometimes|nullable|string|max:10000',
            'study_plan.image' => 'sometimes|nullable|string|max:500',
            'study_plan.image_url' => 'sometimes|nullable|url:https|max:1500',
            'study_plan.image_base64' => 'sometimes|nullable|string',
            'study_plan.items' => 'sometimes|array|max:40',
            'study_plan.items.*.number' => 'sometimes|integer|min:1',
            'study_plan.items.*.title' => 'required_with:study_plan.items|string|max:255',
            'study_plan.items.*.description' => 'sometimes|nullable|string|max:10000',

            'problem' => 'sometimes|nullable|array',
            'problem.name' => 'sometimes|nullable|string|max:255',
            'problem.title' => 'sometimes|nullable|string|max:300',
            'problem.description' => 'sometimes|nullable|string|max:10000',
            'problem.items' => 'sometimes|array|max:20',
            'problem.items.*.icon' => 'sometimes|nullable|string|max:100',
            'problem.items.*.title' => 'sometimes|nullable|string|max:255',
            'problem.items.*.description' => 'sometimes|nullable|string|max:5000',

            'investment' => 'sometimes|nullable|array',
            'investment.name' => 'sometimes|nullable|string|max:255',
            'investment.title' => 'sometimes|nullable|string|max:300',
            'investment.description' => 'sometimes|nullable|string|max:10000',
            'investment.items' => 'sometimes|array|max:4',
            'investment.items.*.tag' => 'sometimes|nullable|string|max:100',
            'investment.items.*.title' => 'sometimes|nullable|string|max:255',
            'investment.items.*.description' => 'sometimes|nullable|string|max:10000',
            'investment.items.*.price_before' => 'sometimes|nullable|string|max:50',
            'investment.items.*.price_before_text' => 'sometimes|nullable|string|max:50',
            'investment.items.*.price_before_visible' => 'sometimes|boolean',
            'investment.items.*.price_now' => 'sometimes|nullable|string|max:50',
            'investment.items.*.price_now_text' => 'sometimes|nullable|string|max:50',
            'investment.items.*.features' => 'sometimes|array|max:20',
            'investment.items.*.features.*' => 'string|max:255',

            'faq' => 'sometimes|nullable|array',
            'faq.name' => 'sometimes|nullable|string|max:255',
            'faq.title' => 'sometimes|nullable|string|max:300',
            'faq.description' => 'sometimes|nullable|string|max:10000',
            'faq.items' => 'sometimes|array|max:30',
            'faq.items.*.question' => 'required_with:faq.items|string|max:500',
            'faq.items.*.answer' => 'sometimes|nullable|string|max:10000',
            'faq.items.*.sort' => 'sometimes|integer|min:0',
            'faq.items.*.visible' => 'sometimes|boolean',

            'utm_config' => 'sometimes|nullable|array',
            'utm_config.campaign' => 'sometimes|nullable|string|max:255',
            'utm_config.channels' => 'sometimes|array|max:30',
            'utm_config.channels.*.id' => 'required_with:utm_config.channels|string|max:100',
            'utm_config.channels.*.term' => 'sometimes|nullable|string|max:500',
            'utm_config.channels.*.content' => 'sometimes|nullable|string|max:500',
            'utm_config.channels.*.active' => 'sometimes|boolean',
        ], [
            'url_slug.max' => 'El slug admite como máximo 120 caracteres.',
            'banner_language.in' => 'El idioma debe ser es, en o zh.',
            'banner_video_link.max' => 'El video del banner no puede superar 1500 caracteres.',
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
            'note' => 'La landing quedó guardada. La vista pública se revisa y se publica desde el ERP cuando corresponda.',
        ]);
    }
}
