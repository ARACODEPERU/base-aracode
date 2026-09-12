<?php

namespace App\Services;

use App\Models\Parameter;
use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Academic\Entities\AcaCertificate;
use Modules\Academic\Entities\AcaContent;
use Modules\Academic\Entities\AcaCourse;
use Modules\Academic\Entities\AcaStudentHistory;
use Modules\CMS\Entities\CmsTestimony;

/**
 * Reglas del apartado "Testimonios" del alumno.
 *
 * Puede dejar testimonio cualquier alumno con algun curso de precio mayor a 0
 * o con una suscripcion activa y vigente, pero solo sobre los cursos que haya
 * culminado (100% de los contenidos revisados). En los Programas de
 * Especializacion ademas debe existir el certificado del curso emitido.
 */
class StudentTestimonyAccess
{
    /**
     * Parametro del sistema con el texto del aviso de autorizacion.
     */
    public const CONSENT_PARAMETER_CODE = 'P000033';

    public const DEFAULT_CONSENT_TEXT = 'Autorizo a CPA Academy a publicar mi testimonio en su pagina web y redes sociales, incluida la imagen que comparti, con fines de difusion academica.';

    public const SPECIALIZATION_TYPE = 'Programas de Especialización';

    /**
     * Curso de pago o suscripcion activa y vigente.
     */
    public static function canParticipate(?User $user): bool
    {
        return JobOffersAccess::canView($user);
    }

    public static function studentId(?User $user): ?int
    {
        return JobOffersAccess::studentId($user);
    }

    /**
     * Texto del aviso de autorizacion (editable desde Parametros del sistema).
     */
    public static function consentText(): string
    {
        $text = Parameter::where('parameter_code', self::CONSENT_PARAMETER_CODE)->value('value_default');

        $text = $text ? trim(htmlspecialchars_decode($text, ENT_QUOTES)) : '';

        return $text !== '' ? $text : self::DEFAULT_CONSENT_TEXT;
    }

    /**
     * Identificador corto de la version del aviso aceptado.
     */
    public static function consentVersion(): string
    {
        return substr(sha1(self::consentText()), 0, 12);
    }

    public static function isSpecialization(?AcaCourse $course): bool
    {
        return $course && strcasecmp((string) $course->type_description, self::SPECIALIZATION_TYPE) === 0;
    }

    /**
     * Cursos culminados por el alumno (100% de contenidos revisados) y que
     * ademas cumplen la regla de certificado cuando son de especializacion.
     *
     * Devuelve una coleccion de arreglos listos para el frontend, incluyendo
     * el testimonio del alumno (si ya lo dejo).
     */
    public static function completedCourses(?User $user): Collection
    {
        $studentId = self::studentId($user);

        if (!$user || !$user->person_id || !$studentId) {
            return collect();
        }

        $personId = $user->person_id;

        $courseIds = AcaStudentHistory::where('person_id', $personId)
            ->whereNotNull('course_id')
            ->distinct()
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($courseIds->isEmpty()) {
            return collect();
        }

        // Total de contenidos por curso (se excluyen los exámenes: is_file = 4).
        $totals = AcaContent::query()
            ->join('aca_themes', 'aca_themes.id', '=', 'aca_contents.theme_id')
            ->join('aca_modules', 'aca_modules.id', '=', 'aca_themes.module_id')
            ->whereIn('aca_modules.course_id', $courseIds)
            ->whereRaw('(aca_contents.is_file IS NULL OR aca_contents.is_file <> 4)')
            ->groupBy('aca_modules.course_id')
            ->selectRaw('aca_modules.course_id as course_id, COUNT(*) as total')
            ->pluck('total', 'course_id');

        $viewed = AcaStudentHistory::where('person_id', $personId)
            ->whereIn('course_id', $courseIds)
            ->groupBy('course_id')
            ->selectRaw('course_id, COUNT(DISTINCT content_id) as total')
            ->pluck('total', 'course_id');

        $completedIds = $courseIds->filter(function ($courseId) use ($totals, $viewed) {
            $total = (int) ($totals[$courseId] ?? 0);

            return $total > 0 && (int) ($viewed[$courseId] ?? 0) >= $total;
        })->values();

        if ($completedIds->isEmpty()) {
            return collect();
        }

        $courses = AcaCourse::with('category')
            ->whereIn('id', $completedIds)
            ->where('status', true)
            ->orderBy('description')
            ->get();

        $testimonies = CmsTestimony::where('source', CmsTestimony::SOURCE_STUDENT)
            ->where('student_id', $studentId)
            ->whereIn('course_id', $courses->pluck('id'))
            ->get()
            ->keyBy('course_id');

        $certificates = AcaCertificate::where('student_id', $studentId)
            ->whereNull('module_id')
            ->whereIn('course_id', $courses->pluck('id'))
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $courses
            ->filter(function (AcaCourse $course) use ($certificates) {
                if (!self::isSpecialization($course)) {
                    return true;
                }

                return in_array((int) $course->id, $certificates, true);
            })
            ->map(function (AcaCourse $course) use ($testimonies, $totals, $viewed, $certificates) {
                $testimony = $testimonies->get($course->id);
                $total = (int) ($totals[$course->id] ?? 0);
                $seen = (int) ($viewed[$course->id] ?? 0);

                return [
                    'id' => (int) $course->id,
                    'description' => $course->description,
                    'image' => $course->image,
                    'category' => $course->category?->description,
                    'type_description' => $course->type_description,
                    'is_specialization' => self::isSpecialization($course),
                    'certificate_exists' => in_array((int) $course->id, $certificates, true),
                    'progress' => $total > 0 ? (int) round(($seen / $total) * 100) : 100,
                    'contents_total' => $total,
                    'contents_viewed' => $seen,
                    'testimony' => $testimony ? self::testimonyPayload($testimony) : null,
                ];
            })
            ->values();
    }

    /**
     * Datos del testimonio expuestos al alumno.
     * Los rechazados no se muestran (solo habilitan volver a enviarlo).
     */
    public static function testimonyPayload(CmsTestimony $testimony): array
    {
        $editableUntil = $testimony->editableUntil();

        return [
            'id' => $testimony->id,
            'course_id' => $testimony->course_id ? (int) $testimony->course_id : null,
            'rating' => $testimony->rating,
            'comment' => $testimony->description,
            'author_name' => $testimony->author_name,
            'author_role' => $testimony->author_role,
            'photo' => $testimony->image,
            'approval_status' => $testimony->approval_status,
            'created_at' => optional($testimony->created_at)->toIso8601String(),
            'editable_until' => optional($editableUntil)->toIso8601String(),
            'can_edit' => $testimony->isEditableByStudent(),
            'is_rejected' => $testimony->approval_status === CmsTestimony::STATUS_REJECTED,
        ];
    }

    /**
     * Un testimonio solo lo puede modificar su autor, mientras no haya sido
     * aprobado (ya publicado en la web) y dentro de las 15 horas.
     */
    public static function canEdit(?CmsTestimony $testimony, ?int $studentId): bool
    {
        if (!$testimony || !$studentId) {
            return false;
        }

        if ($testimony->approval_status === CmsTestimony::STATUS_APPROVED) {
            return false;
        }

        return (int) $testimony->student_id === (int) $studentId
            && $testimony->isEditableByStudent();
    }
}
