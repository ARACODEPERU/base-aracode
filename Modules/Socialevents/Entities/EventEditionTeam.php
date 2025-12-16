<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Socialevents\Database\factories\EventEditionTeamFactory;

class EventEditionTeam extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
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

    protected static function newFactory(): EventEditionTeamFactory
    {
        //return EventEditionTeamFactory::new();
    }
}
