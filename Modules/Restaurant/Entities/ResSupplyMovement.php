<?php

namespace Modules\Restaurant\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ResSupplyMovement extends Model
{
    protected $fillable = [
        'supply_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
        'local_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function supply(): BelongsTo
    {
        return $this->belongsTo(ResSupply::class, 'supply_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
