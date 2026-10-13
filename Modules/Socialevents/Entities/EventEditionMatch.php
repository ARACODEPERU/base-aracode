<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Socialevents\Database\factories\EventEditionMatchFactory;

class EventEditionMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'edition_id',
        'team_h_id',
        'team_a_id',
        'phase',
        'round_number',
        'group_name',
        'match_date',
        'location',
        'score_h',
        'score_a',
        'status',
        'placeholder_h',
        'placeholder_a',
        'original_score',
        'penalty_rounds',
        'penalties',
        'half_minutes_override',
        'halves_override',
        'extra_half_minutes_override',
        'extra_time_played',
        'score_regular_h',
        'score_regular_a',
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'penalties' => 'array',
        'extra_time_played' => 'boolean',
        'half_minutes_override' => 'integer',
        'halves_override' => 'integer',
        'extra_half_minutes_override' => 'integer',
    ];

    public function equipolocal(): BelongsTo
    {
        return $this->belongsTo(EventTeam::class, 'team_h_id', 'id');
    }

    public function equipovisitante(): BelongsTo
    {
        return $this->belongsTo(EventTeam::class, 'team_a_id', 'id');
    }

    public function edicion(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class, 'edition_id', 'id');
    }

    public function substitutions(): HasMany
    {
        return $this->hasMany(EventEditionMatchSubstitution::class, 'match_id', 'id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(EventEditionMatchGoal::class, 'match_id', 'id')->orderBy('minute')->orderBy('id');
    }
}
