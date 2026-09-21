<?php

namespace Modules\Socialevents\Entities;

use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventEditionPlayerSuspension extends Model
{
    use HasFactory;

    public const TYPE_DEFINITIVE = 'definitive';
    public const TYPE_MATCHES = 'matches';
    public const TYPE_DATE_RANGE = 'date_range';

    protected $table = 'event_edition_player_suspensions';

    protected $fillable = [
        'edition_id',
        'player_id',
        'type',
        'matches_count',
        'matches_served',
        'starts_at',
        'ends_at',
        'reason',
        'suspended_by',
        'suspended_at',
        'lifted_at',
    ];

    protected $casts = [
        'suspended_at' => 'datetime',
        'lifted_at' => 'datetime',
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class, 'edition_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'player_id');
    }

    /**
     * ¿La suspensión sigue vigente (no levantada manualmente)?
     */
    public function isActive(): bool
    {
        return $this->lifted_at === null;
    }

    /**
     * Etiqueta legible del tipo de suspensión.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_DEFINITIVE => 'Definitiva (todo el torneo)',
            self::TYPE_MATCHES => 'Por ' . $this->matches_count . ' partido(s)',
            self::TYPE_DATE_RANGE => 'Por fechas',
            default => $this->type,
        };
    }

    /**
     * Estado legible para listados.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->lifted_at !== null) {
            return 'Levantada';
        }

        return match ($this->type) {
            self::TYPE_DEFINITIVE => 'Vigente',
            self::TYPE_MATCHES => $this->matches_served >= $this->matches_count
                ? 'Cumplida'
                : 'Vigente (' . ($this->matches_count - $this->matches_served) . ' partido(s) restante(s))',
            self::TYPE_DATE_RANGE => ($this->starts_at && $this->starts_at->isFuture())
                ? 'Programada'
                : (($this->ends_at && $this->ends_at->isPast()) ? 'Finalizada' : 'Vigente'),
            default => 'Vigente',
        };
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('lifted_at');
    }
}
