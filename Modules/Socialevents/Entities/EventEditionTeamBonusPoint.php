<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventEditionTeamBonusPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'edition_id',
        'team_id',
        'points',
        'reason',
        'created_by',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class, 'edition_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(EventTeam::class, 'team_id');
    }
}
