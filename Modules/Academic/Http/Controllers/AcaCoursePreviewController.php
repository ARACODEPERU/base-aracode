<?php

namespace Modules\Academic\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaModule;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Services\SubscriptionContentPermissions;

/**
 * Vista previa de un curso para administradores.
 *
 * Reutiliza las vistas y los constructores de datos del alumno
 * (AcaStudentController::courseLessonsData y courseLessonThemesData) para que
 * el administrador vea exactamente la misma pantalla, sin matricular, sin
 * registrar historial y sin crear intentos de examen.
 */
class AcaCoursePreviewController extends Controller
{
    public const MODE_ALL = 'all';

    public const MODE_PURCHASE = 'purchase';

    public function show(Request $request, $id)
    {
        $this->authorizePreview();

        $context = $this->buildContext($request, (int) $id);
        $data = app(AcaStudentController::class)->courseLessonsData((int) $id, true);

        // Un plan que no incluye el curso no debe mostrar módulos, igual que
        // el alumno no tendría acceso al contenido.
        if (! $context['simulation']['has_access']) {
            $data['course']['modules'] = [];
        }

        return Inertia::render('Academic::Students/Lessons', array_merge($data, [
            'preview' => $this->previewPayload($context, true),
        ]));
    }

    public function module(Request $request, $id, $moduleId)
    {
        $this->authorizePreview();

        $context = $this->buildContext($request, (int) $id);

        // Comprobar que el módulo pertenece al curso antes de construir la vista.
        AcaModule::where('course_id', $id)->findOrFail($moduleId);

        $data = app(AcaStudentController::class)->courseLessonThemesData((int) $moduleId, $context['simulation']);

        return Inertia::render('Academic::Students/Themes', array_merge($data, [
            'preview' => $this->previewPayload($context, false),
        ]));
    }

    private function authorizePreview(): void
    {
        $user = Auth::user();

        abort_unless(
            $user && $user->hasAnyRole(['admin', 'Administrador']),
            403,
            'Solo los administradores pueden usar la vista previa de cursos.'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(Request $request, int $id): array
    {
        $course = AcaCourse::findOrFail($id);

        $subscriptionTypes = AcaSubscriptionType::query()
            ->where('status', true)
            ->orderBy('order_number')
            ->orderBy('id')
            ->get(['id', 'title', 'access_all_courses', 'access_specialization_programs', 'access_all_webinars', 'can_view_specialization_zooms', 'can_view_specialization_webinars']);

        $modes = [
            [
                'value' => self::MODE_ALL,
                'label' => 'Todo el contenido (sin filtros)',
                'kind' => 'all',
            ],
        ];

        foreach ($subscriptionTypes as $subscriptionType) {
            $modes[] = [
                'value' => 'subscription:'.$subscriptionType->id,
                'label' => 'Suscripción: '.$subscriptionType->title,
                'kind' => 'subscription',
            ];
        }

        $modes[] = [
            'value' => self::MODE_PURCHASE,
            'label' => 'Curso comprado (matrícula)',
            'kind' => 'purchase',
        ];

        $selectedMode = (string) $request->query('mode', self::MODE_ALL);

        if (! collect($modes)->contains(fn ($mode) => $mode['value'] === $selectedMode)) {
            $selectedMode = self::MODE_ALL;
        }

        $selectedModeData = collect($modes)->firstWhere('value', $selectedMode);
        $simulation = $this->simulate($selectedMode, $course, $subscriptionTypes);

        return [
            'course' => $course,
            'modes' => $modes,
            'selectedMode' => $selectedMode,
            'selectedModeData' => $selectedModeData,
            'simulation' => $simulation,
        ];
    }

    /**
     * Proyecta los permisos del modo elegido sin consultar matrículas reales.
     *
     * @return array<string, mixed>
     */
    private function simulate(string $mode, AcaCourse $course, $subscriptionTypes): array
    {
        $isSpecialization = SubscriptionContentPermissions::isSpecializationCourse($course->type_description);

        $permissions = [
            SubscriptionContentPermissions::ACCESS_ALL_COURSES => false,
            SubscriptionContentPermissions::ACCESS_SPECIALIZATION_PROGRAMS => false,
            SubscriptionContentPermissions::ACCESS_ALL_WEBINARS => false,
            SubscriptionContentPermissions::CAN_VIEW_SPECIALIZATION_ZOOMS => false,
            SubscriptionContentPermissions::CAN_VIEW_SPECIALIZATION_WEBINARS => false,
        ];

        $isMatriculated = false;
        $hasAccess = true;
        $accessReason = 'Se muestran todos los contenidos del curso sin aplicar filtros.';

        if ($mode === self::MODE_PURCHASE) {
            $isMatriculated = true;
            $accessReason = 'Simula un alumno que compró el curso (matrícula activa).';
        } elseif (str_starts_with($mode, 'subscription:')) {
            $subscriptionTypeId = (int) substr($mode, strlen('subscription:'));
            $subscriptionType = $subscriptionTypes->firstWhere('id', $subscriptionTypeId);

            if ($subscriptionType) {
                foreach (array_keys($permissions) as $permission) {
                    $permissions[$permission] = (bool) $subscriptionType->{$permission};
                }

                $hasAccess = SubscriptionContentPermissions::canAccessCourse($permissions, $course->type_description)
                    || ! $course->price
                    || (float) $course->price <= 0;
                $accessReason = $hasAccess
                    ? 'Simula una suscripción activa del plan "'.$subscriptionType->title.'".'
                    : 'Con el plan "'.$subscriptionType->title.'" el alumno no tiene acceso a este curso.';
            }
        }

        $zoomVisible = $isSpecialization
            ? ($isMatriculated || $permissions[SubscriptionContentPermissions::CAN_VIEW_SPECIALIZATION_ZOOMS])
            : true;

        $webinarVisible = $isMatriculated
            ? false
            : ($isSpecialization
                ? $permissions[SubscriptionContentPermissions::CAN_VIEW_SPECIALIZATION_WEBINARS]
                : true);

        if ($mode === self::MODE_ALL) {
            $zoomVisible = true;
            $webinarVisible = true;
        }

        return [
            'kind' => str_starts_with($mode, 'subscription:') ? 'subscription' : $mode,
            'show_all' => $mode === self::MODE_ALL,
            'is_matriculated' => $isMatriculated,
            'has_access' => $hasAccess,
            'access_reason' => $accessReason,
            'is_specialization_course' => $isSpecialization,
            'zoom_visible' => $zoomVisible,
            'webinar_visible' => $webinarVisible,
            'permissions' => $permissions,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function previewPayload(array $context, bool $promptReset): array
    {
        $course = $context['course'];
        $selectedMode = $context['selectedMode'];

        return [
            'active' => true,
            'course_id' => $course->id,
            'course_title' => $course->description,
            'mode' => $selectedMode,
            'mode_label' => $context['selectedModeData']['label'],
            'modes' => $context['modes'],
            'progress_key' => 'academic-preview-progress:'.$course->id.':'.$selectedMode,
            'prompt_reset' => $promptReset,
            'has_access' => $context['simulation']['has_access'],
            'access_reason' => $context['simulation']['access_reason'],
            'back_url' => route('aca_courses_list'),
            'course_url' => route('aca_courses_preview', [
                'id' => $course->id,
                'mode' => $selectedMode,
            ]),
        ];
    }
}
