<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Socialevents\Database\factories\EventEditionMatchFactory;

class EventEditionMatch extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
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
        'original_score'
    ];

    protected $casts = [
        'match_date' => 'datetime',
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

}
