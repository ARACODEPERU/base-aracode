<?php

namespace Modules\Socialevents\Entities;

use App\Models\LocalSale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvenLocalArea extends Model
{
    protected $table = 'even_local_areas';

    public const TYPE_EVENT_HALL = 'event_hall';
    public const TYPE_SPORTS_FIELD = 'sports_field';
    public const TYPE_POOL = 'pool';
    public const TYPE_KIDS_PLAY = 'kids_play';

    protected $fillable = [
        'local_id',
        'name',
        'area_type',
        'capacity',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function typeOptions(): array
    {
        return [
            ['value' => self::TYPE_EVENT_HALL, 'label' => 'Salón de eventos'],
            ['value' => self::TYPE_SPORTS_FIELD, 'label' => 'Campo deportivo'],
            ['value' => self::TYPE_POOL, 'label' => 'Piscina'],
            ['value' => self::TYPE_KIDS_PLAY, 'label' => 'Juegos infantiles'],
        ];
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(LocalSale::class, 'local_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(EvenLocalRentalRate::class, 'area_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EvenLocalRentalItem::class, 'area_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EvenLocalAreaSchedule::class, 'area_id');
    }
}
