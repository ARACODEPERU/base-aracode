<?php

namespace Modules\Socialevents\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvenLocalAreaSchedule extends Model
{
    protected $table = 'even_local_area_schedules';

    protected $fillable = [
        'area_id',
        'day_of_week',
        'open_time',
        'close_time',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(EvenLocalArea::class, 'area_id');
    }
}
