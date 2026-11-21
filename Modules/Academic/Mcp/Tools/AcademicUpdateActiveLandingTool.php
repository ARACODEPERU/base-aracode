<?php

namespace Modules\Academic\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Modules\Academic\Entities\AcaCourseLanding;
use Modules\Academic\Services\CourseLandingService;

/**
 * Edita las secciones de una landing activa sin tocar inversión/precios,
 * publicación ni slug. Las imágenes de secciones se pueden reemplazar o quitar
 * enviando la sección sin imagen.
 */
#[Name('academic_update_active_landing')]
#[Description('Permiso de activos. Edita secciones de una landing de un curso ACTIVO: profesional, staff, resultados, testimonios, plan de estudios, problema, FAQ, UTM, enlaces y video del banner. No permite cambiar precios/inversión, publicar/despublicar ni cambiar el slug. Para quitar una imagen de una sección, envía esa sección sin image. Primero usa confirm=false, muestra el cambio y luego confirma con confirm=true y reason.')]
class AcademicUpdateActiveLandingTool extends AcademicTool
{
    protected ?string $permission = 'aca_cursos_editar';

    protected string $ability = 'academic:write_active';

    protected function execute(Request $request): Response
    {
        $datos = $request->validate([
            'course_id' => 'required|integer',

            'whatsapp_link' => 'sometimes|nullable|url:https|max:500',
            'flow_id' => 'sometimes|nullable|string|max:255',
            'payment_facilities_link' => 'sometimes|nullable|url:https|max:500',
            'corporate_contact_link' => 'sometimes|nullable|url:https|max:500',
            'banner_video_link' => 'sometimes|nullable|string|max:1500',
            'banner_start_date' => 'sometimes|nullable|date',
            'banner_end_date' => 'sometimes|nullable|date',
            'banner_language' => 'sometimes|required|in:es,en,zh',

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
            'testimonials.items.*.image' => 'sometimes|nullable|string|max:255',
            'testimonials.items.*.image_url' => 'sometimes|nullable|url:https|max:2048',
            'testimonials.items.*.image_base64' => 'sometimes|nullable|string',

            'study_plan' => 'sometimes|nullable|array',
            'study_plan.name' => 'sometimes|nullable|string|max:255',
            'study_plan.title' => 'sometimes|nullable|string|max:300',
            'study_plan.description' => 'sometimes|nullable|string|max:10000',
            'study_plan.image' => 'sometimes|nullable|string|max:255',
            'study_plan.image_url' => 'sometimes|nullable|url:https|max:2048',
            'study_plan.image_base64' => 'sometimes|nullable|string',
            'study_plan.items' => 'sometimes|array|max:40',
            'study_plan.items.*.number' => 'sometimes|integer',
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

            'faq' => 'sometimes|nullable|array',
            'faq.name' => 'sometimes|nullable|string|max:255',
            'faq.title' => 'sometimes|nullable|string|max:300',
            'faq.description' => 'sometimes|nullable|string|max:10000',
            'faq.items' => 'sometimes|array|max:30',
            'faq.items.*.question' => 'required_with:faq.items|string|max:500',
            'faq.items.*.answer' => 'sometimes|nullable|string|max:10000',
            'faq.items.*.sort' => 'sometimes|integer',
            'faq.items.*.visible' => 'sometimes|boolean',

            'utm_config' => 'sometimes|array',
            'utm_config.campaign' => 'sometimes|nullable|string|max:255',
            'utm_config.channels' => 'sometimes|array|max:30',
            'utm_config.channels.*.id' => 'required_with:utm_config.channels|string|max:100',
            'utm_config.channels.*.term' => 'sometimes|nullable|string|max:500',
            'utm_config.channels.*.content' => 'sometimes|nullable|string|max:500',
            'utm_config.channels.*.active' => 'sometimes|boolean',

            'confirm' => 'sometimes|boolean',
            'reason' => 'nullable|string|min:10|max:500|required_if:confirm,true',
        ], [
            'reason.required_if' => 'Para aplicar un cambio en una landing activa escribe el motivo y la confirmación de la persona.',
        ], [
            'course_id' => 'id del curso',
            'confirm' => 'confirmación',
            'reason' => 'motivo',
        ]);

        // Se revisa el payload completo, no solo los campos validados, para que
        // no se cuele investment/is_published/url_slug ni un precio.
        $this->activeEditing()->rechazarCamposLanding($request->all());

        $curso = $this->activeEditing()->cursoActivo((int) $datos['course_id']);
        $landing = AcaCourseLanding::where('course_id', $curso->id)->first();

        if (! $landing) {
            throw ValidationException::withMessages([
                'course_id' => 'El curso activo no tiene landing. Créala y revísala desde el ERP.',
            ]);
        }

        $cambios = $datos;
        unset($cambios['course_id'], $cambios['confirm'], $cambios['reason']);

        if ($cambios === []) {
            throw ValidationException::withMessages([
                'landing' => 'No enviaste ninguna sección o ajuste de la landing para cambiar.',
            ]);
        }

        if (! ($datos['confirm'] ?? false)) {
            return $this->json([
                'requires_confirmation' => true,
                'course_id' => $curso->id,
                'preview' => [
                    'current' => app(CourseLandingService::class)->state($landing),
                    'proposed_fields' => array_keys($cambios),
                    'proposed' => $cambios,
                ],
                'note' => 'No se guardó nada. Revisa la landing actual y el cambio propuesto con la persona. Vuelve a llamar con confirm=true y reason. No se aceptan precios, inversión, publicación ni slug.',
            ]);
        }

        $resultado = $this->activeEditing()->actualizarLanding($curso, $cambios);

        return $this->json([
            'requires_confirmation' => false,
            'reason' => $datos['reason'],
            'result' => $resultado,
            'note' => 'Landing actualizada solo en las secciones enviadas. La inversión, los precios, la publicación y el slug no cambiaron.',
        ]);
    }
}
