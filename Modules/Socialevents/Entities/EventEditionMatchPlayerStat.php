<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Socialevents\Database\factories\EventEditionMatchPlayerStatFactory;

class EventEditionMatchPlayerStat extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'match_id',
        'player_id',
        'team_id',
        'goals',
        'assists',
        'saves',
        'minutes_played',
        'is_mvp',
        'clean_sheet'
    ];

    public function player() {
        return $this->belongsTo(EventEditionTeamPlayer::class, 'player_id', 'person_id');
    }
}
