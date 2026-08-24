<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'bonus_points',
        'rank',
        'is_champion'
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(EventTeam::class, 'team_id');
    }
    public function edition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class, 'edition_id');
    }

    public function bonusPoints(): HasMany
    {
        return $this->hasMany(EventEditionTeamBonusPoint::class, 'team_id', 'team_id');
    }

    /**
     * Puntos totales (base + extras) usados para el ordenamiento y la tabla.
     */
    public function totalPoints(): int
    {
        return (int) $this->points + (int) $this->bonus_points;
    }

    /**
     * Recalcula el bonus acumulado del equipo a partir de sus registros de puntos extra.
     */
    public function recalculateBonus(): void
    {
        $total = (int) EventEditionTeamBonusPoint::where('edition_id', $this->edition_id)
            ->where('team_id', $this->team_id)
            ->sum('points');

        if ((int) $this->bonus_points !== $total) {
            // La tabla usa PK compuesta (edition_id, team_id), por eso actualizamos por claves.
            EventEditionTeam::where('edition_id', $this->edition_id)
                ->where('team_id', $this->team_id)
                ->update(['bonus_points' => $total]);
        }
    }
}
