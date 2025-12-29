<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Socialevents\Database\factories\EventEditionTeamFactory;

class EventEditionTeam extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $fillable = [
        'edition_id',
        'team_id',
        'matches_played',
        'matches_won',
        'matches_drawn',
        'matches_lost',
        'goals_for',
        'goals_against',
        'goal_difference',
        'points',
        'rank',
        'is_champion'
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(EventTeam::class, 'team_id');
    }
}
