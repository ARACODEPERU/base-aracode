<?php

namespace Modules\Socialevents\Entities;

use App\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Premio otorgado en una edición (campeón, goleador, mejor arquero, etc.).
 *
 * Guarda el nombre del ganador como copia, además de la referencia a la
 * persona/equipo, para que el diploma emitido no cambie si luego se editan.
 */
class EventEditionAward extends Model
{
    protected $table = 'socialevent_edition_awards';

    protected $fillable = [
        'edition_id',
        'award_key',
        'label',
        'recipient_type',
        'team_id',
        'person_id',
        'recipient_name',
        'team_name',
        'source',
        'place',
        'meta',
        'sort_order',
        'is_published',
        'template_id',
    ];

    protected $casts = [
        'meta' => 'array',
        'place' => 'integer',
        'sort_order' => 'integer',
        'is_published' => 'boolean',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class, 'edition_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(EventTeam::class, 'team_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function diplomas(): HasMany
    {
        return $this->hasMany(EventEditionDiploma::class, 'award_id');
    }

    /** Modelo de diploma elegido explícitamente para este premio. */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EventEditionDiplomaTemplate::class, 'template_id');
    }

    public function diplomaType(): string
    {
        return $this->isTeamAward()
            ? EventEditionDiplomaTemplate::TYPE_TEAM
            : EventEditionDiplomaTemplate::TYPE_PLAYER;
    }

    /** El equipo premiado recibe un único diploma con su plantel. */
    public function isTeamAward(): bool
    {
        return $this->recipient_type === 'team';
    }
}
