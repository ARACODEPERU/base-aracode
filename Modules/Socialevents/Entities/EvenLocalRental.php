<?php

namespace Modules\Socialevents\Entities;

use App\Models\LocalSale;
use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvenLocalRental extends Model
{
    protected $table = 'even_local_rentals';

    protected $fillable = [
        'local_id',
        'customer_id',
        'rental_rate_id',
        'event_type',
        'event_date',
        'event_end_date',
        'start_time',
        'end_time',
        'total_hours',
        'hourly_rate',
        'base_amount',
        'includes_tables_chairs',
        'includes_food',
        'beer_provided_by',
        'total_price',
        'paid_amount',
        'balance_amount',
        'payment_status',
        'reservation_status',
        'notes',
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_end_date' => 'date',
        'total_hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'includes_tables_chairs' => 'boolean',
        'includes_food' => 'boolean',
    ];

    public static function eventTypeOptions(): array
    {
        return [
            ['value' => 'birthday', 'label' => 'Cumpleaños'],
            ['value' => 'wedding', 'label' => 'Boda'],
            ['value' => 'quinceanera', 'label' => '15 años'],
            ['value' => 'party', 'label' => 'Fiesta'],
            ['value' => 'meeting', 'label' => 'Reunión'],
            ['value' => 'other', 'label' => 'Otro'],
        ];
    }

    public static function reservationStatusOptions(): array
    {
        return [
            ['value' => 'pending', 'label' => 'Pendiente'],
            ['value' => 'confirmed', 'label' => 'Confirmada'],
            ['value' => 'in_occupation', 'label' => 'En ocupación'],
            ['value' => 'completed', 'label' => 'Completada'],
            ['value' => 'cancelled', 'label' => 'Cancelada'],
        ];
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(LocalSale::class, 'local_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'customer_id');
    }

    public function rate(): BelongsTo
    {
        return $this->belongsTo(EvenLocalRentalRate::class, 'rental_rate_id');
    }

    public function extraCharges(): HasMany
    {
        return $this->hasMany(EvenLocalRentalExtraCharge::class, 'rental_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(EvenLocalRentalPayment::class, 'rental_id');
    }
}
