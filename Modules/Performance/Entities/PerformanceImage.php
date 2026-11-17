<?php

namespace Modules\Performance\Entities;

use Illuminate\Database\Eloquent\Model;

class PerformanceImage extends Model
{
    protected $table = 'performance_images';

    protected $guarded = [];

    protected $casts = [
        'optimized_at' => 'datetime',
        'quarantined_at' => 'datetime',
    ];
}
