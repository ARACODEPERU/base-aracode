<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Academic\Database\factories\AcaSubscriptionTypeFactory;

class AcaSubscriptionType extends Model
{
    use HasFactory;

    /**
     * Periodos que admiten cobro automático y su duración en meses.
     */
    public const RECURRING_PERIODS = [
        'Mensual' => 1,
        'Trimestral' => 3,
        'Anual' => 12,
    ];

    /**
     * Duración en meses del plan cuando su periodo es recurrente.
     */
    public function recurringMonths(): ?int
    {
        return self::RECURRING_PERIODS[trim((string) $this->period)] ?? null;
    }

    /**
     * Indica si el periodo del plan se puede cobrar con MercadoPago.
     */
    public function supportsRecurring(): bool
    {
        return $this->recurringMonths() !== null;
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'usine',
        'description',
        'details',
        'prices',
        'status',
        'period',
        'order_number',
        'access_all_courses',
        'access_specialization_programs',
        'access_all_webinars',
        'can_view_specialization_zooms',
        'can_view_specialization_webinars',
        'recurring_enabled',
        'test_mode',
        'mercadopago_plan_id',
    ];

    protected $casts = [
        'details' => 'array',
        'prices' => 'array',
        'access_all_courses' => 'boolean',
        'access_specialization_programs' => 'boolean',
        'access_all_webinars' => 'boolean',
        'can_view_specialization_zooms' => 'boolean',
        'can_view_specialization_webinars' => 'boolean',
        'recurring_enabled' => 'boolean',
        'test_mode' => 'boolean',
    ];
}
