<?php

namespace Modules\Socialevents\Entities;

use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventEditionPlayerExclusion extends Model
{
    use HasFactory;

    protected $table = 'event_edition_player_exclusions';

    protected $fillable = [
        'edition_id',
        'player_id',
        'reason',
        'excluded_by',
        'excluded_at',
    ];

    protected $casts = [
        'excluded_at' => 'datetime',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class, 'edition_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'player_id');
    }
}