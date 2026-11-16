<?php

namespace Modules\Performance\Entities;

use Illuminate\Database\Eloquent\Model;

class PerformanceAlert extends Model
{
    protected $table = 'performance_alerts';

    protected $guarded = [];

    protected $casts = [
        'detected_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];
}
