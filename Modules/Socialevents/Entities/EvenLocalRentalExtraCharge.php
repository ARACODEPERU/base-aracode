<?php

namespace Modules\Socialevents\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvenLocalRentalExtraCharge extends Model
{
    public const PHASE_BOOKING = 'booking';

    public const PHASE_DURING_EVENT = 'during_event';

    protected $table = 'even_local_rental_extra_charges';

    protected $fillable = [
        'rental_id',
        'description',
        'amount',
        'phase',
        'reason',
        'added_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(EvenLocalRental::class, 'rental_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
