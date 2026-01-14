<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Socialevents\Database\factories\EventEditionMatchParticipationFactory;

class EventEditionMatchParticipation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'match_id',
        'player_id',
        'role'
    ];

    protected static function newFactory(): EventEditionMatchParticipationFactory
    {
        //return EventEditionMatchParticipationFactory::new();
    }
}
