<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo de diploma reutilizable (sólo anverso).
 *
 * Pertenece a un torneo (`event_id`), no a una edición concreta: la misma
 * plantilla se usa en varias ediciones. `edition_id` guarda la edición donde se
 * creó como referencia histórica. El tamaño del lienzo lo define la imagen de
 * fondo y los elementos se guardan en `layout` como JSON para el editor Konva.
 */
class EventEditionDiplomaTemplate extends Model
{
    protected $table = 'socialevent_edition_diploma_templates';

    protected $fillable = [
        'event_id',
        'edition_id',
        'award_key',
        'diploma_type',
        'year',
        'name',
        'background_path',
        'background_width',
        'background_height',
        'layout',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'layout' => 'array',
        'background_width' => 'integer',
        'background_height' => 'integer',
        'year' => 'integer',
        'is_active' => 'boolean',
    ];

    public const TYPE_TEAM = 'team';

    public const TYPE_PLAYER = 'player';

    /** @return array<int, string> */
    public static function types(): array
    {
        return [self::TYPE_TEAM, self::TYPE_PLAYER];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class, 'edition_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(EvenEvent::class, 'event_id');
    }

    public function isTeamType(): bool
    {
        return $this->diploma_type === self::TYPE_TEAM;
    }

    public function typeLabel(): string
    {
        return $this->isTeamType() ? 'Por equipo' : 'Por jugador';
    }

    public function diplomas(): HasMany
    {
        return $this->hasMany(EventEditionDiploma::class, 'template_id');
    }

    /** Ancho y alto reales del lienzo, con respaldo por si el fondo no cargó. */
    public function canvas(): array
    {
        return [
            'width' => $this->background_width ?: 1550,
            'height' => $this->background_height ?: 1096,
        ];
    }
}
