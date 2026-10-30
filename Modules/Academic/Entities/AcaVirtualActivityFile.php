<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaVirtualActivityFile extends Model
{
    protected $table = 'aca_virtual_activity_files';

    protected $fillable = [
        'activity_id',
        'path',
        'original_name',
        'mime_type',
        'size_kb',
    ];

    protected $casts = [
        'size_kb' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(AcaVirtualActivity::class, 'activity_id');
    }
}
