<?php

namespace Modules\Academic\Services;

use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaSubscriptionType;

/**
 * Permisos que otorgan las suscripciones activas de un alumno.
 *
 * Cada tipo de suscripción declara, desde la pantalla de suscripciones, qué
 * incluye:
 *
 * - Alcance del catálogo: todos los cursos o talleres, todos los Programas de
 *   Especialización y todos los webinars.
 * - Contenido de los Programas de Especialización: enlaces Meet/Zoom y
 *   contenido tipo Webinar.
 *
 * Nada depende del nombre o del título del plan: renombrarlo no cambia el
 * acceso. Cuando el alumno tiene varias suscripciones vigentes, los permisos se
 * combinan (basta que una los conceda).
 *
 * "Vigente" lo define AcaStudentSubscription::scopeActiveNow(): el estado manda
 * salvo que la fecha de fin ya haya pasado, así el acceso no depende de que el
 * comando de vencimiento haya corrido.
 */
class SubscriptionContentPermissions
{
    /** Incluye todos los cursos o talleres (no especialización ni webinars). */
    public const ACCESS_ALL_COURSES = 'access_all_courses';

    /** Incluye todos los Programas de Especialización. */
    public const ACCESS_SPECIALIZATION_PROGRAMS = 'access_specialization_programs';

    /** Incluye todos los cursos de tipo Webinar. */
    public const ACCESS_ALL_WEBINARS = 'access_all_webinars';

    /** Acceso a los enlaces Meet/Zoom dentro de Programas de Especialización. */
    public const CAN_VIEW_SPECIALIZATION_ZOOMS = 'can_view_specialization_zooms';

    /** Acceso al contenido tipo Webinar dentro de Programas de Especialización. */
    public const CAN_VIEW_SPECIALIZATION_WEBINARS = 'can_view_specialization_webinars';

    /** Tipo de curso de los Programas de Especialización. */
    public const SPECIALIZATION_TYPE = 'Programas de Especialización';

    /** Tipo de curso de los webinars. */
    public const WEBINAR_TYPE = 'Webinar';

    /**
     * Permisos del alumno combinando sus suscripciones activas.
     *
     * @return array{
     *     access_all_courses: bool,
     *     access_specialization_programs: bool,
     *     access_all_webinars: bool,
     *     can_view_specialization_zooms: bool,
     *     can_view_specialization_webinars: bool
     * }
     */
    public static function forStudent(?int $studentId): array
    {
        if (! $studentId) {
            return self::none();
        }

        $subscriptionTypeIds = AcaStudentSubscription::query()
            ->where('student_id', $studentId)
            ->activeNow()
            ->pluck('subscription_id')
            ->filter()
            ->unique();

        if ($subscriptionTypeIds->isEmpty()) {
            return self::none();
        }

        $subscriptionTypes = AcaSubscriptionType::query()
            ->whereIn('id', $subscriptionTypeIds)
            ->get([
                'access_all_courses',
                'access_specialization_programs',
                'access_all_webinars',
                'can_view_specialization_zooms',
                'can_view_specialization_webinars',
            ]);

        $granted = [];

        foreach (array_keys(self::none()) as $permission) {
            $granted[$permission] = $subscriptionTypes->contains(
                fn ($type) => (bool) $type->{$permission}
            );
        }

        return $granted;
    }

    /**
     * ¿Alguna suscripción activa incluye los Programas de Especialización?
     *
     * @param  array<string, bool>  $permissions
     */
    public static function includesSpecializationPrograms(array $permissions): bool
    {
        return $permissions[self::ACCESS_SPECIALIZATION_PROGRAMS] ?? false;
    }

    /**
     * ¿La suscripción da acceso al catálogo al que pertenece el curso?
     *
     * @param  array<string, bool>  $permissions
     */
    public static function canAccessCourse(array $permissions, ?string $courseType): bool
    {
        if (self::isSpecializationCourse($courseType)) {
            return $permissions[self::ACCESS_SPECIALIZATION_PROGRAMS] ?? false;
        }

        if (self::isWebinarCourse($courseType)) {
            return $permissions[self::ACCESS_ALL_WEBINARS] ?? false;
        }

        return $permissions[self::ACCESS_ALL_COURSES] ?? false;
    }

    public static function isSpecializationCourse(?string $courseType): bool
    {
        return strcasecmp((string) $courseType, self::SPECIALIZATION_TYPE) === 0;
    }

    public static function isWebinarCourse(?string $courseType): bool
    {
        return strcasecmp((string) $courseType, self::WEBINAR_TYPE) === 0;
    }

    /**
     * Todos los permisos en falso.
     *
     * @return array{
     *     access_all_courses: bool,
     *     access_specialization_programs: bool,
     *     access_all_webinars: bool,
     *     can_view_specialization_zooms: bool,
     *     can_view_specialization_webinars: bool
     * }
     */
    private static function none(): array
    {
        return [
            self::ACCESS_ALL_COURSES => false,
            self::ACCESS_SPECIALIZATION_PROGRAMS => false,
            self::ACCESS_ALL_WEBINARS => false,
            self::CAN_VIEW_SPECIALIZATION_ZOOMS => false,
            self::CAN_VIEW_SPECIALIZATION_WEBINARS => false,
        ];
    }
}
