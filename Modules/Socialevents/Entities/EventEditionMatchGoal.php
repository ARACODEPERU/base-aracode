<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un gol del partido con su tiempo y minuto.
 *
 * El total por jugador sigue viviendo en EventEditionMatchPlayerStat::goals,
 * pero ese contador no dice cuándo se hizo cada gol: esta tabla sí, y es lo que
 * permite separar los goles del tiempo reglamentario de los de la prórroga.
 */
class EventEditionMatchGoal extends Model
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
        'goal_type',
        'is_own_goal',
        'assist_player_id',
        'created_by',
    ];

    protected $casts = [
        'is_own_goal' => 'boolean',
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
     * Autor del gol.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(EventEditionTeamPlayer::class, 'player_id', 'person_id');
    }

    /**
     * Asistente del gol (el jugador que lo habilitó).
     */
    public function assister(): BelongsTo
    {
        return $this->belongsTo(EventEditionTeamPlayer::class, 'assist_player_id', 'person_id');
    }
}
