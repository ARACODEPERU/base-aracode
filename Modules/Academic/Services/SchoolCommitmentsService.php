<?php

namespace Modules\Academic\Services;

use Modules\Academic\Entities\AcaSchoolCharge;
use Modules\Academic\Entities\AcaSchoolEnrollment;
use Modules\Academic\Entities\AcaSchoolFee;
use Modules\Academic\Entities\AcaSchoolFeeType;
use Modules\Academic\Entities\AcaSchoolPaymentSchedule;

/**
 * Compromisos de pago automaticos de una matricula:
 * cargo pendiente de matricula segun la tarifa configurada para el
 * alcance del alumno (seccion > grado > nivel > general) y cronograma
 * de mensualidades (colegio privado con tarifa configurada).
 */
class SchoolCommitmentsService
{
    /**
     * Crea los compromisos de la matricula. Idempotente: los que ya
     * existen no se duplican.
     *
     * @return array{matricula_charge_id: ?int, installments_created: int}
     */
    public function createForEnrollment(AcaSchoolEnrollment $enrollment): array
    {
        $matricula = $this->createMatriculaCharge($enrollment);
        $installments = AcaSchoolPaymentSchedule::generateForEnrollment($enrollment);

        return [
            'matricula_charge_id' => $matricula?->id,
            'installments_created' => $installments,
        ];
    }

    /**
     * Cargo PENDIENTE de matricula. Idempotente: si ya existe un cargo
     * no anulado para la matricula en este anio no se duplica.
     */
    public function createMatriculaCharge(AcaSchoolEnrollment $enrollment): ?AcaSchoolCharge
    {
        $feeType = AcaSchoolFeeType::where('code', 'matricula')->where('status', true)->first();

        if (! $feeType) {
            return null;
        }

        $exists = AcaSchoolCharge::where('enrollment_id', $enrollment->id)
            ->where('fee_type_id', $feeType->id)
            ->whereIn('status', [AcaSchoolCharge::STATUS_PENDIENTE, AcaSchoolCharge::STATUS_PAGADO])
            ->exists();

        if ($exists) {
            return null;
        }

        $section = $enrollment->section;
        $grade = $section?->grade;

        $fee = AcaSchoolFee::resolve(
            $enrollment->year_id,
            $feeType->id,
            $grade?->level_id,
            $grade?->id,
            $section?->id
        );

        if (! $fee || (float) $fee->amount <= 0) {
            return null;
        }

        return AcaSchoolCharge::create([
            'school_id' => $enrollment->school_id,
            'enrollment_id' => $enrollment->id,
            'fee_type_id' => $feeType->id,
            'description' => $feeType->name.' '.($enrollment->year?->year ?? ''),
            'amount' => $fee->amount,
            'status' => AcaSchoolCharge::STATUS_PENDIENTE,
        ]);
    }
}
