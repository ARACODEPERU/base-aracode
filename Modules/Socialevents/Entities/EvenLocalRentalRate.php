<?php

namespace Modules\Socialevents\Entities;

use App\Models\LocalSale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvenLocalRentalRate extends Model
{
    protected $table = 'even_local_rental_rates';

    protected $fillable = [
        'local_id',
        'name',
        'hourly_rate',
        'is_active',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function local(): BelongsTo
    {
        return $this->belongsTo(LocalSale::class, 'local_id');
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(EvenLocalRental::class, 'rental_rate_id');
    }
}
