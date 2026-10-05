<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcaSchoolFeeType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_recurring',
        'status',
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
        'status' => 'boolean',
    ];

    public function fees(): HasMany
    {
        return $this->hasMany(AcaSchoolFee::class, 'fee_type_id');
    }

    /**
     * Conceptos base del colegio. firstOrCreate = idempotente.
     */
    public static function ensureDefaults(): void
    {
        foreach (self::defaults() as $default) {
            self::firstOrCreate(
                ['code' => $default['code']],
                $default
            );
        }
    }

    public static function defaults(): array
    {
        return [
            ['code' => 'matricula', 'name' => 'Matrícula', 'is_recurring' => false, 'status' => true],
            ['code' => 'mensualidad', 'name' => 'Mensualidad', 'is_recurring' => true, 'status' => true],
            ['code' => 'auxiliar', 'name' => 'Auxiliar', 'is_recurring' => false, 'status' => true],
            ['code' => 'copias', 'name' => 'Copias y materiales de trabajo', 'is_recurring' => false, 'status' => true],
            ['code' => 'vigilancia', 'name' => 'Vigilancia del colegio', 'is_recurring' => false, 'status' => true],
            ['code' => 'certificados', 'name' => 'Certificados', 'is_recurring' => false, 'status' => true],
            ['code' => 'otros', 'name' => 'Otros', 'is_recurring' => false, 'status' => true],
        ];
    }

    public static function recurringType(): ?self
    {
        return self::where('code', 'mensualidad')->where('status', true)->first();
    }
}
