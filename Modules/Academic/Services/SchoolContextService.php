<?php

namespace Modules\Academic\Services;

use App\Models\Parameter;
use Illuminate\Support\Facades\Cache;
use Modules\Academic\Entities\AcaSchool;

/**
 * Contexto de colegio para el modulo escolar.
 *
 * - El interruptor PTM0005 (tabla parameters) define si el sistema trabaja
 *   con multiples colegios (feature de pago). Desactivado (default) todo
 *   opera sobre el colegio por defecto.
 * - currentSchool() jamas debe tumbar una pagina: si no hay colegios
 *   registrados devuelve null y los controladores deciden como reaccionar.
 */
class SchoolContextService
{
    /** Codigo de parametro del interruptor multi-colegio (tabla parameters). */
    public const MULTI_SCHOOL_PARAMETER = 'PTM0005';

    /** Clave de sesion con el colegio elegido en modo multi-colegio. */
    public const SESSION_KEY = 'academic_school_id';

    public function isMultiSchoolEnabled(): bool
    {
        try {
            return (string) Parameter::where('parameter_code', self::MULTI_SCHOOL_PARAMETER)->value('value_default') === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Colegio activo: el elegido en sesion (modo multi-colegio) o el colegio
     * por defecto (modo mono-colegio).
     */
    public function currentSchool(): ?AcaSchool
    {
        try {
            if ($this->isMultiSchoolEnabled() && session()->has(self::SESSION_KEY)) {
                $school = AcaSchool::find(session(self::SESSION_KEY));
                if ($school) {
                    return $school;
                }
            }

            return AcaSchool::defaultSchool();
        } catch (\Throwable) {
            return null;
        }
    }

    public function currentSchoolId(): ?int
    {
        return $this->currentSchool()?->id;
    }

    /**
     * Cambia el colegio activo (solo tiene efecto con multi-colegio activo).
     */
    public function setSchool(int $schoolId): void
    {
        session([self::SESSION_KEY => $schoolId]);
    }

    /**
     * Datos que se comparten al frontend (HandleInertiaRequests).
     */
    public function shareData(): array
    {
        $school = $this->currentSchool();

        return [
            'multiSchool' => $this->isMultiSchoolEnabled(),
            'current' => $school ? [
                'id' => $school->id,
                'name' => $school->name,
            ] : null,
        ];
    }

    /**
     * Colegios disponibles para el selector (solo modo multi-colegio).
     */
    public function selectableSchools(): array
    {
        if (! $this->isMultiSchoolEnabled()) {
            return [];
        }

        return Cache::remember('academic_schools_select', 300, function () {
            return AcaSchool::where('status', true)->orderBy('name')
                ->get(['id', 'name'])
                ->toArray();
        });
    }
}
