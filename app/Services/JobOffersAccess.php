<?php

namespace App\Services;

use App\Models\Parameter;
use App\Models\User;
use Carbon\Carbon;
use Modules\Academic\Entities\AcaCapRegistration;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaStudentSubscription;

/**
 * Acceso a la seccion "Ofertas Laborales" y al codigo HTML/iframe que la alimenta.
 *
 * Puede verla cualquier alumno que haya comprado algun curso con precio mayor a 0
 * o que tenga una suscripcion activa y vigente.
 */
class JobOffersAccess
{
    /**
     * Parametro del sistema que contiene el codigo HTML/iframe de la vista.
     */
    public const PARAMETER_CODE = 'P000032';

    /**
     * Id del alumno (aca_students) asociado a la persona del usuario autenticado.
     */
    public static function studentId(?User $user): ?int
    {
        if (!$user || !$user->person_id) {
            return null;
        }

        return AcaStudent::where('person_id', $user->person_id)->value('id');
    }

    /**
     * Suscripcion activa (status = true) o vigente por fechas.
     */
    public static function hasActiveSubscription(?int $studentId): bool
    {
        if (!$studentId) {
            return false;
        }

        $today = Carbon::today();

        return AcaStudentSubscription::where('student_id', $studentId)
            ->where(function ($query) use ($today) {
                $query->where('status', true)
                    ->orWhere(function ($q) use ($today) {
                        $q->whereDate('date_start', '<=', $today)
                            ->whereDate('date_end', '>=', $today);
                    });
            })
            ->exists();
    }

    /**
     * Algun curso comprado con precio mayor a 0 (aunque la matricula ya haya vencido).
     */
    public static function hasPaidCourse(?int $studentId): bool
    {
        if (!$studentId) {
            return false;
        }

        return AcaCapRegistration::query()
            ->join('aca_courses', 'aca_courses.id', '=', 'aca_cap_registrations.course_id')
            ->where('aca_cap_registrations.student_id', $studentId)
            ->where('aca_courses.price', '>', 0)
            ->exists();
    }

    /**
     * Curso de pago O suscripcion activa y vigente.
     */
    public static function canView(?User $user): bool
    {
        $studentId = self::studentId($user);

        if (!$studentId) {
            return false;
        }

        return self::hasPaidCourse($studentId) || self::hasActiveSubscription($studentId);
    }

    /**
     * Codigo HTML/iframe configurado en el parametro del sistema.
     * Devuelve null cuando el parametro esta vacio.
     */
    public static function iframeCode(): ?string
    {
        $code = Parameter::where('parameter_code', self::PARAMETER_CODE)->value('value_default');

        if (!$code) {
            return null;
        }

        // El formulario de edicion de parametros guarda el HTML con entidades.
        $code = trim(htmlspecialchars_decode($code, ENT_QUOTES));

        return $code === '' ? null : $code;
    }
}
