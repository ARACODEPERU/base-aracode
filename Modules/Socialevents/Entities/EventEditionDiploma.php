<?php

namespace Modules\Socialevents\Entities;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Diploma emitido.
 *
 * Conserva copias congeladas del ganador, el plantel y el layout para poder
 * reimprimir exactamente el mismo documento.
 */
class EventEditionDiploma extends Model
{
    protected $table = 'socialevent_edition_diplomas';

    protected $fillable = [
        'public_token',
        'edition_id',
        'template_id',
        'award_id',
        'recipient_type',
        'team_id',
        'person_id',
        'recipient_name',
        'team_name',
        'award_label',
        'edition_name',
        'squad_snapshot',
        'layout_snapshot',
        'issued_at',
        'issued_by',
        'views',
    ];

    protected $casts = [
        'squad_snapshot' => 'array',
        'layout_snapshot' => 'array',
        'issued_at' => 'datetime',
        'views' => 'integer',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class, 'edition_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EventEditionDiplomaTemplate::class, 'template_id');
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(EventEditionAward::class, 'award_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(EventTeam::class, 'team_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
