<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventEdition extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'event_id',
        'year',
        'name',
        'start_date',
        'end_date',
        // --- NUEVOS CAMPOS EN FILLABLE ---
        'competition_format',
        'score_points_win',
        'score_points_draw',
        'score_points_loss',
        'inscription_fee',
        'min_players_per_team',
        'max_players_per_team',
        'prize_details',
        // ---------------------------------
        'path_database_file',
        'name_database_file',
        'details',
        'status',
        'yellow_price',
        'direct_red_price',
        'double_yellow_price'
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(EvenEvent::class, 'event_id', 'id');
    }

    protected function prizeDetails(): Attribute
    {
        return Attribute::make(
            get: fn ($value) =>
                $value !== null
                    ? html_entity_decode($value, ENT_QUOTES, 'UTF-8')
                    : null,

            set: fn ($value) =>
                $value !== null
                    ? htmlentities($value, ENT_QUOTES, 'UTF-8')
                    : null
        );
    }

    protected function details(): Attribute
    {
        return Attribute::make(
            get: fn ($value) =>
                $value !== null
                    ? html_entity_decode($value, ENT_QUOTES, 'UTF-8')
                    : null,

            set: fn ($value) =>
                $value !== null
                    ? htmlentities($value, ENT_QUOTES, 'UTF-8')
                    : null
        );
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(EventEditionTeam::class, 'edition_id', 'id');
    }
}
