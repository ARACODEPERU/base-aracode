<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una atajada del arquero con su tiempo, tipo y quién la remató.
 *
 * El total por jugador sigue viviendo en EventEditionMatchPlayerStat::saves,
 * pero ese contador no dice cómo fue cada atajada (penal, mano a mano, cabeza,
 * tiro olímpico) ni en qué tiempo ocurrió: esta tabla sí.
 */
class EventEditionMatchSave extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'match_id',
        'team_id',
        'player_id',
        'minute',
        'minute_in_period',
        'period',
        'save_type',
        'is_penalty',
        'shooter_player_id',
        'created_by',
    ];

    protected $casts = [
        'is_penalty' => 'boolean',
        'minute' => 'integer',
        'minute_in_period' => 'integer',
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
     * Arquero que atajó.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(EventEditionTeamPlayer::class, 'player_id', 'person_id');
    }

    /**
     * Jugador rival que remató (quien la pateó).
     */
    public function shooter(): BelongsTo
    {
        return $this->belongsTo(EventEditionTeamPlayer::class, 'shooter_player_id', 'person_id');
    }
}
