<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvenLocalRentalItem extends Model
{
    protected $table = 'even_local_rental_items';

    protected $fillable = [
        'rental_id',
        'area_id',
        'rental_rate_id',
        'event_date',
        'event_end_date',
        'start_time',
        'end_time',
        'total_hours',
        'hourly_rate',
        'base_amount',
        'extras_total',
        'subtotal',
        'is_included',
        'is_tournament',
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_end_date' => 'date',
        'total_hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'extras_total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'is_included' => 'boolean',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(EvenLocalRental::class, 'rental_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(EvenLocalArea::class, 'area_id');
    }

    public function rate(): BelongsTo
    {
        return $this->belongsTo(EvenLocalRentalRate::class, 'rental_rate_id');
    }
}
