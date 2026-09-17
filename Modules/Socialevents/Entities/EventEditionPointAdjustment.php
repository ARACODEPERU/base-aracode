<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventEditionPointAdjustment extends Model
{
    protected $fillable = [
        'edition_id',
        'team_id',
        'match_id',
        'report_id',
        'points',
        'reason',
        'created_by',
    ];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(EventEdition::class, 'edition_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(EventTeam::class, 'team_id');
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(EventEditionMatch::class, 'match_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(EventEditionMatchReport::class, 'report_id');
    }

    /**
     * Puntos ajustados por equipo para una edicion, indexados por team_id.
     *
     * @return array<int, int> team_id => neto (negativo resta, positivo suma)
     */
    public static function netByTeam(int $editionId): array
    {
        return self::query()
            ->where('edition_id', $editionId)
            ->selectRaw('team_id, SUM(points) AS net')
            ->groupBy('team_id')
            ->pluck('net', 'team_id')
            ->map(fn ($net) => (int) $net)
            ->all();
    }

    /**
     * Ajustes individuales por equipo (para badges y tooltips).
     *
     * @return array<int, array<string, mixed>> team_id => lista de ajustes
     */
    public static function listByTeam(int $editionId): array
    {
        return self::query()
            ->where('edition_id', $editionId)
            ->with('team:id,name')
            ->orderByDesc('id')
            ->get()
            ->groupBy('team_id')
            ->map(fn ($items) => $items->map(fn ($a) => [
                'id' => $a->id,
                'points' => (int) $a->points,
                'reason' => $a->reason,
                'match_id' => $a->match_id,
                'report_id' => $a->report_id,
                'created_by' => $a->created_by,
                'created_at' => optional($a->created_at)->toDateTimeString(),
            ])->values()->all())
            ->all();
    }
}
