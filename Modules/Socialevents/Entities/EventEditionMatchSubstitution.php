<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventEditionMatchSubstitution extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'match_id',
        'team_id',
        'player_out_id',
        'player_in_id',
        'minute',
        'minute_in_period',
        'period',
        'reason',
        'created_by',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(EventEditionMatch::class, 'match_id', 'id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(EventTeam::class, 'team_id', 'id');
    }

    /**
     * Jugador que sale del campo.
     */
    public function playerOut(): BelongsTo
    {
        return $this->belongsTo(EventEditionTeamPlayer::class, 'player_out_id', 'person_id');
    }

    /**
     * Jugador que entra al campo.
     */
    public function playerIn(): BelongsTo
    {
        return $this->belongsTo(EventEditionTeamPlayer::class, 'player_in_id', 'person_id');
    }
}
