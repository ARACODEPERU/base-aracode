<?php

namespace Modules\Academic\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaSchoolPaymentSchedule extends Model
{
    /** Cuotas de marzo a diciembre. */
    public const INSTALLMENTS = 10;

    /** Dia de vencimiento de cada cuota. */
    public const DUE_DAY = 5;

    protected $fillable = [
        'enrollment_id',
        'fee_type_id',
        'installment',
        'due_date',
        'amount',
        'charge_id',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolEnrollment::class, 'enrollment_id');
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolFeeType::class, 'fee_type_id');
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(AcaSchoolCharge::class, 'charge_id');
    }

    public function isPaid(): bool
    {
        return ! is_null($this->charge_id);
    }

    /**
     * Genera el cronograma de mensualidades de la matricula (10 cuotas
     * fijas, marzo a diciembre, vencimiento dia 5). Solo para colegios
     * privados y solo si existe tarifa de mensualidad configurada para
     * el alcance de la seccion del alumno.
     *
     * Idempotente: las cuotas ya existentes no se duplican.
     */
    public static function generateForEnrollment(AcaSchoolEnrollment $enrollment): int
    {
        $school = $enrollment->school;
        $year = $enrollment->year;

        if (! $school || ! $year || $school->type !== AcaSchool::TYPE_PRIVADO) {
            return 0;
        }

        $feeType = AcaSchoolFeeType::recurringType();

        if (! $feeType) {
            return 0;
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
            return 0;
        }

        $yearNumber = (int) $year->year;
        $created = 0;

        foreach (range(1, self::INSTALLMENTS) as $installment) {
            $month = $installment + 2; // 1 = marzo ... 10 = diciembre

            $exists = self::where('enrollment_id', $enrollment->id)
                ->where('fee_type_id', $feeType->id)
                ->where('installment', $installment)
                ->exists();

            if ($exists) {
                continue;
            }

            self::create([
                'enrollment_id' => $enrollment->id,
                'fee_type_id' => $feeType->id,
                'installment' => $installment,
                'due_date' => Carbon::create($yearNumber, $month, self::DUE_DAY)->toDateString(),
                'amount' => $fee->amount,
            ]);

            $created++;
        }

        return $created;
    }
}
