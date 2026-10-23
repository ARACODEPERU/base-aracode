<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcaVirtualActivityOption extends Model
{
    protected $table = 'aca_virtual_activity_options';

    protected $fillable = [
        'question_id',
        'text',
        'is_correct',
        'sort_order',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(AcaVirtualActivityQuestion::class, 'question_id');
    }
}
