<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Academic\Entities\AcaStudent;
use Modules\Academic\Entities\AcaSubscriptionType;

/**
 * Suscripción recurrente de un alumno en MercadoPago.
 *
 * Guarda el estado del preapproval y el snapshot fiscal con el que se emiten
 * los comprobantes de cada cobro mensual.
 */
class AcaSubscriptionPreapproval extends Model
{
    protected $table = 'aca_subscription_preapprovals';

    protected $fillable = [
        'student_id',
        'subscription_id',
        'preapproval_id',
        'preapproval_plan_id',
        'status',
        'amount',
        'currency_id',
        'frequency',
        'frequency_type',
        'billing_day',
        'start_date',
        'next_payment_date',
        'last_charge_at',
        'cancelled_at',
        'last_synced_at',
        'payer_email',
        'external_reference',
        'invoice_data',
        'raw',
        'first_failure_notified_at',
        'grace_expired_notified_at',
        'is_test',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'frequency' => 'integer',
        'billing_day' => 'integer',
        'start_date' => 'date',
        'next_payment_date' => 'date',
        'last_charge_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'first_failure_notified_at' => 'datetime',
        'grace_expired_notified_at' => 'datetime',
        'invoice_data' => 'array',
        'raw' => 'array',
        'is_test' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(AcaStudent::class, 'student_id', 'id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AcaSubscriptionType::class, 'subscription_id');
    }

    /**
     * Una suscripción cancelada o sin autorizar no debe cobrar ni dar acceso.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['authorized', 'paused'], true);
    }

    /**
     * Días de reintento configurados para un cobro rechazado (3 por defecto).
     */
    public function graceDays(): int
    {
        return max(0, (int) config('services.mercadopago.subscription_grace_days', 3));
    }

    /**
     * Fin de la gracia: vencimiento del periodo más los días de reintento.
     */
    public function graceEndsAt(): ?Carbon
    {
        if (! $this->next_payment_date) {
            return null;
        }

        return $this->next_payment_date->copy()->endOfDay()->addDays($this->graceDays());
    }

    /**
     * El cobro ya venció pero todavía corre el plazo de reintento.
     */
    public function isInGrace(?Carbon $now = null): bool
    {
        if (! $this->next_payment_date) {
            return false;
        }

        $now ??= now();
        $vencimiento = $this->next_payment_date->copy()->endOfDay();

        return $now->greaterThan($vencimiento) && ! $this->hasGraceExpired($now);
    }

    /**
     * Pasaron los días de gracia sin un cobro aprobado.
     */
    public function hasGraceExpired(?Carbon $now = null): bool
    {
        $fin = $this->graceEndsAt();

        return $fin !== null && ($now ?? now())->greaterThan($fin);
    }
}
