<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Socialevents\Database\factories\EventEditionMatchSanctionFactory;

class EventEditionMatchSanction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'match_id',
        'player_id',
        'type',
        'minute',
        'amount_fine'
    ];

    public function player() {
        return $this->belongsTo(EventEditionTeamPlayer::class, 'player_id', 'person_id');
    }
}
